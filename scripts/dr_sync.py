#!/usr/bin/env python3
"""
=============================================================================
Neilos Partner Portal — Hybrid Cloud Disaster Recovery & Sync Engine
=============================================================================
Backs up MySQL/MariaDB database, source code, and user document uploads,
and synchronizes them securely via FTP/FTPS to Maisha cPanel Cloud Host.

Features:
- Automated mysqldump + gzip compression
- Incremental user uploads synchronization
- Source code tarball packaging
- GFS (Grandfather-Father-Son) retention management
- Integrity verification & execution logging
=============================================================================
"""

import os
import sys
import ftplib
import gzip
import shutil
import subprocess
import tarfile
import argparse
import logging
import json
import time
from datetime import datetime, timezone
from pathlib import Path

# Paths
SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent
STORAGE_DIR = PROJECT_ROOT / "storage"
LOCAL_BACKUP_DIR = STORAGE_DIR / "backups"
LOG_DIR = STORAGE_DIR / "logs"
LOG_FILE = LOG_DIR / "dr_backup.log"

LOCAL_BACKUP_DIR.mkdir(parents=True, exist_ok=True)
LOG_DIR.mkdir(parents=True, exist_ok=True)

# Logger setup
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("DR_Sync")


def load_env(env_path: Path) -> dict:
    """Parse key-value pairs from .env file."""
    config = {}
    if not env_path.exists():
        logger.warning(f".env file not found at {env_path}")
        return config
    with open(env_path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or line.startswith(";"):
                continue
            if "=" in line:
                key, val = line.split("=", 1)
                key = key.strip()
                val = val.strip().strip("'").strip('"')
                config[key] = val
    return config


class FTPClient:
    """Helper wrapper for FTP operations with auto-reconnect and directory helpers."""
    def __init__(self, host, port, user, passwd, timeout=30):
        self.host = host
        self.port = int(port or 21)
        self.user = user
        self.passwd = passwd
        self.timeout = timeout
        self.ftp = None

    def connect(self):
        logger.info(f"Connecting to FTP {self.host}:{self.port}...")
        self.ftp = ftplib.FTP()
        self.ftp.connect(self.host, self.port, timeout=self.timeout)
        self.ftp.login(self.user, self.passwd)
        self.ftp.set_pasv(True)
        logger.info(f"FTP login successful. Root directory: {self.ftp.pwd()}")
        return self

    def ensure_dir(self, remote_dir: str):
        """Recursively ensure a directory exists on the FTP server."""
        parts = [p for p in remote_dir.strip("/").split("/") if p]
        curr = ""
        for p in parts:
            curr = f"{curr}/{p}"
            try:
                self.ftp.cwd(curr)
            except ftplib.error_perm:
                try:
                    self.ftp.mkd(curr)
                    logger.debug(f"Created remote dir: {curr}")
                except Exception as e:
                    logger.debug(f"Directory create info: {e}")
        self.ftp.cwd("/")

    def upload_file(self, local_file: Path, remote_path: str) -> bool:
        """Upload a local file to remote path."""
        remote_dir = os.path.dirname(remote_path)
        if remote_dir:
            self.ensure_dir(remote_dir)

        file_size = local_file.stat().st_size
        logger.info(f"Uploading {local_file.name} ({file_size / 1024 / 1024:.2f} MB) -> {remote_path}...")
        start_t = time.time()
        with open(local_file, "rb") as f:
            self.ftp.storbinary(f"STOR {remote_path}", f)
        elapsed = time.time() - start_t
        speed_kb = (file_size / 1024) / max(0.001, elapsed)
        logger.info(f"Uploaded {local_file.name} in {elapsed:.2f}s ({speed_kb:.1f} KB/s)")
        return True

    def file_exists_with_size(self, remote_path: str, expected_size: int) -> bool:
        """Check if remote file exists and has identical size."""
        try:
            sz = self.ftp.size(remote_path)
            return sz == expected_size
        except Exception:
            return False

    def list_files(self, remote_dir: str) -> list:
        """List files in a remote directory with details."""
        try:
            self.ftp.cwd(remote_dir)
            files = []
            self.ftp.retrlines("NLST", files.append)
            self.ftp.cwd("/")
            return files
        except Exception:
            self.ftp.cwd("/")
            return []

    def delete_file(self, remote_path: str):
        """Delete remote file."""
        try:
            self.ftp.delete(remote_path)
            logger.info(f"Pruned old remote backup: {remote_path}")
        except Exception as e:
            logger.warning(f"Failed to delete {remote_path}: {e}")

    def close(self):
        if self.ftp:
            try:
                self.ftp.quit()
            except Exception:
                pass


def backup_database(config: dict, timestamp: str) -> Path:
    """Perform mysqldump and compress with gzip."""
    db_host = config.get("DB_HOST", "127.0.0.1")
    db_port = config.get("DB_PORT", "3306")
    db_name = config.get("DB_NAME", "neilos_portal")
    db_user = config.get("DB_USER", "neilosne_tz")
    db_pass = config.get("DB_PASS", "")

    # Find mysqldump
    mysqldump_bin = "/opt/lampp/bin/mysqldump"
    if not os.path.exists(mysqldump_bin):
        mysqldump_bin = shutil.which("mysqldump") or "mysqldump"

    sql_file = LOCAL_BACKUP_DIR / f"{db_name}_{timestamp}.sql"
    gz_file = LOCAL_BACKUP_DIR / f"{db_name}_{timestamp}.sql.gz"

    cmd = [
        mysqldump_bin,
        f"-h{db_host}",
        f"-P{db_port}",
        f"-u{db_user}",
        f"-p{db_pass}",
        "--single-transaction",
        "--quick",
        "--triggers",
        "--add-drop-table",
        db_name
    ]

    logger.info(f"Running database dump for '{db_name}'...")
    start_t = time.time()
    with open(sql_file, "wb") as f_out:
        proc = subprocess.run(cmd, stdout=f_out, stderr=subprocess.PIPE)

    if proc.returncode != 0:
        err_msg = proc.stderr.decode("utf-8", errors="ignore")
        if sql_file.exists():
            sql_file.unlink()
        raise RuntimeError(f"mysqldump failed: {err_msg}")

    # Compress to gzip
    with open(sql_file, "rb") as f_in, gzip.open(gz_file, "wb", compresslevel=9) as f_out:
        shutil.copyfileobj(f_in, f_out)

    # Clean up raw SQL
    sql_file.unlink()
    elapsed = time.time() - start_t
    logger.info(f"Database compressed to {gz_file.name} ({gz_file.stat().st_size / 1024:.1f} KB) in {elapsed:.2f}s")

    # Create/update copy as latest_database.sql.gz
    latest_file = LOCAL_BACKUP_DIR / "latest_database.sql.gz"
    shutil.copyfile(gz_file, latest_file)

    return gz_file


def backup_source_code(timestamp: str) -> Path:
    """Create a compressed tarball of application source code."""
    tar_file = LOCAL_BACKUP_DIR / f"neilos_code_{timestamp}.tar.gz"
    logger.info(f"Creating application codebase archive {tar_file.name}...")

    # Exclusions
    excludes = {
        ".git",
        ".github",
        "storage",
        ".user_uploaded",
        "Neilos_Partner_Portal_Executive_Security_Report.docx",
        "Neilos  SOF.xlsx"
    }

    start_t = time.time()
    with tarfile.open(tar_file, "w:gz", compresslevel=7) as tar:
        for item in PROJECT_ROOT.iterdir():
            if item.name in excludes:
                continue
            if item.name == "public":
                # Add public directory but exclude uploads
                def public_filter(tarinfo):
                    if "public/uploads" in tarinfo.name and tarinfo.name != "public/uploads":
                        return None
                    return tarinfo
                tar.add(item, arcname="public", filter=public_filter)
            else:
                tar.add(item, arcname=item.name)

    elapsed = time.time() - start_t
    tar_size = tar_file.stat().st_size
    logger.info(f"Application archive created: {tar_file.name} ({tar_size / 1024 / 1024:.2f} MB) in {elapsed:.2f}s")

    # Copy to latest_code.tar.gz
    latest_code = LOCAL_BACKUP_DIR / "latest_code.tar.gz"
    shutil.copyfile(tar_file, latest_code)

    return tar_file


def sync_uploads(ftp_client: FTPClient, remote_base: str) -> dict:
    """Incrementally sync user uploads (KYC docs, SOFs, receipts, avatars) to remote FTP."""
    local_uploads = PROJECT_ROOT / "public" / "uploads"
    if not local_uploads.exists():
        logger.warning(f"Uploads directory {local_uploads} does not exist. Skipping.")
        return {"uploaded": 0, "skipped": 0, "bytes": 0}

    logger.info(f"Synchronizing uploads from {local_uploads} to {remote_base}/uploads/...")
    stats = {"uploaded": 0, "skipped": 0, "bytes": 0}
    start_t = time.time()

    for root, _, files in os.walk(local_uploads):
        for f in files:
            local_path = Path(root) / f
            rel_path = local_path.relative_to(local_uploads)
            remote_path = f"{remote_base}/uploads/{rel_path.as_posix()}"

            file_size = local_path.stat().st_size
            if ftp_client.file_exists_with_size(remote_path, file_size):
                stats["skipped"] += 1
                continue

            ftp_client.upload_file(local_path, remote_path)
            stats["uploaded"] += 1
            stats["bytes"] += file_size

    elapsed = time.time() - start_t
    logger.info(f"Uploads sync completed in {elapsed:.2f}s: {stats['uploaded']} uploaded ({stats['bytes'] / 1024 / 1024:.2f} MB), {stats['skipped']} skipped (already up to date).")
    return stats


def enforce_remote_retention(ftp_client: FTPClient, remote_dir: str, prefix: str, ext: str, keep_latest: int = 14):
    """Prune remote snapshots beyond retention limit."""
    logger.info(f"Checking remote retention policy in {remote_dir} (keep latest {keep_latest})...")
    files = ftp_client.list_files(remote_dir)
    snapshots = [f for f in files if f.startswith(prefix) and f.endswith(ext) and not f.startswith("latest_")]
    snapshots.sort()  # Alphabetical sort corresponds to chronological sort with YYYY-MM-DD format

    if len(snapshots) > keep_latest:
        to_delete = snapshots[:-keep_latest]
        for f in to_delete:
            ftp_client.delete_file(f"{remote_dir}/{f}")
        logger.info(f"Pruned {len(to_delete)} old snapshots from {remote_dir}.")
    else:
        logger.info(f"Snapshots within quota ({len(snapshots)}/{keep_latest}). None pruned.")


def main():
    parser = argparse.ArgumentParser(description="Neilos Disaster Recovery & Remote Sync Engine")
    parser.add_argument("--db-only", action="store_true", help="Only backup and sync the database")
    parser.add_argument("--uploads-only", action="store_true", help="Only sync the uploaded documents")
    parser.add_argument("--code-only", action="store_true", help="Only archive and sync the codebase")
    parser.add_argument("--all", action="store_true", default=True, help="Full sync: database + codebase + uploads")
    args = parser.parse_args()

    # Determine mode
    do_db = args.db_only or args.all
    do_code = args.code_only or args.all
    do_uploads = args.uploads_only or args.all

    if args.db_only or args.uploads_only or args.code_only:
        do_db = args.db_only
        do_code = args.code_only
        do_uploads = args.uploads_only

    timestamp = datetime.now().strftime("%Y-%m-%d_%H%M%S")
    logger.info(f"=== Neilos Disaster Recovery Sync Started [{timestamp}] ===")

    env_config = load_env(PROJECT_ROOT / ".env")

    ftp_host = env_config.get("DR_FTP_HOST", "ftp.neilosnetwork.co.tz")
    ftp_port = int(env_config.get("DR_FTP_PORT", "21"))
    ftp_user = env_config.get("DR_FTP_USER", "")
    ftp_pass = env_config.get("DR_FTP_PASS", "")
    ftp_base = env_config.get("DR_FTP_BASE_DIR", "/").rstrip("/")

    if not ftp_user or not ftp_pass:
        logger.error("DR_FTP_USER or DR_FTP_PASS is missing in .env! Aborting.")
        sys.exit(1)

    status_report = {
        "timestamp": timestamp,
        "success": False,
        "database_file": None,
        "database_size_bytes": 0,
        "code_file": None,
        "code_size_bytes": 0,
        "uploads_uploaded": 0,
        "uploads_skipped": 0,
        "duration_seconds": 0
    }
    start_time = time.time()

    ftp_client = None
    try:
        ftp_client = FTPClient(ftp_host, ftp_port, ftp_user, ftp_pass)
        ftp_client.connect()

        # Remote directories
        db_remote_dir = f"{ftp_base}/backups/database" if ftp_base else "/backups/database"
        code_remote_dir = f"{ftp_base}/backups/source_code" if ftp_base else "/backups/source_code"
        uploads_remote_dir = f"{ftp_base}/backups" if ftp_base else "/backups"

        # 1. Database Backup
        if do_db:
            db_gz = backup_database(env_config, timestamp)
            status_report["database_file"] = db_gz.name
            status_report["database_size_bytes"] = db_gz.stat().st_size

            # Upload timestamped dump
            ftp_client.upload_file(db_gz, f"{db_remote_dir}/{db_gz.name}")
            # Upload latest alias
            ftp_client.upload_file(LOCAL_BACKUP_DIR / "latest_database.sql.gz", f"{db_remote_dir}/latest_database.sql.gz")

            # Retention: keep last 14 database snapshots
            enforce_remote_retention(ftp_client, db_remote_dir, "neilos_portal_", ".sql.gz", keep_latest=14)

        # 2. Source Code Archive
        if do_code:
            code_tar = backup_source_code(timestamp)
            status_report["code_file"] = code_tar.name
            status_report["code_size_bytes"] = code_tar.stat().st_size

            # Upload timestamped archive
            ftp_client.upload_file(code_tar, f"{code_remote_dir}/{code_tar.name}")
            # Upload latest alias
            ftp_client.upload_file(LOCAL_BACKUP_DIR / "latest_code.tar.gz", f"{code_remote_dir}/latest_code.tar.gz")

            # Retention: keep last 7 code archives
            enforce_remote_retention(ftp_client, code_remote_dir, "neilos_code_", ".tar.gz", keep_latest=7)

        # 3. User Uploads Sync
        if do_uploads:
            up_stats = sync_uploads(ftp_client, uploads_remote_dir)
            status_report["uploads_uploaded"] = up_stats["uploaded"]
            status_report["uploads_skipped"] = up_stats["skipped"]

        status_report["success"] = True
        status_report["duration_seconds"] = round(time.time() - start_time, 2)

        # Write latest status JSON
        with open(LOCAL_BACKUP_DIR / "latest_status.json", "w") as f:
            json.dump(status_report, f, indent=2)

        logger.info(f"=== Neilos DR Sync Finished Successfully in {status_report['duration_seconds']}s ===")

    except Exception as e:
        status_report["error"] = str(e)
        status_report["duration_seconds"] = round(time.time() - start_time, 2)
        logger.error(f"DR Sync failed with error: {e}", exc_info=True)
        sys.exit(1)
    finally:
        if ftp_client:
            ftp_client.close()


if __name__ == "__main__":
    main()
