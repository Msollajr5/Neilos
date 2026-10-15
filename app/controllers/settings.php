<?php
// ============================================================
// Neilos Company Settings & Migration Controller
// Allows Admin to maintain Neilos company info and export/import system packages
// ============================================================
requireLogin();
requirePermission('admin.settings');

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'index';
$tab    = $_GET['tab'] ?? 'company';

// ------------------------------------------------------------------
// GET / POST: Export Complete System Migration Package
// ------------------------------------------------------------------
if ($action === 'export_system') {
    try {
        $result = exportSystemMigration();
        $zipPath  = $result['zip_path'];
        $filename = $result['filename'];

        if (!file_exists($zipPath)) {
            throw new RuntimeException("Generated export package not found.");
        }

        auditLog("Exported full system migration package ({$filename})", 'system', 0);

        // Stream file download to browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipPath));
        flush();
        readfile($zipPath);

        // Remove temporary package from public upload dir after streaming
        @unlink($zipPath);
        exit;
    } catch (Throwable $e) {
        setFlash('danger', 'System Export Error: ' . e($e->getMessage()));
        header('Location: ' . APP_URL . '/?page=settings&tab=migration');
        exit;
    }
}

// ------------------------------------------------------------------
// POST: Import System Migration Package
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import_system') {
    verifyCsrf();

    if (empty($_FILES['migration_package']['name']) || $_FILES['migration_package']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Please select a valid migration ZIP package to upload.');
        header('Location: ' . APP_URL . '/?page=settings&tab=migration');
        exit;
    }

    $uploadedFile = $_FILES['migration_package']['tmp_name'];
    $originalName = $_FILES['migration_package']['name'];

    try {
        $restoreResult = importSystemMigration($uploadedFile);
        auditLog("Imported and restored system migration package ({$originalName})", 'system', 0);
        
        $msg = "System restored successfully! Restored <strong>{$restoreResult['restored_tables']}</strong> database tables and <strong>{$restoreResult['restored_files']}</strong> uploaded files.";
        if (!empty($restoreResult['file_errors'])) {
            $msg .= "<br><small class='text-warning'>" . count($restoreResult['file_errors']) . " non-critical file notes.</small>";
        }
        setFlash('success', $msg);
    } catch (Throwable $e) {
        setFlash('danger', 'Migration Import Failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=settings&tab=migration');
    exit;
}

// ------------------------------------------------------------------
// POST: Save company info
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_company') {
    verifyCsrf();

    $fields = [
        'company_name', 'trading_name', 'registration_number',
        'tin', 'vat_vrn', 'address', 'city_region', 'country',
        'phone', 'email', 'website', 'default_currency',
        'authorized_signatory', 'signatory_title',
        'finance_contact', 'finance_email',
        'tech_contact', 'tech_email',
    ];

    $set  = [];
    $vals = [];
    foreach ($fields as $f) {
        $set[]  = "$f = ?";
        $vals[] = trim($_POST[$f] ?? '');
    }
    $set[]  = 'updated_by = ?';
    $vals[] = $user['id'];

    // Handle logo upload
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        try {
            $up = uploadFile($_FILES['logo'], 'neilos/logo');
            $set[]  = 'logo_path = ?';
            $vals[] = $up['path'];
        } catch (RuntimeException $e) {
            setFlash('warning', 'Logo upload note: ' . e($e->getMessage()));
        }
    }

    // Upsert row id=1
    $existing = $db->prepare("SELECT id FROM neilos_company_info WHERE id = 1");
    $existing->execute();
    if ($existing->fetch()) {
        $sql = "UPDATE neilos_company_info SET " . implode(', ', $set) . " WHERE id = 1";
        $db->prepare($sql)->execute($vals);
    } else {
        $allCols = array_merge($fields, ['updated_by']);
        $sql = "INSERT INTO neilos_company_info (" . implode(',', $allCols) . ") VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ")";
        $db->prepare($sql)->execute($vals);
    }

    auditLog('Updated Neilos company settings', 'neilos_company_info', 1);
    setFlash('success', 'Company settings saved successfully.');
    header('Location: ' . APP_URL . '/?page=settings&tab=company');
    exit;
}

