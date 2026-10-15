# Neilos Partner Portal — System Portability & Migration Guide

This document outlines the complete **Export → Move → Import → Run** migration process for the Neilos Partner Portal.

---

## 1. What is Included in the Migration Package?

When you export the system, a single standalone ZIP package (`system-migration-YYYY-MM-DD-HHMMSS.zip`) is generated containing:

1. **Complete Database Snapshot (`database/database-backup.sql`)**:
   - All tables, rows, primary keys, auto-increments, indexes, constraints, and views.
   - Preserves all User IDs, Order Numbers, Partner relationships, and timeline events without renumbering.
2. **All Uploaded Physical Files (`storage/uploads/`)**:
   - Signed SOF contracts (`uploads/sof/`)
   - Order attachments & evidence (`uploads/orders/`)
   - Contractor KYC documents (`uploads/kyc/`)
   - Bulk upload CSV/Excel files (`uploads/bulk_upload/`)
   - User profile images (`uploads/profiles/`)
3. **Portable Application Configuration (`config/portable-config.json`)**:
   - Company branding, legal details, TIN/VAT, contact info.
   - Commercial rules, USD exchange rates, SLA parameters.
4. **Cryptographic Manifest (`manifest.json`)**:
   - File counts, sizes, database table statistics, and individual SHA-256 checksums to verify package integrity before restoration.

---

## 2. Exporting the System from Device A

### Method A: Web Interface (Recommended)
1. Log in to the Neilos Portal on Device A as an **Administrator**.
2. Go to **Settings** from the left navigation menu.
3. Click the **Backup & Migration** tab.
4. Click **Export & Download Migration Package (.zip)**.
5. The package will download directly to your computer.

### Method B: Command Line (CLI)
Run the PHP export script in your terminal:
```bash
php scripts/export-system.php
```
*Optional custom output path:*
```bash
php scripts/export-system.php --output="C:\Backups\neilos-live-backup.zip"
```

---

## 3. Moving the Package to Device B

Copy the generated `.zip` file from Device A to Device B using USB, SCP, SFTP, or shared network storage.

---

## 4. Restoring the System on Device B

### Step 1: Environment Setup on Device B
Ensure Device B has:
- **PHP 8.1+** (with `pdo_mysql`, `zip`, `gd` extensions enabled).
- **MariaDB 10.4+ / MySQL 8.0+**.
- **Apache / Nginx / PHP Built-in Server**.

Configure your environment settings in `.env`:
```bash
cp .env.example .env
```
Edit `.env` on Device B with local credentials:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=neilos_portal
DB_USER=root
DB_PASS=your_password
APP_URL=http://localhost/Neilos/public
```

---

### Step 2: Restoring the Package

#### Method A: Web Interface
1. Open the portal in your browser on Device B.
2. Log in as **Admin**.
3. Go to **Settings > Backup & Migration**.
4. In the **Restore / Import System (Device B)** section, select your `.zip` migration package.
5. Click **Validate & Restore System Migration** and confirm the prompt.
6. The system will restore the database, extract all upload files, apply schema synchronizations, and display the health verification audit.

#### Method B: Command Line (CLI)
Run the automated import script:
```bash
php scripts/import-system.php --package="path/to/system-migration-2026-08-20.zip" --yes
```

---

## 5. Dockerized Deployment & Migration

To run the entire portal inside Docker on Device B:

1. Copy the codebase and migration package to Device B.
2. Launch the containers:
   ```bash
   docker-compose up -d
   ```
3. Run the import script inside the container:
   ```bash
   docker-compose exec app php scripts/import-system.php --package="/var/www/html/public/uploads/system-migration-2026-08-20.zip" --yes
   ```
4. Access the portal at `http://localhost:8080`.

---

## 6. Post-Migration Verification Checklist

Run the built-in integrity auditor to verify the restoration:
```bash
php scripts/verify-migration.php
```

Or manually verify:
- [ ] Database connects successfully with all tables and rows intact.
- [ ] Existing users can log in with their existing credentials.
- [ ] All orders, customers, and partners are present.
- [ ] Uploaded PDFs, DOCX, and signed SOFs open and download correctly.
- [ ] Order timelines and activity logs are preserved.
- [ ] Generating new SOFs and uploading new documents works smoothly.
- [ ] No hard-coded machine paths are used.
