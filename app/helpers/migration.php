<?php
// ============================================================
// Neilos Partner Portal — System Migration & Portability Engine
// ============================================================

if (!defined('ROOT_DIR'))   define('ROOT_DIR', dirname(__DIR__, 2));
if (!defined('APP_DIR'))    define('APP_DIR', ROOT_DIR . '/app');
if (!defined('PUBLIC_DIR')) define('PUBLIC_DIR', ROOT_DIR . '/public');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', PUBLIC_DIR . '/uploads/');

/**
 * Generate a complete, self-contained SQL dump of the database using PDO.
 * Works on any server without requiring external mysqldump binary.
 */
function generateDatabaseSqlDump(PDO $pdo): string {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $sql  = "-- ============================================================\n";
    $sql .= "-- Neilos Portal Database Migration Snapshot\n";
    $sql .= "-- Database: `{$dbName}`\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s T') . "\n";
    $sql .= "-- Host: " . php_uname('n') . "\n";
    $sql .= "-- ============================================================\n\n";

    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $sql .= "SET time_zone = '+00:00';\n";
    $sql .= "SET NAMES utf8mb4;\n\n";
    $sql .= "START TRANSACTION;\n\n";

    // Get all tables
    $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

    foreach ($tables as $row) {
        $table = $row[0];

        $sql .= "-- ------------------------------------------------------------\n";
        $sql .= "-- Table structure & data for `{$table}`\n";
        $sql .= "-- ------------------------------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $createSql  = $createStmt['Create Table'] ?? '';
        $sql .= $createSql . ";\n\n";

        // Dump table data in chunks
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount  = (int)$countStmt->fetchColumn();

        if ($rowCount > 0) {
            $offset = 0;
            $limit  = 200;

            while ($offset < $rowCount) {
                $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
                $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $cols = array_keys($rows[0]);
                    $quotedCols = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", $cols);
                    $colList = implode(', ', $quotedCols);

                    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                    $valLines = [];

                    foreach ($rows as $r) {
                        $vals = [];
                        foreach ($r as $val) {
                            if ($val === null) {
                                $vals[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $vals[] = $val;
                            } else {
                                $vals[] = $pdo->quote($val);
                            }
                        }
                        $valLines[] = "(" . implode(', ', $vals) . ")";
                    }

                    $sql .= implode(",\n", $valLines) . ";\n";
                }
                $offset += $limit;
            }
            $sql .= "\n";
        }
    }

    // Get Views if any
    $viewsStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $viewsStmt->fetchAll(PDO::FETCH_NUM);
    foreach ($views as $row) {
        $view = $row[0];
        $sql .= "DROP VIEW IF EXISTS `{$view}`;\n";
        $createStmt = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(PDO::FETCH_ASSOC);
        $createSql  = $createStmt['Create View'] ?? '';
        $sql .= $createSql . ";\n\n";
    }

    $sql .= "COMMIT;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    return $sql;
}

/**
 * Recursively collect all physical files in the upload directory.
 */
function scanUploadFiles(string $uploadDir): array {
    $files = [];
    $uploadDir = rtrim(str_replace('\\', '/', $uploadDir), '/');

    if (!is_dir($uploadDir)) {
        return $files;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $absPath = str_replace('\\', '/', $item->getRealPath());
            $relPath = ltrim(str_replace($uploadDir, '', $absPath), '/');

            // Skip transient / lock files
            if (basename($absPath) === '.DS_Store' || str_ends_with($absPath, '.tmp')) {
                continue;
            }

            $size = (int)$item->getSize();
            $hash = hash_file('sha256', $absPath);

            $files[] = [
                'relative_path' => $relPath,
                'absolute_path' => $absPath,
                'size'          => $size,
                'sha256'        => $hash,
                'modified_at'   => date('Y-m-d H:i:s', $item->getMTime()),
            ];
        }
    }

    return $files;
}

/**
 * Gather live database table statistics (table names, row counts, data size).
 */