// ------------------------------------------------------------------
// POST: Save / Update Pricing Package
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_pricing_package') {
    verifyCsrf();
    ensurePricingAndServiceTypesSchema($db);

    $pkgId       = (int)($_POST['package_id'] ?? 0);
    $serviceType = trim($_POST['service_type'] ?? 'FTTH');
    $pkgCode     = trim($_POST['package_code'] ?? '');
    $pkgName     = trim($_POST['package_name'] ?? '');
    $speedMbps   = !empty($_POST['speed_mbps']) ? (int)$_POST['speed_mbps'] : null;
    $rawMrc      = trim($_POST['mrc_amount'] ?? '0');
    $rawNrc      = trim($_POST['nrc_amount'] ?? '0');
    $currency    = strtoupper(trim($_POST['currency'] ?? 'TZS')) ?: 'TZS';
    if (!in_array($currency, ['USD', 'TZS'])) $currency = 'TZS';
    $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $orderNum    = (int)($_POST['display_order'] ?? 0);
    $filterParam = trim($_POST['return_filter'] ?? '');

    if (!$pkgName) {
        setFlash('danger', 'Package name is required.');
        header('Location: ' . APP_URL . '/?page=settings&tab=pricing' . ($filterParam ? '&filter=' . urlencode($filterParam) : ''));
        exit;
    }

    if (!$pkgCode) {
        $pkgCode = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $pkgName));
    }

    // Clean and validate MRC and NRC inputs
    $cleanMrc = str_ireplace(['TZS', 'USD', '$', ',', ' '], '', $rawMrc);
    $cleanNrc = str_ireplace(['TZS', 'USD', '$', ',', ' '], '', $rawNrc);

    $mrc = is_numeric($cleanMrc) ? round((float)$cleanMrc, 2) : 0.00;
    $nrc = is_numeric($cleanNrc) ? round((float)$cleanNrc, 2) : 0.00;

    if ($mrc < 0 || $nrc < 0) {
        setFlash('danger', 'Pricing amounts cannot be negative.');
        header('Location: ' . APP_URL . '/?page=settings&tab=pricing' . ($filterParam ? '&filter=' . urlencode($filterParam) : ''));
        exit;
    }

    if ($pkgId > 0) {
        $stmt = $db->prepare("UPDATE service_pricing_packages SET service_type=?, package_code=?, package_name=?, speed_mbps=?, mrc_amount=?, nrc_amount=?, currency=?, is_active=?, display_order=? WHERE id=?");
        $stmt->execute([$serviceType, $pkgCode, $pkgName, $speedMbps, $mrc, $nrc, $currency, $isActive, $orderNum, $pkgId]);
        auditLog("Updated pricing package '$pkgName' (ID: $pkgId, Service: $serviceType, Currency: $currency)", 'service_pricing_packages', $pkgId);
        setFlash('success', "Pricing package <strong>" . e($pkgName) . "</strong> (" . e($serviceType) . ") updated successfully.");
    } else {
        $stmt = $db->prepare("INSERT INTO service_pricing_packages (service_type, package_code, package_name, speed_mbps, mrc_amount, nrc_amount, currency, is_active, display_order) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$serviceType, $pkgCode, $pkgName, $speedMbps, $mrc, $nrc, $currency, $isActive, $orderNum]);
        $newId = (int)$db->lastInsertId();
        auditLog("Created new pricing package '$pkgName' (ID: $newId, Service: $serviceType, Currency: $currency)", 'service_pricing_packages', $newId);
        setFlash('success', "Pricing package <strong>" . e($pkgName) . "</strong> (" . e($serviceType) . ") created successfully.");
    }

    $redirectUrl = APP_URL . '/?page=settings&tab=pricing' . ($filterParam ? '&filter=' . urlencode($filterParam) : '&filter=' . urlencode($serviceType));
    header('Location: ' . $redirectUrl);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Pricing Package
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_pricing_package') {
    verifyCsrf();
    ensurePricingAndServiceTypesSchema($db);

    $pkgId       = (int)($_POST['package_id'] ?? 0);
    $filterParam = trim($_POST['return_filter'] ?? '');

    if ($pkgId > 0) {
        $stmt = $db->prepare("SELECT package_name FROM service_pricing_packages WHERE id = ?");
        $stmt->execute([$pkgId]);
        $pName = $stmt->fetchColumn();

        $db->prepare("DELETE FROM service_pricing_packages WHERE id = ?")->execute([$pkgId]);
        auditLog("Deleted pricing package '$pName' (ID: $pkgId)", 'service_pricing_packages', $pkgId);
        setFlash('success', "Pricing package <strong>" . e($pName ?: "#$pkgId") . "</strong> deleted successfully.");
    }

    header('Location: ' . APP_URL . '/?page=settings&tab=pricing' . ($filterParam ? '&filter=' . urlencode($filterParam) : ''));
    exit;
}

