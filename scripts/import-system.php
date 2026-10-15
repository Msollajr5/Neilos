<?php
// ============================================================
// Neilos Partner Portal — CLI System Import & Restoration Tool
// Usage: php scripts/import-system.php --package=path/to/system-migration-*.zip [--yes]
// ============================================================

define('CLI_MODE', true);
require_once __DIR__ . '/../public/index.php';

echo "============================================================\n";
echo "Neilos Partner Portal — System Migration Package Importer\n";
echo "============================================================\n\n";

$options = getopt('', ['package:', 'yes::']);
$packagePath = $options['package'] ?? '';
$autoConfirm = isset($options['yes']);

if (empty($packagePath)) {
    echo "[-] ERROR: Missing required --package parameter.\n";
    echo "Usage: php scripts/import-system.php --package=system-migration-YYYY-MM-DD.zip [--yes]\n\n";
    exit(1);
}

// Resolve relative or absolute path
if (!file_exists($packagePath)) {
    $altPath = ROOT_DIR . '/' . ltrim($packagePath, '/\\');
    if (file_exists($altPath)) {
        $packagePath = $altPath;
    } else {
        $uploadAlt = UPLOAD_DIR . ltrim($packagePath, '/\\');
        if (file_exists($uploadAlt)) {
            $packagePath = $uploadAlt;
        }
    }
}

if (!file_exists($packagePath)) {
    echo "[-] ERROR: File not found: {$packagePath}\n\n";
    exit(1);
}

echo "[*] Validating migration package...\n";
$validation = validateMigrationPackage($packagePath);

if (!$validation['valid']) {
    echo "[-] VALIDATION FAILED: {$validation['error']}\n\n";
    exit(1);
}

$manifest = $validation['manifest'];
echo "[+] Package is valid.\n";
echo "------------------------------------------------------------\n";
echo "  Source Host:     " . ($manifest['source_host'] ?? 'Unknown') . "\n";
echo "  Created Date:    " . ($manifest['created_at'] ?? 'Unknown') . "\n";
echo "  Tables to Dump:  " . $validation['table_count'] . " tables\n";
echo "  Files to Unpack: " . $validation['file_count'] . " files (" . round($validation['total_bytes'] / (1024 * 1024), 2) . " MB)\n";
echo "------------------------------------------------------------\n\n";

if (!$autoConfirm) {
    echo "WARNING: Restoring will overwrite existing database records with this backup.\n";
    echo "Type 'YES' to proceed with system restore: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if (strtoupper($line) !== 'YES') {
        echo "\n[*] Operation cancelled by user.\n\n";
        exit(0);
    }
}

try {
    echo "\n[*] Restoring database snapshot and synchronizing upload directory...\n";
    $result = importSystemMigration($packagePath);

    echo "\n[+] SUCCESS! System restored successfully.\n";
    echo "------------------------------------------------------------\n";
    echo "  Restored Tables:  " . $result['restored_tables'] . "\n";
    echo "  Restored Files:   " . $result['restored_files'] . "\n";
    echo "  Integrity Status: " . ($result['integrity']['healthy'] ? '100% HEALTHY' : 'WARNINGS DETECTED') . "\n";
    echo "------------------------------------------------------------\n";

    if (!empty($result['file_errors'])) {
        echo "\nFile Notes:\n";
        foreach ($result['file_errors'] as $err) {
            echo "  - {$err}\n";
        }
    }

    echo "\n[+] Portal is ready to use! Access via " . APP_URL . "\n\n";
    exit(0);
} catch (Throwable $e) {
    echo "\n[-] ERROR: System restoration failed.\n";
    echo "Details: " . $e->getMessage() . "\n\n";
    exit(1);
}