function getDatabaseStats(PDO $pdo): array {
    $stats = [];
    try {
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ");
        $stmt->execute([$dbName]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            // Get exact row count for small/medium DB
            $exactCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$r['TABLE_NAME']}`")->fetchColumn();
            $stats[$r['TABLE_NAME']] = [
                'rows'        => $exactCount,
                'data_bytes'  => (int)$r['DATA_LENGTH'],
                'index_bytes' => (int)$r['INDEX_LENGTH'],
            ];
        }
    } catch (Throwable $e) {}
    return $stats;
}

/**
 * Export complete system into a single standalone migration ZIP package.
 */
function exportSystemMigration(string $customDestination = ''): array {
    $db = getDB();
    $timestamp = date('Y-m-d_His');
    $packageFilename = "system-migration-{$timestamp}.zip";

    $tempDir = rtrim(sys_get_temp_dir(), '/\\') . '/neilos_export_' . uniqid();
    if (!mkdir($tempDir, 0777, true)) {
        throw new RuntimeException("Could not create temporary export directory: {$tempDir}");
    }

    $dbDir      = $tempDir . '/database';
    $storageDir = $tempDir . '/storage/uploads';
    $configDir  = $tempDir . '/config';

    mkdir($dbDir, 0777, true);
    mkdir($storageDir, 0777, true);
    mkdir($configDir, 0777, true);

    // 1. Export Database
    $sqlDump = generateDatabaseSqlDump($db);
    $dbSqlFile = $dbDir . '/database-backup.sql';
    file_put_contents($dbSqlFile, $sqlDump);
    $dbSqlHash = hash_file('sha256', $dbSqlFile);
    $dbSqlBytes = filesize($dbSqlFile);
    $dbStats = getDatabaseStats($db);

    // 2. Export Upload Storage
    $uploadFiles = scanUploadFiles(UPLOAD_DIR);
    $totalUploadBytes = 0;
    $manifestFiles = [];

    foreach ($uploadFiles as $file) {
        $destFile = $storageDir . '/' . $file['relative_path'];
        $destFileDir = dirname($destFile);
        if (!is_dir($destFileDir)) {
            mkdir($destFileDir, 0777, true);
        }
        copy($file['absolute_path'], $destFile);
        $totalUploadBytes += $file['size'];

        $manifestFiles[] = [
            'path'        => 'storage/uploads/' . $file['relative_path'],
            'upload_rel'  => $file['relative_path'],
            'size_bytes'  => $file['size'],
            'sha256'      => $file['sha256'],
            'modified_at' => $file['modified_at'],
        ];
    }

    // 3. Export Portable Config
    $companyInfo = $db->query("SELECT * FROM neilos_company_info WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    unset($companyInfo['id'], $companyInfo['created_at'], $companyInfo['updated_at']);

    $portableConfig = [
        'app_name'         => APP_NAME,
        'app_version'      => '2.0',
        'exported_at'      => date('Y-m-d H:i:s T'),
        'currency_rate'    => USD_TZS_RATE,
        'vat_rate'         => VAT_RATE,
        'financial_limits' => [
            'max_nrc' => MAX_NRC_AMOUNT,
            'max_mrc' => MAX_MRC_AMOUNT,
        ],
        'company_info'     => $companyInfo,
    ];
    file_put_contents($configDir . '/portable-config.json', json_encode($portableConfig, JSON_PRETTY_PRINT));

    // 4. Generate README-MIGRATION.md
    $readmeContent = "# Neilos Partner Portal — System Migration Package\n\n"
        . "Package Created: " . date('Y-m-d H:i:s T') . "\n"
        . "Source Host: " . php_uname('n') . "\n"
        . "Total Database Tables: " . count($dbStats) . "\n"
        . "Total Uploaded Files: " . count($uploadFiles) . " (" . round($totalUploadBytes / (1024 * 1024), 2) . " MB)\n\n"
        . "## RESTORATION INSTRUCTIONS\n\n"
        . "### Method 1: Web Interface (Recommended)\n"
        . "1. On Device B, open Neilos Partner Portal as Administrator.\n"
        . "2. Navigate to **System Settings > Backup & System Migration**.\n"
        . "3. Upload this `.zip` package and click **Import System**.\n"
        . "4. Review the integrity verification report.\n\n"
        . "### Method 2: Command Line (CLI)\n"
        . "```bash\n"
        . "php scripts/import-system.php --package={$packageFilename}\n"
        . "```\n\n"
        . "### Method 3: Manual Direct Restore\n"
        . "1. Restore the database:\n"
        . "   `mysql -u root -p neilos_portal < database/database-backup.sql`\n"
        . "2. Copy files from `storage/uploads/` to `public/uploads/` on Device B.\n";
    file_put_contents($tempDir . '/README-MIGRATION.md', $readmeContent);

    // 5. Generate Manifest
    $manifest = [
        'application'          => 'Neilos Partner Portal',
        'backup_version'       => '2.0',
        'export_format'        => 'neilos-migration-v2',
        'created_at'           => date('Y-m-d H:i:s T'),
        'created_timestamp'    => time(),
        'source_host'          => php_uname('n'),
        'source_os'            => PHP_OS,
        'php_version'          => PHP_VERSION,
        'database'             => [
            'engine'       => 'MariaDB / MySQL',
            'dump_file'    => 'database/database-backup.sql',
            'size_bytes'   => $dbSqlBytes,
            'sha256'       => $dbSqlHash,
            'table_count'  => count($dbStats),
            'tables'       => $dbStats,
        ],
        'storage'              => [
            'upload_directory' => 'storage/uploads',
            'file_count'       => count($uploadFiles),
            'total_bytes'      => $totalUploadBytes,
            'files'            => $manifestFiles,
        ],
        'portable_config'      => 'config/portable-config.json',
        'checksum_algorithm'   => 'sha256',
    ];
    file_put_contents($tempDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

    // 6. Create ZIP Archive
    $finalZipPath = !empty($customDestination) ? $customDestination : (PUBLIC_DIR . '/uploads/' . $packageFilename);
    $finalZipDir  = dirname($finalZipPath);
    if (!is_dir($finalZipDir)) {
        mkdir($finalZipDir, 0777, true);
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create ZIP file at {$finalZipPath}");
        }

        $allFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($allFiles as $file) {
            $abs = str_replace('\\', '/', $file->getRealPath());
            $rel = ltrim(str_replace(str_replace('\\', '/', $tempDir), '', $abs), '/');
            if ($file->isDir()) {
                $zip->addEmptyDir($rel);
            } elseif ($file->isFile()) {
                $zip->addFile($abs, $rel);
            }
        }
        $zip->close();
    } else {
        throw new RuntimeException("PHP ZipArchive extension is required for migration export.");
    }

    // Clean up temporary export folder
    $cleanupIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($cleanupIterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }
    rmdir($tempDir);

    return [
        'success'          => true,
        'filename'         => $packageFilename,
        'zip_path'         => $finalZipPath,
        'size_bytes'       => filesize($finalZipPath),
        'table_count'      => count($dbStats),
        'file_count'       => count($uploadFiles),
        'total_upload_mb'  => round($totalUploadBytes / (1024 * 1024), 2),
        'manifest'         => $manifest,
    ];
}

/**
 * Validate a migration package before importing.
 */
function validateMigrationPackage(string $zipPath): array {
    if (!file_exists($zipPath) || !is_readable($zipPath)) {
        return ['valid' => false, 'error' => 'Migration package file is not accessible or does not exist.'];
    }

    if (!class_exists('ZipArchive')) {
        return ['valid' => false, 'error' => 'PHP ZipArchive extension is not enabled.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['valid' => false, 'error' => 'Unable to open ZIP archive. File may be corrupted.'];
    }

    // Check manifest.json
    $manifestJson = $zip->getFromName('manifest.json');
    if ($manifestJson === false) {
        $zip->close();
        return ['valid' => false, 'error' => 'Invalid migration package: manifest.json is missing.'];
    }

    $manifest = json_decode($manifestJson, true);
    if (!is_array($manifest) || empty($manifest['database']['dump_file'])) {
        $zip->close();
        return ['valid' => false, 'error' => 'Invalid or unreadable manifest.json structure.'];
    }

    // Check database dump
    $dbDumpName = $manifest['database']['dump_file'];
    $dbStat = $zip->statName($dbDumpName);
    if ($dbStat === false || $dbStat['size'] === 0) {
        $zip->close();
        return ['valid' => false, 'error' => "Database snapshot '{$dbDumpName}' is missing or empty inside package."];
    }

    $zip->close();

    return [
        'valid'        => true,
        'manifest'     => $manifest,
        'table_count'  => $manifest['database']['table_count'] ?? count($manifest['database']['tables'] ?? []),
        'file_count'   => $manifest['storage']['file_count'] ?? 0,
        'total_bytes'  => $manifest['storage']['total_bytes'] ?? 0,
        'created_at'   => $manifest['created_at'] ?? 'Unknown',
        'source_host'  => $manifest['source_host'] ?? 'Unknown',
    ];
}

/**
 * Restore complete system from a valid migration package.
 */
function importSystemMigration(string $zipPath, array $options = []): array {
    $validation = validateMigrationPackage($zipPath);
    if (!$validation['valid']) {
        throw new RuntimeException($validation['error']);
    }

    $manifest = $validation['manifest'];
    $tempDir  = rtrim(sys_get_temp_dir(), '/\\') . '/neilos_import_' . uniqid();
    if (!mkdir($tempDir, 0777, true)) {
        throw new RuntimeException("Could not create temporary import directory: {$tempDir}");
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException("Could not extract migration package.");
    }
    $zip->extractTo($tempDir);
    $zip->close();

    $db = getDB();
    $restoredTables = 0;
    $restoredFiles  = 0;
    $fileErrors     = [];

    // 1. Verify and Restore Database
    $dbFile = $tempDir . '/' . ($manifest['database']['dump_file'] ?? 'database/database-backup.sql');
    if (!file_exists($dbFile)) {
        throw new RuntimeException("Database SQL dump file not found in extracted package.");
    }

    // Optional Checksum verification
    if (!empty($manifest['database']['sha256'])) {
        $actualDbHash = hash_file('sha256', $dbFile);
        if ($actualDbHash !== $manifest['database']['sha256']) {
            throw new RuntimeException("Database integrity check failed: SHA-256 mismatch.");
        }
    }

    $sqlContent = file_get_contents($dbFile);
    if (empty($sqlContent)) {
        throw new RuntimeException("Database dump is empty.");
    }

    // Execute database restoration via PDO
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec($sqlContent);
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    $restoredTables = $manifest['database']['table_count'] ?? count($manifest['database']['tables'] ?? []);

    // 2. Restore Upload Storage Directory
    $storageSrcDir = $tempDir . '/storage/uploads';
    if (is_dir($storageSrcDir)) {
        $targetUploadDir = rtrim(str_replace('\\', '/', UPLOAD_DIR), '/');
        if (!is_dir($targetUploadDir)) {
            mkdir($targetUploadDir, 0777, true);
        }

        $filesToRestore = $manifest['storage']['files'] ?? [];
        foreach ($filesToRestore as $fileMeta) {
            $srcPath  = $tempDir . '/' . $fileMeta['path'];
            $destPath = $targetUploadDir . '/' . $fileMeta['upload_rel'];

            if (file_exists($srcPath)) {
                // Verify checksum if available
                if (!empty($fileMeta['sha256'])) {
                    $fileHash = hash_file('sha256', $srcPath);
                    if ($fileHash !== $fileMeta['sha256']) {
                        $fileErrors[] = "Checksum mismatch on {$fileMeta['upload_rel']}";
                    }
                }

                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }

                if (copy($srcPath, $destPath)) {
                    $restoredFiles++;
                } else {
                    $fileErrors[] = "Failed to write file to {$destPath}";
                }
            } else {
                $fileErrors[] = "Missing bundled file: {$fileMeta['path']}";
            }
        }
    }

    // 3. Restore Portable Configuration into Database
    $configFile = $tempDir . '/config/portable-config.json';
    if (file_exists($configFile)) {
        $pConfig = json_decode(file_get_contents($configFile), true);
        if (!empty($pConfig['company_info']) && is_array($pConfig['company_info'])) {
            $ci = $pConfig['company_info'];
            $ciCols = [];
            $ciVals = [];
            foreach ($ci as $col => $val) {
                $ciCols[] = "`{$col}`";
                $ciVals[] = $val;
            }
            if (!empty($ciCols)) {
                $placeholders = implode(', ', array_fill(0, count($ciVals), '?'));
                $updates = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $ciCols));
                $colStr = implode(', ', $ciCols);
                try {
                    $upsertSql = "INSERT INTO neilos_company_info (id, {$colStr}) VALUES (1, {$placeholders}) ON DUPLICATE KEY UPDATE {$updates}";
                    $db->prepare($upsertSql)->execute($ciVals);
                } catch (Throwable $e) {}
            }
        }
    }

    // 4. Run Auto-healing & Schema Sync
    try {
        ensurePermissionsSchema($db);
    } catch (Throwable $e) {}

    // Clean up temporary import folder
    $cleanupIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($cleanupIterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
        }
    }
    rmdir($tempDir);

    // 5. Run Post-Migration Integrity Verification
    $integrityReport = verifySystemIntegrity();

    return [
        'success'          => true,
        'message'          => 'System migration restored successfully.',
        'restored_tables'  => $restoredTables,
        'restored_files'   => $restoredFiles,
        'file_errors'      => $fileErrors,
        'manifest'         => $manifest,
        'integrity'        => $integrityReport,
    ];
}