// ------------------------------------------------------------------
// POST: Save / Update Service Type
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_service_type') {
    verifyCsrf();
    ensurePricingAndServiceTypesSchema($db);

    $stId        = (int)($_POST['service_type_id'] ?? 0);
    $code        = trim($_POST['code'] ?? '');
    $shortCode   = strtoupper(trim($_POST['short_code'] ?? ''));
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $model       = trim($_POST['pricing_model'] ?? 'package');
    $defaultNrc  = (float)str_ireplace(['TZS', 'USD', '$', ',', ' '], '', trim($_POST['default_nrc'] ?? '0'));
    $defaultMrc  = (float)str_ireplace(['TZS', 'USD', '$', ',', ' '], '', trim($_POST['default_mrc'] ?? '0'));
    $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $orderNum    = (int)($_POST['display_order'] ?? 0);

    if (!$name) {
        setFlash('danger', 'Service type name is required.');
        header('Location: ' . APP_URL . '/?page=settings&tab=service_types');
        exit;
    }

    if (!$code) {
        $code = $name;
    }

    if (!$shortCode) {
        $shortCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $code), 0, 4));
    }

    if ($stId > 0) {
        $stmt = $db->prepare("UPDATE service_types SET code=?, short_code=?, name=?, description=?, pricing_model=?, default_nrc=?, default_mrc=?, is_active=?, display_order=? WHERE id=?");
        $stmt->execute([$code, $shortCode, $name, $description, $model, $defaultNrc, $defaultMrc, $isActive, $orderNum, $stId]);
        auditLog("Updated service type '$name' (ID: $stId, Short Code: $shortCode)", 'service_types', $stId);
        setFlash('success', "Service type <strong>" . e($name) . "</strong> updated successfully.");
    } else {
        $stmt = $db->prepare("INSERT INTO service_types (code, short_code, name, description, pricing_model, default_nrc, default_mrc, is_active, display_order) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$code, $shortCode, $name, $description, $model, $defaultNrc, $defaultMrc, $isActive, $orderNum]);
        $newId = (int)$db->lastInsertId();
        auditLog("Created new service type '$name' (ID: $newId, Short Code: $shortCode)", 'service_types', $newId);
        setFlash('success', "Service type <strong>" . e($name) . "</strong> created successfully.");
    }

    header('Location: ' . APP_URL . '/?page=settings&tab=service_types');
    exit;
}

// ------------------------------------------------------------------
// POST: Save Standard Defaults (MRC & NRC)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_standard_defaults') {
    verifyCsrf();
    ensurePricingAndServiceTypesSchema($db);

    $stId       = (int)($_POST['service_type_id'] ?? 0);
    $defaultNrc = (float)str_ireplace(['TZS', 'USD', '$', ',', ' '], '', trim($_POST['default_nrc'] ?? '0'));
    $defaultMrc = (float)str_ireplace(['TZS', 'USD', '$', ',', ' '], '', trim($_POST['default_mrc'] ?? '0'));

    if ($stId > 0) {
        $stmt = $db->prepare("SELECT name FROM service_types WHERE id = ?");
        $stmt->execute([$stId]);
        $stName = $stmt->fetchColumn();

        $db->prepare("UPDATE service_types SET default_nrc = ?, default_mrc = ? WHERE id = ?")
           ->execute([$defaultNrc, $defaultMrc, $stId]);

        auditLog("Updated standard NRC/MRC defaults for '$stName' (NRC: $defaultNrc, MRC: $defaultMrc)", 'service_types', $stId);
        setFlash('success', "Standard NRC & MRC defaults for <strong>" . e($stName) . "</strong> updated successfully.");
    }

    header('Location: ' . APP_URL . '/?page=settings&tab=defaults');
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Service Type
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_service_type') {
    verifyCsrf();
    ensurePricingAndServiceTypesSchema($db);

    $stId = (int)($_POST['service_type_id'] ?? 0);
    if ($stId > 0) {
        $stmt = $db->prepare("SELECT name FROM service_types WHERE id = ?");
        $stmt->execute([$stId]);
        $stName = $stmt->fetchColumn();

        $db->prepare("DELETE FROM service_types WHERE id = ?")->execute([$stId]);
        auditLog("Deleted service type '$stName' (ID: $stId)", 'service_types', $stId);
        setFlash('success', "Service type <strong>" . e($stName ?: "#$stId") . "</strong> deleted successfully.");
    }

    header('Location: ' . APP_URL . '/?page=settings&tab=service_types');
    exit;
}

// ------------------------------------------------------------------
// GET: Load company info and live migration stats & settings
// ------------------------------------------------------------------
ensurePricingAndServiceTypesSchema($db);

$company         = $db->query("SELECT * FROM neilos_company_info WHERE id = 1")->fetch() ?: [];
$systemIntegrity = verifySystemIntegrity();
$dbStats         = getDatabaseStats($db);
$uploadFiles     = scanUploadFiles(UPLOAD_DIR);
$totalUploadSize = array_sum(array_column($uploadFiles, 'size'));

$allPricingPackages = $db->query("SELECT * FROM service_pricing_packages ORDER BY service_type ASC, display_order ASC, speed_mbps ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$allServiceTypes    = $db->query("SELECT * FROM service_types ORDER BY display_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Count active packages per service type
$serviceTypeCounts = [];
foreach ($allPricingPackages as $p) {
    $st = $p['service_type'];
    if (!isset($serviceTypeCounts[$st])) {
        $serviceTypeCounts[$st] = 0;
    }
    if ((int)$p['is_active'] === 1) {
        $serviceTypeCounts[$st]++;
    }
}
$selectedFilter = trim($_GET['filter'] ?? '');

$pageTitle = 'Settings & System Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/settings/company.php';
include APP_DIR . '/views/layout/footer.php';

