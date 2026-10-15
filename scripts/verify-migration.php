<?php
// ============================================================
// Neilos Partner Portal — CLI System Health & Integrity Auditor
// Usage: php scripts/verify-migration.php
// ============================================================

define('CLI_MODE', true);
require_once __DIR__ . '/../public/index.php';

echo "============================================================\n";
echo "Neilos Partner Portal — System & Storage Integrity Audit\n";
echo "============================================================\n\n";

$report = verifySystemIntegrity();

echo "[*] Database Health:\n";
echo "  - Status:        " . ($report['database']['connected'] ? 'CONNECTED' : 'DISCONNECTED') . "\n";
echo "  - Database:      " . ($report['database']['database'] ?? 'N/A') . "\n";
echo "  - Version:       " . ($report['database']['version'] ?? 'N/A') . "\n";
echo "  - Total Tables:  " . ($report['database']['table_count'] ?? 0) . "\n\n";

echo "[*] Entity Record Counts:\n";
foreach ($report['records'] as $tbl => $info) {
    printf("  - %-25s: %d\n", $info['label'], $info['count']);
}

echo "\n[*] Physical Upload Storage:\n";
echo "  - Path:          " . $report['storage']['upload_dir'] . "\n";
echo "  - Exists:        " . ($report['storage']['exists'] ? 'YES' : 'NO') . "\n";
echo "  - Writable:      " . ($report['storage']['writable'] ? 'YES' : 'NO') . "\n";
echo "  - Total Files:   " . $report['storage']['file_count'] . " (" . $report['storage']['total_mb'] . " MB)\n\n";

if (!empty($report['warnings'])) {
    echo "[!] Warnings:\n";
    foreach ($report['warnings'] as $w) {
        echo "  - " . $w . "\n";
    }
    echo "\n";
}

if (!empty($report['errors'])) {
    echo "[-] Errors:\n";
    foreach ($report['errors'] as $err) {
        echo "  - " . $err . "\n";
    }
    echo "\n";
    echo "[-] AUDIT STATUS: FAILED\n\n";
    exit(1);
}

echo "[+] AUDIT STATUS: " . ($report['healthy'] ? "100% HEALTHY & VERIFIED" : "COMPLETED WITH WARNINGS") . "\n\n";
exit(0);
