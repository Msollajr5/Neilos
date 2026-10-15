<?php
// ============================================================
// Neilos Partner Portal — Format & Upload Helpers
// ============================================================

/**
 * Format a number with commas and 2 decimal places.
 */
function money(float $amount, string $currency = ''): string {
    $formatted = number_format($amount, 2, '.', ',');
    return $currency ? "$currency $formatted" : $formatted;
}

/**
 * Format a numeric amount as TZS with thousands separator.
 * e.g. 240000 → "TZS 240,000"
 * Handles null gracefully with fallback.
 */
function formatTZS(?float $amount, bool $showSymbol = true, string $fallback = '—'): string {
    if ($amount === null) {
        return $fallback;
    }
    $formatted = number_format($amount, 0, '.', ',');
    return $showSymbol ? "TZS $formatted" : $formatted;
}

/**
 * Parse and validate a TZS amount input string.
 * Normalizes user input (strips "TZS", commas, spaces).
 * Strictly validates:
 *  - Non-empty when not nullable (blocks blank approval bypass)
 *  - Non-negative (blocks negative inputs)
 *  - Valid numeric format (rejects alphabetic / malformed text)
 *  - Non-zero unless allowZero is true (preserves distinction between blank & zero)
 *  - Configurable maximum threshold (blocks unreasonable values)
 * 
 * Returns clean numeric float or null.
 * Throws RuntimeException with clear error message on validation failure.
 */
function parseTZSInput(
    ?string $raw,
    bool $nullable = false,
    bool $allowZero = false,
    ?float $max = null,
    string $fieldLabel = 'Amount'
): ?float {
    if ($raw === null) {
        if ($nullable) return null;
        throw new RuntimeException("$fieldLabel is required.");
    }

    $trimmed = trim($raw);
    if ($trimmed === '') {
        if ($nullable) return null;
        throw new RuntimeException("$fieldLabel is required.");
    }

    // Check for negative sign upfront
    if (str_contains($trimmed, '-')) {
        throw new RuntimeException("$fieldLabel cannot be negative.");
    }

    // Strip currency symbols, spaces, commas, and formatting characters
    $clean = str_ireplace(['TZS', 'USD', '$', ',', ' ', "\xc2\xa0", "\t", "\n", "\r"], '', $trimmed);

    // Validate that cleaned string is strictly numeric digits (with optional 1-2 decimal places)
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
        throw new RuntimeException("$fieldLabel must be a valid numeric amount.");
    }

    $val = (float)$clean;

    if ($val < 0) {
        throw new RuntimeException("$fieldLabel cannot be negative.");
    }

    if (!$allowZero && $val == 0.0) {
        throw new RuntimeException("$fieldLabel cannot be zero.");
    }

    if ($max !== null && $val > $max) {
        throw new RuntimeException("$fieldLabel exceeds maximum allowed limit of " . formatTZS($max) . ".");
    }

    return $val;
}

/**
 * Generate an SO-prefixed Service Order Form number.
 * Format: SO-YYMMDD-NNN (auto-increments within the same day).
 */
function generateSofNumber(int $orderId = 0): string {
    $ymd  = date('ymd');
    $base = 'SO-' . $ymd . '-';
    try {
        $db = getDB();
        // Ensure the row has an SO number stored before generating a new one
        if ($orderId) {
            $existing = $db->prepare("SELECT sof_number FROM orders WHERE id = ?");
            $existing->execute([$orderId]);
            $sofNum = $existing->fetchColumn();
            if ($sofNum) return $sofNum;
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE sof_number LIKE ?");
        $stmt->execute([$base . '%']);
        $count = (int)$stmt->fetchColumn();
        return $base . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $base . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}

/**
 * Format bytes to human-readable size.
 */
function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

/**
 * Return a CSS class for order status badge.
 */
function orderStatusClass(string $status): string {
    return match($status) {
        // v1.0 lifecycle
        'Feasibility Review'          => 'badge-warning',
        'Await Commercial Approval'   => 'badge-warning',
        'Management Approval'         => 'badge-danger',
        'Pending SOF'                 => 'badge-info',
        'SOF Review'                  => 'badge-primary',
        'Installation'                => 'badge-primary',
        'Testing'                     => 'badge-primary',
        'UAT'                         => 'badge-info',
        'Closed'                      => 'badge-success',
        'Not Feasible'                => 'badge-danger',
        'Cancelled'                   => 'badge-danger',
        // Legacy statuses (kept for data continuity)
        'Submitted'                   => 'badge-warning',
        'Awaiting BSA Approval'       => 'badge-warning',
        'Awaiting Commercial Approval'=> 'badge-warning',
        'Awaiting Management Approval'=> 'badge-danger',
        'Approved'                    => 'badge-primary',
        'Provisioning'                => 'badge-primary',
        'UAT - Awaiting Confirmation' => 'badge-info',
        'Activated'                   => 'badge-success',
        'Billing Triggered'           => 'badge-success',
        default                       => 'badge-secondary',
    };
}

/**
 * Return a human-readable label and icon class for order status.
 */
function orderStatusIcon(string $status): string {
    return match($status) {
        'Feasibility Review'         => 'search',
        'Await Commercial Approval'  => 'dollar',
        'Management Approval'        => 'users',
        'Pending SOF'                => 'document',
        'SOF Review'                 => 'edit',
        'Installation'               => 'project',
        'Testing'                    => 'check',
        'UAT'                        => 'check',
        'Closed'                     => 'server',
        'Not Feasible'               => 'x',
        'Cancelled'                  => 'x',
        default                      => 'info',
    };
}

/**
 * Get short code for a service type (e.g. FTTH -> FTH, FTTB -> FTB, DIA -> DIA, Layer 2 -> L2, BIA -> BIA, Remote Hands -> RHO).
 */
function getServiceTypeShortCode(string $serviceType, ?PDO $db = null): string {
    $clean = trim($serviceType);
    if (!$clean) return 'GEN';

    if ($db) {
        try {
            $stmt = $db->prepare("SELECT short_code FROM service_types WHERE name = ? OR code = ? LIMIT 1");
            $stmt->execute([$clean, $clean]);
            $sc = $stmt->fetchColumn();
            if ($sc && trim($sc) !== '') return strtoupper(trim($sc));
        } catch (Throwable $e) {}
    }

    $upper = strtoupper($clean);
    if (str_contains($upper, 'FTTH')) return 'FTH';
    if (str_contains($upper, 'FTTB')) return 'FTB';
    if (str_contains($upper, 'FTTE')) return 'FTE';
    if (str_contains($upper, 'DIA')) return 'DIA';
    if (str_contains($upper, 'LAYER 2') || str_contains($upper, 'LAYER2') || str_contains($upper, 'L2')) return 'L2';
    if (str_contains($upper, 'BIA') || str_contains($upper, 'BROADBAND')) return 'BIA';
    if (str_contains($upper, 'REMOTE HANDS') || str_contains($upper, 'RHO')) return 'RHO';

    $letters = preg_replace('/[^A-Z0-9]/', '', $upper);
    return substr($letters ?: 'SVC', 0, 3);
}

/**
 * Get 3-letter short code for a partner (e.g. Sprint Tanzania -> SPT, Seacom -> SEA, SimbaNet -> SIM, Flashnet -> FLA).
 */
function getPartnerShortCode($partner, ?PDO $db = null): string {
    if (is_numeric($partner) && (int)$partner > 0 && $db) {
        try {
            $stmt = $db->prepare("SELECT name FROM partners WHERE id = ?");
            $stmt->execute([(int)$partner]);
            $pName = $stmt->fetchColumn();
            if ($pName) $partner = $pName;
        } catch (Throwable $e) {}
    }

    $name = is_string($partner) ? trim($partner) : '';
    if (!$name) return 'NEL';

    $upper = strtoupper($name);
    // Canonical 3-letter codes for known partners
    if (str_contains($upper, 'SPRINT')) {
        return 'SPT';
    }
    if (str_contains($upper, 'SEACOM')) {
        return 'SEA';
    }
    if (str_contains($upper, 'FLASHNET')) {
        return 'FLA';
    }
    if (str_contains($upper, 'ACME')) {
        return 'ACM';
    }
    if (str_contains($upper, 'SIMBANET')) {
        return 'SIM';
    }
    if (str_contains($upper, 'HALOTEL')) {
        return 'HAL';
    }
    if (str_contains($upper, 'TIGO') || str_contains($upper, 'MIC TANZANIA') || str_contains($upper, 'HONORA')) {
        return 'TIG';
    }
    if (str_contains($upper, 'VODACOM')) {
        return 'VOD';
    }
    if (str_contains($upper, 'AIRTEL')) {
        return 'AIR';
    }
    if (str_contains($upper, 'TTCL')) {
        return 'TTC';
    }
    if (str_contains($upper, 'ZANTEL')) {
        return 'ZAN';
    }

    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    return strtoupper(substr($clean ?: 'NEL', 0, 3));
}

/**
 * Generate an order number according to criteria:
 * [PARTNER_CODE]-[SERVICE_CODE]-[YYMMDD]-[0001]
 * e.g., SEA-FTH-260917-0001
 */
function generateOrderNumber($partner = null, ?string $serviceType = null): string {
    $ymd = date('ymd');
    try {
        $db = getDB();
        $pCode = getPartnerShortCode($partner, $db);
        $sCode = getServiceTypeShortCode($serviceType ?: 'FTTH', $db);

        $prefix = "{$pCode}-{$sCode}-{$ymd}-";
        
        $stmt = $db->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 100");
        $stmt->execute(["%{$ymd}-%"]);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxSeq = 0;
        foreach ($existing as $ordNum) {
            $parts = explode('-', $ordNum);
            $seqPart = end($parts);
            if (is_numeric($seqPart)) {
                $maxSeq = max($maxSeq, (int)$seqPart);
            }
        }

        $nextSeq = $maxSeq + 1;
        do {
            $candidate = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_number = ?");
            $checkStmt->execute([$candidate]);
            $exists = (int)$checkStmt->fetchColumn();
            if ($exists > 0) {
                $nextSeq++;
            }
        } while ($exists > 0);

        return $candidate;
    } catch (Exception $e) {
        $p = is_string($partner) ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $partner), 0, 3)) : 'NEL';
        $s = $serviceType ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $serviceType), 0, 3)) : 'FTH';
        return "{$p}-{$s}-{$ymd}-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}