/**
 * Live System Integrity & Storage Health Audit.
 */
function verifySystemIntegrity(): array {
    $db = getDB();
    $report = [
        'healthy'         => true,
        'checked_at'      => date('Y-m-d H:i:s T'),
        'database'        => [],
        'storage'         => [],
        'records'         => [],
        'warnings'        => [],
        'errors'          => [],
    ];

    // 1. Check Database connection and tables
    try {
        $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
        $dbVer  = $db->query("SELECT VERSION()")->fetchColumn();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $report['database'] = [
            'connected'   => true,
            'database'    => $dbName,
            'version'     => $dbVer,
            'table_count' => count($tables),
        ];

        // Key entity counts
        $keyTables = [
            'users'                     => 'Users',
            'partners'                  => 'Partners',
            'orders'                    => 'Orders',
            'active_services'           => 'Active Services',
            'order_documents'           => 'Order Documents',
            'contractor_evidence'       => 'Contractor Evidence',
            'partner_kyc_applications'  => 'KYC Applications',
            'activity_logs'             => 'Activity Logs',
        ];

        foreach ($keyTables as $t => $label) {
            if (in_array($t, $tables)) {
                $count = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                $report['records'][$t] = [
                    'label' => $label,
                    'count' => $count,
                ];
            } else {
                $report['records'][$t] = [
                    'label' => $label,
                    'count' => 0,
                ];
                $report['warnings'][] = "Table `{$t}` is missing.";
            }
        }
    } catch (Throwable $e) {
        $report['healthy'] = false;
        $report['errors'][] = "Database connectivity error: " . $e->getMessage();
        return $report;
    }

    // 2. Check Physical Upload Storage
    $uploadDir = UPLOAD_DIR;
    $isDir = is_dir($uploadDir);
    $isWritable = is_writable($uploadDir);
    $files = scanUploadFiles($uploadDir);
    $totalSize = array_sum(array_column($files, 'size'));

    $report['storage'] = [
        'upload_dir'  => $uploadDir,
        'exists'      => $isDir,
        'writable'    => $isWritable,
        'file_count'  => count($files),
        'total_bytes' => $totalSize,
        'total_mb'    => round($totalSize / (1024 * 1024), 2),
    ];

    if (!$isDir) {
        $report['healthy'] = false;
        $report['errors'][] = "Upload storage directory does not exist at {$uploadDir}";
    } elseif (!$isWritable) {
        $report['warnings'][] = "Upload directory is not writable. File uploads may fail.";
    }

    // 3. Database to Physical File Link Audit
    $fileChecks = [
        'order_documents' => [
            'query' => "SELECT id, file_path, original_filename FROM order_documents",
            'path_col' => 'file_path',
        ],
        'contractor_evidence' => [
            'query' => "SELECT id, file_path, original_filename FROM contractor_evidence",
            'path_col' => 'file_path',
        ],
    ];

    $missingPhysicalFiles = [];
    foreach ($fileChecks as $src => $meta) {
        try {
            $rows = $db->query($meta['query'])->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $path = $r[$meta['path_col']] ?? '';
                if (!empty($path)) {
                    // Check standard locations (relative to public/uploads or public/)
                    $fullPath1 = PUBLIC_DIR . '/' . ltrim($path, '/');
                    $fullPath2 = UPLOAD_DIR . ltrim($path, '/');
                    if (!file_exists($fullPath1) && !file_exists($fullPath2) && !file_exists($path)) {
                        $missingPhysicalFiles[] = "[{$src} #{$r['id']}] {$path}";
                    }
                }
            }
        } catch (Throwable $e) {}
    }

    if (!empty($missingPhysicalFiles)) {
        $countMissing = count($missingPhysicalFiles);
        $sample = array_slice($missingPhysicalFiles, 0, 5);
        $report['warnings'][] = "Found {$countMissing} database file records with missing files on disk. (e.g. " . implode(', ', $sample) . ")";
    }

    return $report;
}
