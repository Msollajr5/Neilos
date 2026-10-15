<?php
// ============================================================
// Neilos Partner Portal — CLI System Export Tool
// Usage: php scripts/export-system.php [--output=custom_package_name.zip]
// ============================================================

define('CLI_MODE', true);
require_once __DIR__ . '/../public/index.php';

echo "============================================================\n";
echo "Neilos Partner Portal — System Migration Package Exporter\n";
echo "============================================================\n\n";

$options = getopt('', ['output::']);
$customOutput = $options['output'] ?? '';

try {
    echo "[*] Scanning active database and generating full SQL dump...\n";
    $result = exportSystemMigration($customOutput);

    echo "\n[+] SUCCESS! Migration package created successfully.\n";
    echo "------------------------------------------------------------\n";
    echo "  Package File:      " . $result['filename'] . "\n";
    echo "  Location:          " . $result['zip_path'] . "\n";
    echo "  Package Size:      " . round($result['size_bytes'] / (1024 * 1024), 2) . " MB\n";
    echo "  Database Tables:   " . $result['table_count'] . " tables\n";
    echo "  Uploaded Files:    " . $result['file_count'] . " files (" . $result['total_upload_mb'] . " MB)\n";
    echo "  Generated At:      " . $result['manifest']['created_at'] . "\n";
    echo "------------------------------------------------------------\n";
    echo "You can now copy this file to Device B and run:\n";
    echo "  php scripts/import-system.php --package={$result['filename']}\n\n";
    exit(0);
} catch (Throwable $e) {
    echo "\n[-] ERROR: Failed to export system migration package.\n";
    echo "Details: " . $e->getMessage() . "\n\n";
    exit(1);
}