/**
 * Relative time string, e.g. "2 hours ago".
 */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' day(s) ago';
}

/**
 * Format a datetime string for display.
 */
function fmtDate(string|null $dt, string $format = 'd M Y'): string {
    if (!$dt) return '—';
    return date($format, strtotime($dt));
}

function fmtDateTime(string|null $dt): string {
    return fmtDate($dt, 'd M Y H:i');
}

/**
 * Flash message helpers (stored in session).
 */
function setFlash(string $type, string $message): void {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): array|null {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize string for safe HTML output.
 */
function e(string|null $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * CSRF token generation and validation.
 */
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ============================================================
// Upload Helper
// ============================================================

/**
 * Handle a file upload, validate, and return stored path info.
 * Returns ['path' => string, 'name' => string, 'size' => int] or throws RuntimeException.
 */
function uploadFile(array $file, string $subdir = 'general'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error: ' . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('File is too large. Maximum allowed size is ' . formatBytes(MAX_FILE_SIZE) . '.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new RuntimeException('File type ".' . e($ext) . '" is not allowed.');
    }

    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $fullPath   = $dir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }

    return [
        'path' => 'uploads/' . $subdir . '/' . $storedName,
        'name' => $file['name'],
        'size' => $file['size'],
    ];
}

/**
 * Ensure service_types and service_pricing_packages tables exist and are seeded.
 */
function ensurePricingAndServiceTypesSchema(?PDO $db = null): void {
    static $initialized = false;
    if ($initialized) return;
    try {
        if (!$db) $db = getDB();

        // 1. Table: service_types
        $db->exec("CREATE TABLE IF NOT EXISTS service_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            pricing_model VARCHAR(50) NOT NULL DEFAULT 'package',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            display_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Table: service_pricing_packages
        $db->exec("CREATE TABLE IF NOT EXISTS service_pricing_packages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            service_type VARCHAR(50) NOT NULL,
            package_code VARCHAR(100) NOT NULL,
            package_name VARCHAR(150) NOT NULL,
            speed_mbps INT NULL,
            mrc_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            nrc_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'TZS',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            display_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_svc (service_type),
            INDEX idx_pkg (package_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Seed default service types if table is empty
        $stCount = (int)$db->query("SELECT COUNT(*) FROM service_types")->fetchColumn();
        if ($stCount === 0) {
            $defaultST = [
                ['FTTH', 'FTTH', 'Fibre to the Home (Shared Broadband)', 'package', 1, 1],
                ['FTTB', 'FTTB', 'Fibre to the Building (Shared Business Broadband)', 'package', 1, 2],
                ['Layer 2 ( last mile)', 'Layer 2 ( last mile)', 'Dedicated Layer 2 Ethernet Last Mile', 'capacity', 1, 3],
                ['BIA (Broadband Internet Access)', 'BIA (Broadband Internet Access)', 'Broadband Internet Access', 'custom', 1, 4],
                ['DIA', 'DIA', 'Dedicated Internet Access (Dedicated Bandwidth)', 'custom', 1, 5],
                ['Remote Hands Only', 'Remote Hands Only', 'Remote Hands On-Site Technical Assistance', 'fixed', 1, 6],
            ];
            $insST = $db->prepare("INSERT INTO service_types (code, name, description, pricing_model, is_active, display_order) VALUES (?,?,?,?,?,?)");
            foreach ($defaultST as $row) {
                $insST->execute($row);
            }
        }

        // Seed default pricing packages if table is empty
        $pkgCount = (int)$db->query("SELECT COUNT(*) FROM service_pricing_packages")->fetchColumn();
        if ($pkgCount === 0) {
            $defaultPkgs = [
                // FTTH Retail Service packages (USD)
                ['FTTH', 'FTTH-20', '20 Mbps', 20, 10.40, 60.00, 'USD', 1, 1],
                ['FTTH', 'FTTH-30', '30 Mbps', 30, 12.48, 60.00, 'USD', 1, 2],
                ['FTTH', 'FTTH-40', '40 Mbps', 40, 13.52, 60.00, 'USD', 1, 3],
                ['FTTH', 'FTTH-50', '50 Mbps', 50, 16.22, 60.00, 'USD', 1, 4],
                ['FTTH', 'FTTH-60', '60 Mbps', 60, 19.47, 60.00, 'USD', 1, 5],
                ['FTTH', 'FTTH-80', '80 Mbps', 80, 23.36, 60.00, 'USD', 1, 6],
                ['FTTH', 'FTTH-100', '100 Mbps', 100, 28.04, 60.00, 'USD', 1, 7],

                // FTTB packages (TZS)
                ['FTTB', 'FTTB-30', '30 Mbps (FTTB)', 30, 99000.00, 140000.00, 'TZS', 1, 10],
                ['FTTB', 'FTTB-40', '40 Mbps (FTTB)', 40, 27500.00, 140000.00, 'TZS', 1, 11],
                ['FTTB', 'FTTB-50', '50 Mbps (FTTB)', 50, 33000.00, 140000.00, 'TZS', 1, 12],
                ['FTTB', 'FTTB-60', '60 Mbps (FTTB)', 60, 39500.00, 140000.00, 'TZS', 1, 13],
                ['FTTB', 'FTTB-70', '70 Mbps (FTTB)', 70, 46000.00, 140000.00, 'TZS', 1, 14],
                ['FTTB', 'FTTB-80', '80 Mbps (FTTB)', 80, 52500.00, 140000.00, 'TZS', 1, 15],
                ['FTTB', 'FTTB-90', '90 Mbps (FTTB)', 90, 59000.00, 140000.00, 'TZS', 1, 16],
                ['FTTB', 'FTTB-100', '100 Mbps (FTTB)', 100, 65500.00, 140000.00, 'TZS', 1, 17],

                // Layer 2 packages
                ['Layer 2 ( last mile)', 'L2-100', '<= 100 Mbps', 100, 110000.00, 250000.00, 'TZS', 1, 18],
                ['Layer 2 ( last mile)', 'L2-1000', '> 100 Mbps', 1000, 220000.00, 250000.00, 'TZS', 1, 19],
            ];
            $insPkg = $db->prepare("INSERT INTO service_pricing_packages (service_type, package_code, package_name, speed_mbps, mrc_amount, nrc_amount, currency, is_active, display_order) VALUES (?,?,?,?,?,?,?,?,?)");
            foreach ($defaultPkgs as $pkg) {
                $insPkg->execute($pkg);
            }
        }

        $initialized = true;
    } catch (Throwable $e) {}
}

/**
 * Retrieve list of active service types from DB (with fallback).
 */
function getActiveServiceTypes(?PDO $db = null): array {
    ensurePricingAndServiceTypesSchema($db);
    try {
        if (!$db) $db = getDB();
        $stmt = $db->query("SELECT name FROM service_types WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($results)) {
            return $results;
        }
    } catch (Throwable $e) {}
    return ['Layer 2 ( last mile)', 'FTTH', 'FTTB', 'BIA (Broadband Internet Access)', 'DIA', 'Remote Hands Only'];
}

/**
 * Retrieve active pricing packages for a service type or all service types.
 */
function getActivePricingPackages(?string $serviceType = null, ?PDO $db = null): array {
    ensurePricingAndServiceTypesSchema($db);
    try {
        if (!$db) $db = getDB();
        if ($serviceType) {
            $stmt = $db->prepare("SELECT * FROM service_pricing_packages WHERE (service_type = ? OR service_type LIKE ?) AND is_active = 1 ORDER BY display_order ASC, speed_mbps ASC, id ASC");
            $stmt->execute([$serviceType, "%$serviceType%"]);
        } else {
            $stmt = $db->query("SELECT * FROM service_pricing_packages WHERE is_active = 1 ORDER BY service_type ASC, display_order ASC, speed_mbps ASC, id ASC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Compute FTTx pricing (MRC, Standard NRC, Currency).
 * Dynamically queries service_pricing_packages from the database, falling back gracefully.
 * - FTTH Retail Service: USD packages (e.g. 20M: $10.40, 30M: $12.48, 50M: $16.22, NRC: $60)
 * - FTTB: TZS packages
 */
function getFTTxPricing(string $package, string $serviceType = 'FTTH'): array {
    $isFTTB = (stripos($serviceType, 'FTTB') !== false) || (stripos($package, 'FTTB') !== false);
    $targetServiceType = $isFTTB ? 'FTTB' : 'FTTH';

    // 1. Attempt dynamic DB lookup
    try {
        $db = getDB();
        ensurePricingAndServiceTypesSchema($db);

        // Try exact package code match for the target service type
        $stmt = $db->prepare("SELECT mrc_amount, nrc_amount, currency FROM service_pricing_packages WHERE is_active = 1 AND service_type = ? AND (package_code = ? OR package_name = ?) LIMIT 1");
        $stmt->execute([$targetServiceType, $package, $package]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return ['mrc' => (float)$row['mrc_amount'], 'nrc' => (float)$row['nrc_amount'], 'currency' => $row['currency'] ?? ($isFTTB ? 'TZS' : 'USD')];
        }

        // Try matching by speed digits (e.g. "20", "30", "40", "50", "100")
        if (preg_match('/(\d+)/', $package, $m)) {
            $speed = (int)$m[1];
            $speedStmt = $db->prepare("SELECT mrc_amount, nrc_amount, currency FROM service_pricing_packages WHERE is_active = 1 AND service_type = ? AND (speed_mbps = ? OR package_code LIKE ? OR package_name LIKE ?) LIMIT 1");
            $speedStmt->execute([$targetServiceType, $speed, "%$speed%", "%$speed%"]);
            $sRow = $speedStmt->fetch(PDO::FETCH_ASSOC);
            if ($sRow) {
                return ['mrc' => (float)$sRow['mrc_amount'], 'nrc' => (float)$sRow['nrc_amount'], 'currency' => $sRow['currency'] ?? ($isFTTB ? 'TZS' : 'USD')];
            }
        }
    } catch (Throwable $e) {}

    // 2. Fallback static map
    if ($isFTTB) {
        $fttbMap = [
            30 => 99000.00,
            40 => 27500.00,
            50 => 33000.00,
            60 => 39500.00,
            70 => 46000.00,
            80 => 52500.00,
            90 => 59000.00,
            100 => 65500.00,
        ];
        preg_match('/(\d+)/', $package, $pm);
        $spd = !empty($pm[1]) ? (int)$pm[1] : 30;
        return ['mrc' => $fttbMap[$spd] ?? 99000.00, 'nrc' => 140000.00, 'currency' => 'TZS'];
    }

    $ftthMap = [
        20 => 10.40,
        30 => 12.48,
        40 => 13.52,
        50 => 16.22,
        60 => 19.47,
        80 => 23.36,
        100 => 28.04,
    ];
    preg_match('/(\d+)/', $package, $pm);
    $spd = !empty($pm[1]) ? (int)$pm[1] : 30;
    return ['mrc' => $ftthMap[$spd] ?? 12.48, 'nrc' => 60.00, 'currency' => 'USD'];
}

/**
 * Compute Layer 2 ( last mile) pricing.
 * Capacity <= 100Mbps -> 110,000 TZS MRC, >= 101Mbps -> 220,000 TZS MRC.
 * Standard NRC = 250,000 TZS, Remote Hands NRC = 80,000 TZS.
 */
function getL2Pricing(string $capacity): array {
    $val = 0;
    if (preg_match('/(\d+)\s*(Gbps|Gb)/i', $capacity, $m)) {
        $val = (float)$m[1] * 1000;
    } elseif (preg_match('/(\d+)\s*(Mbps|Mb)/i', $capacity, $m)) {
        $val = (float)$m[1];
    } elseif (is_numeric($capacity)) {
        $val = (float)$capacity;
    }

    // Try dynamic DB lookup
    try {
        $db = getDB();
        ensurePricingAndServiceTypesSchema($db);
        if ($val > 0 && $val <= 100) {
            $stmt = $db->query("SELECT mrc_amount, nrc_amount FROM service_pricing_packages WHERE service_type LIKE '%Layer 2%' AND package_code = 'L2-100' AND is_active = 1 LIMIT 1");
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) return ['mrc' => (float)$r['mrc_amount'], 'nrc' => (float)$r['nrc_amount'], 'remote_hands_nrc' => 80000.00];
        } elseif ($val > 100) {
            $stmt = $db->query("SELECT mrc_amount, nrc_amount FROM service_pricing_packages WHERE service_type LIKE '%Layer 2%' AND package_code = 'L2-1000' AND is_active = 1 LIMIT 1");
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) return ['mrc' => (float)$r['mrc_amount'], 'nrc' => (float)$r['nrc_amount'], 'remote_hands_nrc' => 80000.00];
        }
    } catch (Throwable $e) {}

    $mrc = ($val > 0 && $val <= 100) ? 110000.00 : ($val > 100 ? 220000.00 : 0.00);
    return [
        'mrc' => $mrc,
        'nrc' => 250000.00,
        'remote_hands_nrc' => 80000.00
    ];
}
/**
 * Canonical Commercial Summary for an order (Single Source of Truth across Neilos Portal).
 * Matches order detail Commercial Summary logic exactly.
 */
function getOrderCommercialSummary(array $order): array {
    // 1. NRC Calculation
    $hasRevNrc = ($order['revised_nrc'] !== null && $order['revised_nrc'] !== '');
    $stdNrc    = (float)($order['standard_nrc'] ?? $order['base_nrc_usd'] ?? 0);
    $revNrc    = $hasRevNrc ? (float)$order['revised_nrc'] : 0.00;
    
    // Active Base NRC is Revised NRC if present, otherwise Standard NRC
    $baseNrc   = $hasRevNrc ? $revNrc : $stdNrc;
    $rhNrc     = (float)($order['remote_hands_nrc_usd'] ?? 0);
    
    $nrcSubtotal = round($baseNrc + $rhNrc, 2);
    $vatNrc      = round($nrcSubtotal * 0.18, 2);
    $totalNrc    = round($nrcSubtotal + $vatNrc, 2);

    // 2. MRC Calculation
    $hasMgmtPrice = ($order['management_approved_price'] !== null && $order['management_approved_price'] !== '');
    $hasRevMrc    = ($order['revised_mrc'] !== null && $order['revised_mrc'] !== '');
    $stdMrc       = (float)($order['standard_mrc'] ?? $order['base_mrc'] ?? 0);
    $revMrc       = $hasRevMrc ? (float)$order['revised_mrc'] : 0.00;
    $mgmtMrc      = $hasMgmtPrice ? (float)$order['management_approved_price'] : 0.00;

    // Effective MRC value: Management Approved Price > Revised MRC > Standard MRC
    $mrcVal   = $hasMgmtPrice ? $mgmtMrc : ($hasRevMrc ? $revMrc : $stdMrc);
    $vatMrc   = round($mrcVal * 0.18, 2);
    $totalMrc = round($mrcVal + $vatMrc, 2);

    // Total Combined Revenue & VAT
    $totalRevenue = round($totalNrc + $totalMrc, 2);
    $totalVat     = round($vatNrc + $vatMrc, 2);

    return [
        'currency'      => $order['mrc_currency'] ?: 'USD',
        'has_rev_nrc'   => $hasRevNrc,
        'std_nrc'       => $stdNrc,
        'rev_nrc'       => $revNrc,
        'base_nrc'      => $baseNrc,
        'rh_nrc'        => $rhNrc,
        'nrc_subtotal'  => $nrcSubtotal,
        'total_nrc_net' => $nrcSubtotal,
        'vat_nrc'       => $vatNrc,
        'total_nrc'     => $totalNrc,     // Total NRC Incl. VAT (for SOF)

        'has_rev_mrc'   => $hasRevMrc,
        'has_mgmt_mrc'  => $hasMgmtPrice,
        'std_mrc'       => $stdMrc,
        'rev_mrc'       => $revMrc,
        'mgmt_mrc'      => $mgmtMrc,
        'mrc_val'       => $mrcVal,       // Effective Base MRC
        'total_mrc_net' => $mrcVal,
        'vat_mrc'       => $vatMrc,
        'total_mrc'     => $totalMrc,     // Effective Total MRC Incl. VAT (for SOF)

        'vat_total'               => $totalVat,
        'total_revenue'           => $totalRevenue, // Total NRC Incl. VAT + Total MRC Incl. VAT (for SOF)
        'total_first_payment_net' => round($nrcSubtotal + $mrcVal, 2) // Net first payment without VAT
    ];
}

/**
 * Retrieve DIA package pricing if defined in DB or fall back to DIA standard defaults.
 */
function getDIAPricing(string $pkgOrBandwidth = ''): array {
    try {
        $db = getDB();
        ensurePricingAndServiceTypesSchema($db);
        if ($pkgOrBandwidth) {
            $stmt = $db->prepare("SELECT mrc_amount, nrc_amount, currency FROM service_pricing_packages WHERE is_active = 1 AND service_type = 'DIA' AND (package_code = ? OR package_name = ?) LIMIT 1");
            $stmt->execute([$pkgOrBandwidth, $pkgOrBandwidth]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return ['mrc' => (float)$row['mrc_amount'], 'nrc' => (float)$row['nrc_amount'], 'currency' => $row['currency'] ?? 'USD'];
            }
        }
        $stStmt = $db->query("SELECT default_nrc, default_mrc FROM service_types WHERE name = 'DIA' LIMIT 1");
        $st = $stStmt->fetch(PDO::FETCH_ASSOC);
        if ($st) {
            return ['mrc' => (float)$st['default_mrc'], 'nrc' => (float)$st['default_nrc'], 'currency' => 'USD'];
        }
    } catch (Throwable $e) {}
    return ['mrc' => 0.00, 'nrc' => 100.00, 'currency' => 'USD'];
}

/**
 * Calculate commercial summary server-side.
 * Predefined package costs apply ONLY to FTTH and DIA service types.
 * All other service types (Layer 2, FTTB, FTTE, BIA, Remote Hands Only, etc.) have 0.00 default NRC/MRC,
 * inserted during Feasibility Review (NRC by BSA) and Commercial Review (MRC by KAM).
 */
function calculateCommercials(array $data): array {
    $serviceType = $data['service_type'] ?? '';
    $vatRate     = VAT_RATE;

    $baseNRC        = 0.00;
    $remoteHandsNRC = 0.00;
    $baseMRC        = 0.00;
    $mrcCurrency    = 'USD';

    if ($serviceType === 'FTTH') {
        $pricing = getFTTxPricing($data['fttx_package'] ?? '', 'FTTH');
        $baseMRC = $pricing['mrc'];
        $baseNRC = $pricing['nrc'];
        $mrcCurrency = $pricing['currency'] ?? 'USD';
    } elseif ($serviceType === 'DIA') {
        $pricing = getDIAPricing($data['dia_package'] ?? $data['fttx_package'] ?? $data['bandwidth'] ?? '');
        $baseMRC = $pricing['mrc'] ?? 0.00;
        $baseNRC = $pricing['nrc'] ?? 0.00;
        $mrcCurrency = $pricing['currency'] ?? 'USD';
    } else {
        // All other products (Layer 2, FTTB, FTTE, BIA, Remote Hands Only, etc.):
        // Prices inserted by BSA (NRC) and KAM (MRC)
        $baseNRC = 0.00;
        $baseMRC = 0.00;
        $mrcCurrency = 'USD';
    }

    // Add Remote Hands NRC (80,000 TZS) if requested
    if (!empty($data['remote_hands_required']) && ($data['remote_hands_required'] == '1' || strtolower($data['remote_hands_required']) === 'yes')) {
        $remoteHandsNRC = 80000.00;
    } elseif (!empty($data['remote_hands_nrc_usd']) && (float)$data['remote_hands_nrc_usd'] > 0) {
        $remoteHandsNRC = (float)$data['remote_hands_nrc_usd'];
    }

    // Term discount logic removed per specification requirement (KAM applies custom discount / revised pricing)
    $discountPct    = 0.00;
    $discountAmt    = 0.00;

    $nrcSubtotal = $baseNRC + $remoteHandsNRC;
    $vatNRC      = round($nrcSubtotal * $vatRate, 2);
    $totalNRC    = round($nrcSubtotal + $vatNRC, 2);

    $mrcAfterDiscount = $baseMRC - $discountAmt;
    $vatMRC         = round($mrcAfterDiscount * $vatRate, 2);
    $totalMRC       = round($mrcAfterDiscount + $vatMRC, 2);

    return [
        'base_nrc_usd'        => $baseNRC,
        'remote_hands_nrc_usd'=> $remoteHandsNRC,
        'nrc_subtotal_usd'    => $nrcSubtotal,
        'vat_on_nrc'          => $vatNRC,
        'total_nrc_incl_vat'  => $totalNRC,
        'base_mrc'            => $baseMRC,
        'mrc_currency'        => $mrcCurrency,
        'discount_pct'        => $discountPct,
        'discount_amount'     => $discountAmt,
        'vat_on_mrc'          => $vatMRC,
        'total_mrc_incl_vat'  => $totalMRC,
        'usd_tzs_rate'        => USD_TZS_RATE,
    ];
}

function profilePictureUrl(?string $path): string {
    if ($path && file_exists(__DIR__ . '/../../public/' . $path)) {
        return APP_URL . '/' . $path;
    }
    return '';
}

/**
 * Safely renders system notification messages.
 * Parses and strips raw/escaped HTML tags so literal strings like <strong> are never displayed as plaintext.
 * Allows safe inline formatting tags (strong, b, em, i, span, br) to render as rich-text HTML elements.
 */
function renderNotificationMessage(string $message): string {
    if (empty($message)) return '';

    // If message contains htmlspecialchars-encoded tags (&lt;strong&gt;), decode them
    $decoded = htmlspecialchars_decode($message, ENT_QUOTES);

    // Strip unsafe tags (script, iframe, style, etc.) while keeping safe formatting tags
    $safe = strip_tags($decoded, '<strong><b><em><i><span><br>');

    return $safe;
}

/**
 * Render standard View | Download | (Replace) | (Delete) file action buttons system-wide.
 */
function renderFileActions(array $options): string {
    $fileUrl     = $options['file_url'] ?? '';
    $fileName    = $options['file_name'] ?? 'File';
    $downloadUrl = $options['download_url'] ?? (APP_URL . '/?page=download&file=' . urlencode($fileUrl));
    $metadata    = $options['metadata'] ?? [];
    $canEdit     = !empty($options['can_edit']);
    $replaceUrl  = $options['replace_url'] ?? '';
    $deleteUrl   = $options['delete_url'] ?? '';
    $deleteId    = $options['delete_id'] ?? 0;
    $csrfToken   = csrfToken();

    $jsonMeta = htmlspecialchars(json_encode(array_merge([
        'file_name' => $fileName
    ], $metadata)), ENT_QUOTES, 'UTF-8');

    $fullFileUrl = (strpos($fileUrl, 'http') === 0) ? $fileUrl : (APP_URL . '/' . ltrim($fileUrl, '/\\'));

    $html = '<div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">';

    // 1. View Button (Opens in-app preview modal)
    $html .= '<button type="button" class="btn-file-action btn-file-view" onclick="event.stopPropagation(); viewSystemFile(\'' . e($fullFileUrl) . '\', \'' . e($fileName) . '\', \'' . e($downloadUrl) . '\', ' . $jsonMeta . ')" title="View in-app document preview">';
    $html .= svgIcon('eye', 13) . ' View';
    $html .= '</button>';

    // 2. Download Button (Direct forced attachment download to local computer)
    $html .= '<a href="' . e($downloadUrl) . '" class="btn-file-action btn-file-download" style="text-decoration:none;" title="Download file to computer">';
    $html .= svgIcon('download', 13) . ' Download';
    $html .= '</a>';

    // 3. Edit / Replace & Delete if authorized
    if ($canEdit) {
        if ($replaceUrl) {
            $html .= '<button type="button" class="btn-file-action btn-file-replace" onclick="event.stopPropagation(); window.location.href=\'' . e($replaceUrl) . '\'" title="Upload replacement file">';
            $html .= svgIcon('edit', 13) . ' Replace';
            $html .= '</button>';
        }

        if ($deleteId && $deleteUrl) {
            $html .= '<form method="POST" action="' . e($deleteUrl) . '" style="display:inline" data-confirm="Delete file \'' . e($fileName) . '\'? This action cannot be undone." data-confirm-title="Delete File?" data-confirm-btn="Delete" data-confirm-class="btn-danger" data-confirm-icon="🗑️" onclick="event.stopPropagation()">';
            $html .= '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
            $html .= '<input type="hidden" name="id" value="' . (int)$deleteId . '">';
            $html .= '<button type="submit" class="btn-file-action btn-file-delete" title="Delete file">';
            $html .= svgIcon('trash', 13) . ' Delete';
            $html .= '</button>';
            $html .= '</form>';
        }
    }

    $html .= '</div>';
    return $html;
}

/**
 * Canonical helper to determine if an order is active.
 * Rule: An order is Active ONLY when:
 * 1. Order status is 'Closed'
 * 2. Order has a valid billing start date
 * 3. Order is not explicitly deactivated (is_active = 1 or not 0)
 */
function isOrderActive(array $order): bool {
    $isClosed = ($order['status'] ?? '') === 'Closed';
    $hasBillingDate = !empty($order['billing_start_date']);
    $isActiveFlag = !isset($order['is_active']) || (int)$order['is_active'] === 1;
    return $isClosed && $hasBillingDate && $isActiveFlag;
}

/**
 * Canonical Single Source of Truth for Projected Total Revenue.
 * Calculates recurring revenue strictly from Closed + Billing Active + Active orders based on Billing Date and Service Term.
 *
 * Requirements:
 * - Order is CLOSED (o.status = 'Closed')
 * - Order has valid BILLING DATE (o.billing_start_date IS NOT NULL AND o.billing_start_date <= CURRENT_DATE())
 * - Order is ACTIVE (o.is_active = 1 or is null; inactive orders contribute TZS 0)
 * - Monthly recurring revenue = getOrderCommercialSummary($order)['total_mrc']
 * - Elapsed billing months = calendar months from billing_start_date through current month (dynamic)
 * - Billable months = elapsed billing months capped by Service Term (e.g. 12, 24, 36 months)
 * - Projected revenue per order = total_mrc * billable_months
 */
function getProjectedTotalRevenue(PDO $db, array $partnerWhere = ['condition' => '1=1', 'params' => []], string $dateWhere = '', array $dateParams = []): array {
    $currentYear  = (int)date('Y');
    $currentMonth = (int)date('n');

    $stmt = $db->prepare("
        SELECT o.*, p.name as partner_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        WHERE o.status = 'Closed'
          AND o.billing_start_date IS NOT NULL
          AND o.billing_start_date <= CURRENT_DATE()
          AND (o.is_active = 1 OR o.is_active IS NULL)
          AND {$partnerWhere['condition']}
          $dateWhere
        ORDER BY o.billing_start_date ASC, o.id ASC
    ");
    $stmt->execute(array_merge($partnerWhere['params'], $dateParams));
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projectedTotalRevenue = 0.0;
    $currentMonthlyMrc     = 0.0;
    $totalBillableMonths   = 0;
    $eligibleOrdersList    = [];

    foreach ($orders as $ord) {
        $isActive = isset($ord['is_active']) ? (int)$ord['is_active'] : 1;
        if ($isActive === 0) {
            continue; // Inactive orders contribute TZS 0
        }

        if ($ord['status'] !== 'Closed' || empty($ord['billing_start_date'])) {
            continue;
        }

        $comm = getOrderCommercialSummary($ord);
        $mrc = (float)($comm['total_mrc'] ?? 0);
        if ($mrc <= 0) {
            continue; // Zero MRC orders contribute TZS 0
        }

        $billDateStr = substr($ord['billing_start_date'], 0, 10);
        $billDate = DateTime::createFromFormat('Y-m-d', $billDateStr);
        if (!$billDate) {
            try {
                $billDate = new DateTime($ord['billing_start_date']);
            } catch (Throwable $e) {
                continue;
            }
        }
        $startYear  = (int)$billDate->format('Y');
        $startMonth = (int)$billDate->format('n');

        $elapsedMonths = ($currentYear - $startYear) * 12 + ($currentMonth - $startMonth) + 1;
        if ($elapsedMonths < 1) {
            $elapsedMonths = 0;
        }

        // Parse Service Term in months
        $termStr = trim($ord['contract_term'] ?? '');
        $termDigits = (int)preg_replace('/[^0-9]/', '', $termStr);
        $termMonths = $termDigits > 0 ? $termDigits : null;

        if ($termMonths !== null) {
            $billableMonths = min($elapsedMonths, $termMonths);
            $isTermExpired  = ($elapsedMonths >= $termMonths);
        } else {
            $billableMonths = $elapsedMonths;
            $isTermExpired  = false;
        }

        $orderProjectedRevenue = round($mrc * $billableMonths, 2);

        $projectedTotalRevenue += $orderProjectedRevenue;
        $totalBillableMonths   += $billableMonths;

        if (!$isTermExpired) {
            $currentMonthlyMrc += $mrc;
        }

        $eligibleOrdersList[] = [
            'order_id'             => (int)$ord['id'],
            'order_number'         => $ord['order_number'],
            'customer_name'        => $ord['customer_name'],
            'partner_name'         => $ord['partner_name'] ?? '',
            'service_type'         => $ord['service_type'] ?? '',
            'billing_start_date'   => $ord['billing_start_date'],
            'contract_term'        => $termStr ?: '—',
            'term_months'          => $termMonths,
            'elapsed_months'       => $elapsedMonths,
            'billable_months'      => $billableMonths,
            'is_term_expired'      => $isTermExpired,
            'monthly_mrc'          => $mrc,
            'projected_revenue'    => $orderProjectedRevenue,
        ];
    }

    return [
        'projected_total_revenue' => round($projectedTotalRevenue, 2),
        'current_monthly_mrc'     => round($currentMonthlyMrc, 2),
        'eligible_order_count'    => count($eligibleOrdersList),
        'total_billable_months'   => $totalBillableMonths,
        'eligible_orders'         => $eligibleOrdersList,
        'current_month_label'     => date('F Y'),
    ];
}

/**
 * Retrieve global system setting value by key.
 */
function getSystemSetting(PDO $db, string $key, $default = null): ?string {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (string)$val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/**
 * Retrieve full global system setting metadata for USD exchange rate.
 */
function getGlobalUsdExchangeRate(PDO $db): array {
    try {
        $stmt = $db->prepare("
            SELECT s.setting_value, s.updated_at, s.updated_by, u.full_name as updated_by_name, u.role as updated_by_role
            FROM system_settings s
            LEFT JOIN users u ON s.updated_by = u.id
            WHERE s.setting_key = 'usd_exchange_rate'
            LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && is_numeric($row['setting_value']) && (float)$row['setting_value'] > 0) {
            return [
                'rate'            => (float)$row['setting_value'],
                'has_rate'        => true,
                'updated_at'      => $row['updated_at'],
                'updated_by'      => (int)($row['updated_by'] ?? 0),
                'updated_by_name' => $row['updated_by_name'] ?: 'System',
                'updated_by_role' => $row['updated_by_role'] ?: ''
            ];
        }
    } catch (Throwable $e) {}
    return [
        'rate'            => 0.0,
        'has_rate'        => false,
        'updated_at'      => null,
        'updated_by'      => 0,
        'updated_by_name' => null,
        'updated_by_role' => null
    ];
}

/**
 * Render visual cards gallery for installation evidence and site photos.
 */
function renderEvidenceGallery(array $evidenceList, array $options = []): string {
    $canDelete    = !empty($options['can_delete']);
    $orderId      = (int)($options['order_id'] ?? 0);
    $assignmentId = (int)($options['assignment_id'] ?? 0);
    $showEmpty    = $options['show_empty'] ?? true;
    $redirectTo   = $options['redirect_to'] ?? (APP_URL . '/?page=order_detail&id=' . $orderId);
    $csrfToken    = csrfToken();

    if (empty($evidenceList)) {
        if (!$showEmpty) return '';
        return '<div class="alert alert-info" style="margin-top:12px;display:flex;align-items:center;gap:10px;font-size:0.85rem">'
             . svgIcon('info', 16)
             . ' <span>No installation photos or test evidence uploaded yet. Select an evidence category above to upload site photos and test results.</span>'
             . '</div>';
    }

    $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));gap:14px;margin-top:14px">';

    foreach ($evidenceList as $ev) {
        $evId      = (int)($ev['id'] ?? 0);
        $evType    = $ev['evidence_type'] ?? 'Evidence';
        $fileName  = $ev['file_name'] ?? '';
        $filePath  = $ev['file_path'] ?? '';
        $fileSize  = (int)($ev['file_size'] ?? 0);
        $notes     = trim($ev['notes'] ?? '');
        $serialNum = trim($ev['serial_number'] ?? '');
        $uploader  = $ev['full_name'] ?? 'Team';
        $uploadAt  = $ev['uploaded_at'] ?? '';

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION) ?: pathinfo($fileName, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'heic', 'jfif']);
        $isPdf   = ($ext === 'pdf');

        $fullFileUrl = '';
        $downloadUrl = '';
        if ($filePath) {
            $fullFileUrl = (strpos($filePath, 'http') === 0) ? $filePath : (APP_URL . '/' . ltrim($filePath, '/\\'));
            $downloadUrl = APP_URL . '/?page=download&table=contractor_evidence&id=' . $evId;
        }
        $imgSrc = $fullFileUrl ?: ($downloadUrl ? $downloadUrl . '&inline=1' : '');
        $displayFileName = !empty($fileName) ? $fileName : ($evType . ($ext ? '.' . $ext : ''));

        // Color theme based on category
        $badgeStyle = 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1';
        $iconSvg = svgIcon('document', 14);
        if (stripos($evType, 'Photo') !== false || stripos($evType, 'Site') !== false) {
            $badgeStyle = 'background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd';
            $iconSvg = svgIcon('image', 14);
        } elseif (stripos($evType, 'ONT') !== false || stripos($evType, 'Serial') !== false) {
            $badgeStyle = 'background:#f3e8ff;color:#7e22ce;border:1px solid #e9d5ff';
            $iconSvg = svgIcon('server', 14);
        } elseif (stripos($evType, 'Signal') !== false || stripos($evType, 'Optical') !== false) {
            $badgeStyle = 'background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4';
            $iconSvg = svgIcon('activity', 14);
        } elseif (stripos($evType, 'Speed') !== false) {
            $badgeStyle = 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0';
            $iconSvg = svgIcon('chart', 14);
        } elseif (stripos($evType, 'Ping') !== false || stripos($evType, 'Latency') !== false) {
            $badgeStyle = 'background:#e0e7ff;color:#4338ca;border:1px solid #c7d2fe';
            $iconSvg = svgIcon('clock', 14);
        } elseif (stripos($evType, 'UAT') !== false) {
            $badgeStyle = 'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0';
            $iconSvg = svgIcon('check', 14);
        } elseif (stripos($evType, 'Additional Cost') !== false) {
            $badgeStyle = 'background:#fef3c7;color:#92400e;border:1px solid #fde68a';
            $iconSvg = svgIcon('document', 14);
        }

        $html .= '<div style="background:var(--surface, #fff);border:1px solid var(--border, #e2e8f0);border-radius:var(--radius-sm, 8px);overflow:hidden;display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:transform 0.15s ease,box-shadow 0.15s ease">';
        
        // Card Top: Category Badge
        $html .= '<div style="padding:10px 12px;border-bottom:1px solid var(--border, #e2e8f0);background:var(--surface-2, #f8fafc);display:flex;align-items:center;justify-content:space-between;gap:8px">';
        $html .= '<span style="font-size:0.75rem;font-weight:700;padding:2px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:4px;' . $badgeStyle . '">' . $iconSvg . ' ' . htmlspecialchars($evType) . '</span>';
        if ($uploadAt) {
            $html .= '<span style="font-size:0.72rem;color:var(--text-secondary, #64748b)">' . fmtDate($uploadAt, 'd M') . '</span>';
        }
        $html .= '</div>';

        // Card Middle: Image thumbnail or document box
        if ($isImage && $imgSrc) {
            $html .= '<div style="height:150px;background:#0f172a;position:relative;overflow:hidden;cursor:pointer;display:flex;align-items:center;justify-content:center" onclick="viewSystemFile(\'' . htmlspecialchars($fullFileUrl, ENT_QUOTES) . '\', \'' . htmlspecialchars($displayFileName, ENT_QUOTES) . '\', \'' . htmlspecialchars($downloadUrl, ENT_QUOTES) . '\', {doc_type:\'' . htmlspecialchars($evType, ENT_QUOTES) . '\'})">';
            $html .= '<img src="' . htmlspecialchars($imgSrc, ENT_QUOTES) . '" alt="' . htmlspecialchars($displayFileName) . '" style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s ease" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">';
            $html .= '<div style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,0.7);color:#fff;font-size:0.72rem;font-weight:600;padding:3px 8px;border-radius:4px;display:flex;align-items:center;gap:4px">' . svgIcon('eye', 12) . ' View Photo</div>';
            $html .= '</div>';
        } elseif ($filePath) {
            $html .= '<div style="height:110px;background:var(--surface-hover, #f1f5f9);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:12px;gap:6px;cursor:pointer" onclick="viewSystemFile(\'' . htmlspecialchars($fullFileUrl, ENT_QUOTES) . '\', \'' . htmlspecialchars($displayFileName, ENT_QUOTES) . '\', \'' . htmlspecialchars($downloadUrl, ENT_QUOTES) . '\', {doc_type:\'' . htmlspecialchars($evType, ENT_QUOTES) . '\'})">';
            $html .= '<div style="color:var(--primary, #0284c7)">' . ($isPdf ? svgIcon('document', 32) : svgIcon('document', 32)) . '</div>';
            $html .= '<span style="font-size:0.72rem;font-weight:700;color:var(--text-secondary, #64748b);text-transform:uppercase">' . ($ext ?: 'FILE') . ' ' . ($fileSize > 0 ? ('(' . formatBytes($fileSize) . ')') : '') . '</span>';
            $html .= '</div>';
        } else {
            $html .= '<div style="height:80px;background:var(--surface-hover, #f8fafc);display:flex;align-items:center;justify-content:center;padding:10px;color:var(--text-secondary)">';
            $html .= '<span style="font-size:0.78rem;font-style:italic">Text / Note entry</span>';
            $html .= '</div>';
        }

        // Card Content: Notes / Serial / Pickup Location / File info
        $html .= '<div style="padding:10px 12px;flex:1;display:flex;flex-direction:column;justify-content:space-between;gap:6px">';
        
        $costAmt = (float)($ev['cost_amount'] ?? 0);
        if ($costAmt > 0) {
            $html .= '<div style="font-size:0.8rem;background:rgba(245,158,11,0.08);border:1px dashed #f59e0b;padding:4px 8px;border-radius:4px;color:#92400e;font-weight:700;display:flex;align-items:center;justify-content:space-between"><span>Additional Cost:</span> <span>$' . number_format($costAmt, 2) . '</span></div>';
        }

        $pickupLoc = trim($ev['pickup_location'] ?? '');
        if ($pickupLoc) {
            $html .= '<div style="font-size:0.75rem;color:var(--primary, #0284c7);background:rgba(2,132,199,0.06);border:1px solid rgba(2,132,199,0.2);padding:3px 7px;border-radius:4px;display:flex;align-items:center;gap:4px"><span>📍</span> <strong style="color:var(--text-secondary)">Site:</strong> ' . htmlspecialchars($pickupLoc) . '</div>';
        }

        if ($serialNum) {
            $html .= '<div style="font-size:0.8rem;background:rgba(126,34,206,0.06);border:1px dashed #d8b4fe;padding:4px 8px;border-radius:4px;color:#6b21a8;word-break:break-all"><strong>Serial:</strong> ' . htmlspecialchars($serialNum) . '</div>';
        }

        if ($notes) {
            $html .= '<div style="font-size:0.8rem;color:var(--text-primary, #1e293b);line-height:1.35;word-break:break-word">' . htmlspecialchars($notes) . '</div>';
        }

        if ($fileName) {
            $html .= '<div style="font-size:0.75rem;color:var(--text-secondary, #64748b);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' . htmlspecialchars($fileName) . '">' . htmlspecialchars($fileName) . ($fileSize > 0 ? ' (' . formatBytes($fileSize) . ')' : '') . '</div>';
        }

        $html .= '<div style="font-size:0.72rem;color:var(--text-secondary, #64748b);margin-top:2px">By ' . htmlspecialchars($uploader) . ' · ' . fmtDateTime($uploadAt) . '</div>';

        // Card Actions
        $html .= '<div style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--border, #e2e8f0);display:flex;align-items:center;justify-content:space-between;gap:6px">';
        
        $html .= '<div style="display:flex;align-items:center;gap:6px">';
        if ($filePath) {
            $html .= '<button type="button" class="btn-file-action btn-file-view" style="font-size:0.74rem;padding:3px 7px" onclick="event.stopPropagation(); viewSystemFile(\'' . htmlspecialchars($fullFileUrl, ENT_QUOTES) . '\', \'' . htmlspecialchars($displayFileName, ENT_QUOTES) . '\', \'' . htmlspecialchars($downloadUrl, ENT_QUOTES) . '\', {doc_type:\'' . htmlspecialchars($evType, ENT_QUOTES) . '\'})">' . svgIcon('eye', 12) . ' View</button>';
            $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" class="btn-file-action btn-file-download" style="font-size:0.74rem;padding:3px 7px;text-decoration:none">' . svgIcon('download', 12) . ' Download</a>';
        }
        $html .= '</div>';

        if ($canDelete && $evId > 0) {
            $html .= '<form method="POST" action="' . APP_URL . '/?page=contractor&action=delete_evidence" style="margin:0" data-confirm="Delete this evidence item (' . htmlspecialchars($evType) . ')? This cannot be undone." data-confirm-title="Delete Evidence?" data-confirm-btn="Delete" data-confirm-class="btn-danger" data-confirm-icon="🗑️" onclick="event.stopPropagation()">';
            $html .= '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
            $html .= '<input type="hidden" name="evidence_id" value="' . $evId . '">';
            $html .= '<input type="hidden" name="assignment_id" value="' . $assignmentId . '">';
            $html .= '<input type="hidden" name="order_id" value="' . $orderId . '">';
            $html .= '<input type="hidden" name="redirect_to" value="' . htmlspecialchars($redirectTo, ENT_QUOTES) . '">';
            $html .= '<button type="submit" class="btn-file-action btn-file-delete" style="font-size:0.74rem;padding:3px 7px" title="Delete evidence">' . svgIcon('trash', 12) . ' Delete</button>';
            $html .= '</form>';
        }

        $html .= '</div>'; // Actions
        $html .= '</div>'; // Content
        $html .= '</div>'; // Card
    }

    $html .= '</div>';
    return $html;
}

/**
 * Mask an email address showing the first 2 letters and last 2 letters of local part,
 * and masked domain name with original TLD.
 * e.g., "ibrahim@sprinttz.co.tz" -> "ib***im@sp***.co.tz"
 *       "admin@neilosnetwork.co.tz" -> "ad*in@ne***.co.tz"
 */
function maskEmail(string $email): string {
    $email = trim($email);
    if (!str_contains($email, '@')) {
        return $email;
    }
    [$local, $domain] = explode('@', $email, 2);
    $localLen = strlen($local);

    if ($localLen <= 2) {
        $maskedLocal = substr($local, 0, 1) . '*';
    } elseif ($localLen <= 4) {
        $maskedLocal = substr($local, 0, 1) . str_repeat('*', $localLen - 2) . substr($local, -1);
    } else {
        $first2 = substr($local, 0, 2);
        $last2  = substr($local, -2);
        $stars  = str_repeat('*', max(3, $localLen - 4));
        $maskedLocal = $first2 . $stars . $last2;
    }

    $domainParts = explode('.', $domain);
    if (count($domainParts) >= 2) {
        $name = $domainParts[0];
        $tld = implode('.', array_slice($domainParts, 1));
        $nameLen = strlen($name);
        if ($nameLen <= 2) {
            $maskedDomain = substr($name, 0, 1) . '*.' . $tld;
        } else {
            $maskedDomain = substr($name, 0, 2) . '***.' . $tld;
        }
    } else {
        $maskedDomain = $domain;
    }

    return $maskedLocal . '@' . $maskedDomain;
}


