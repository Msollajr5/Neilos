<?php
// ============================================================
// Contractors Management Controller — Admin only
// Manages contractor organizations (partners with kyc_type='Contractor'):
// listing, details, editing details, and status updates
// ============================================================
requireLogin();

if (!isAdmin() && !hasRole('Management') && !hasRole('Project Manager') && !hasRole('Project Coordinator') && !hasPermission('contractors.view') && !hasPermission('contractors.manage')) {
    setFlash('danger', 'Access denied.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// Ensure the kyc_type column exists on partners (in case migration not applied)
try {
    $pCols = $db->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kyc_type', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN kyc_type ENUM('Partner','Contractor') NOT NULL DEFAULT 'Partner' AFTER partner_type");
    }
    $db->exec("ALTER TABLE partners MODIFY COLUMN partner_type ENUM('ISP','Reseller','VAR','Enterprise','Government','Contractor','Other') NOT NULL DEFAULT 'Other'");
    $db->exec("ALTER TABLE partner_kyc_applications MODIFY COLUMN registration_number VARCHAR(100) NULL, MODIFY COLUMN tin VARCHAR(50) NULL, MODIFY COLUMN city_region VARCHAR(100) NULL, MODIFY COLUMN registered_name VARCHAR(255) NULL");
} catch (Exception $e) {}

// KYC columns that may or may not exist depending on migration status
$kycCols = [];
try {
    $kycCols = $db->query("SHOW COLUMNS FROM partner_kyc_applications")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

/**
 * Fetch existing Contractor KYC application row for a partner (if any).
 * Does NOT auto-insert a row if missing.
 */
function ensureContractorKyc(PDO $db, int $partnerId): ?array {
    $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE partner_id = ? AND kyc_type = 'Contractor'");
    $stmt->execute([$partnerId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ------------------------------------------------------------------
// POST: Create New Contractor Organization
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $tradingName = trim($_POST['trading_name'] ?? '') ?: $name;
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $cityRegion = trim($_POST['city_region'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = in_array($_POST['status'] ?? 'Active', ['Active', 'Inactive', 'Suspended']) ? $_POST['status'] : 'Active';

    if (!$name) {
        setFlash('danger', 'Contractor company name is required.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, city_region, address, country, created_at)
        VALUES (?, ?, 'Contractor', 'Contractor', ?, ?, ?, 'Tanzania', NOW())")
       ->execute([$name, $tradingName, $status, $cityRegion ?: null, $address ?: null]);
    $contractorId = (int)$db->lastInsertId();

    // Initialize KYC application row
    $db->prepare("INSERT INTO partner_kyc_applications (partner_id, kyc_type, status, registered_name, trading_name, main_contact_name, main_contact_phone, main_contact_email, city_region, address, country, created_at)
        VALUES (?, 'Contractor', 'Draft', ?, ?, ?, ?, ?, ?, ?, 'Tanzania', NOW())")
       ->execute([$contractorId, $name, $tradingName, $contactName, $contactPhone, $contactEmail, $cityRegion, $address]);

    auditLog("Created new contractor organization #$contractorId ($name)", 'partners', $contractorId);
    setFlash('success', "Contractor <strong>" . e($name) . "</strong> registered successfully.");
    header('Location: ' . APP_URL . '/?page=contractors&action=detail&id=' . $contractorId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update contractor details & status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    verifyCsrf();

    $contractorId = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ? AND kyc_type = 'Contractor'");
    $stmt->execute([$contractorId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        setFlash('danger', 'Contractor not found.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    $status = $_POST['status'] ?? 'Active';
    if (!in_array($status, ['Active', 'Inactive', 'Suspended'])) { $status = 'Active'; }

    $db->prepare("UPDATE partners SET
        name = ?, trading_name = ?, partner_type = ?, status = ?,
        customer_category = ?, industry_sector = ?, nature_of_business = ?,
        registration_number = ?, tin = ?, vat_vrn = ?,
        address = ?, city_region = ?, country = ?
        WHERE id = ?")
        ->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['trading_name'] ?? ''),
            $_POST['partner_type'] ?? 'Other',
            $status,
            trim($_POST['customer_category'] ?? ''),
            trim($_POST['industry_sector'] ?? ''),
            trim($_POST['nature_of_business'] ?? ''),
            trim($_POST['registration_number'] ?? ''),
            trim($_POST['tin'] ?? ''),
            trim($_POST['vat_vrn'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['city_region'] ?? ''),
            trim($_POST['country'] ?? 'Tanzania'),
            $contractorId,
        ]);

    // Update contractor-specific KYC details
    $kyc = ensureContractorKyc($db, $contractorId);

    $kycTextFields = [
        'registered_name', 'trading_name', 'customer_category', 'industry_sector',
        'nature_of_business', 'registration_number', 'tin', 'vat_vrn',
        'address', 'city_region', 'country',
        'main_contact_name', 'main_contact_phone', 'main_contact_email',
        'ops_contact_name', 'ops_contact_phone', 'ops_contact_email',
        'tech_supervisor_name', 'tech_supervisor_phone', 'tech_supervisor_email',
        'escalation_contact_name', 'escalation_contact_phone', 'escalation_contact_email',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'bank_payment_terms', 'service_regions', 'review_notes',
    ];
    $kycCapFields = [
        'cap_ftth_install', 'cap_sme_install', 'cap_enterprise_install',
        'cap_site_survey', 'cap_maintenance', 'cap_remote_support',
    ];

    $sets = [];
    $params = [];
    foreach ($kycTextFields as $f) {
        if (!in_array($f, $kycCols)) continue;
        $sets[] = "$f = ?";
        $params[] = trim($_POST[$f] ?? '') ?: null;
    }
    foreach ($kycCapFields as $f) {
        if (!in_array($f, $kycCols)) continue;
        $sets[] = "$f = ?";
        $params[] = isset($_POST[$f]) ? 1 : 0;
    }
    if (in_array('status', $kycCols)) {
        $kycStatus = $_POST['kyc_status'] ?? $kyc['status'];
        if (!in_array($kycStatus, ['Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected'])) {
            $kycStatus = $kyc['status'];
        }
        $sets[] = "status = ?";
        $params[] = $kycStatus;
    }

    if (!empty($sets)) {
        $params[] = $kyc['id'];
        $db->prepare("UPDATE partner_kyc_applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    }

    auditLog("Updated contractor #$contractorId ({$_POST['name']}) status to '$status'", 'partners', $contractorId);
    setFlash('success', 'Contractor details and status updated successfully.');
    header('Location: ' . APP_URL . '/?page=contractors&action=detail&id=' . $contractorId);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete contractor (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    $contractorId = (int)($_POST['id'] ?? 0);
    if ($contractorId > 0) {
        $stmt = $db->prepare("SELECT name FROM partners WHERE id = ? AND kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $cName = $stmt->fetchColumn();

        if ($cName) {
            $db->prepare("DELETE FROM partner_kyc_applications WHERE partner_id = ? AND kyc_type = 'Contractor'")->execute([$contractorId]);
            $db->prepare("DELETE FROM contractor_assignments WHERE contractor_partner_id = ?")->execute([$contractorId]);
            $db->prepare("DELETE FROM partners WHERE id = ? AND kyc_type = 'Contractor'")->execute([$contractorId]);
            auditLog("Deleted contractor $cName (ID: $contractorId)", 'contractors', $contractorId);
            setFlash('success', "Contractor <strong>" . e($cName) . "</strong> deleted successfully.");
        }
    }
    header('Location: ' . APP_URL . '/?page=contractors');
    exit;
}

// ------------------------------------------------------------------
// Contractor Detail
// ------------------------------------------------------------------
if ($action === 'detail') {
    $contractorId = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status, pka.review_notes,
            pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
            pka.ops_contact_name, pka.ops_contact_phone, pka.ops_contact_email,
            pka.tech_supervisor_name, pka.tech_supervisor_phone, pka.tech_supervisor_email,
            pka.escalation_contact_name, pka.escalation_contact_phone, pka.escalation_contact_email,
            pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number,
            pka.bank_payment_terms, pka.service_regions,
            pka.cap_ftth_install, pka.cap_sme_install, pka.cap_enterprise_install,
            pka.cap_site_survey, pka.cap_maintenance, pka.cap_remote_support
            FROM partners p
            LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
            WHERE p.id = ? AND p.kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $contractor = $stmt->fetch();
    } catch (PDOException $e) {
        try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN review_notes TEXT NULL"); } catch (Throwable $t) {}
        $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status
            FROM partners p
            LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
            WHERE p.id = ? AND p.kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $contractor = $stmt->fetch();
    }

    if (!$contractor) {
        http_response_code(404);
        echo '<p style="padding:40px">Contractor not found.</p>';
        exit;
    }

    $ucStmt = $db->prepare("SELECT u.id, u.full_name, u.username, u.email, u.mobile, u.is_active, u.role FROM users u WHERE u.partner_id = ? ORDER BY u.id");
    $ucStmt->execute([$contractorId]);
    $users = $ucStmt->fetchAll();

    $jcStmt = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.customer_location, o.service_type
        FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id
        WHERE ca.contractor_partner_id = ? ORDER BY ca.assigned_at DESC LIMIT 25");
    $jcStmt->execute([$contractorId]);
    $assignments = $jcStmt->fetchAll();

    $stats = [
        'total'   => 0, 'active' => 0, 'submitted' => 0, 'completed' => 0, 'returned' => 0,
    ];
    foreach ($assignments as $a) {
        $stats['total']++;
        if (in_array($a['status'], ['Assigned', 'Accepted', 'In Progress'])) $stats['active']++;
        if ($a['status'] === 'Completed Submitted') $stats['submitted']++;
        if ($a['status'] === 'Completed') $stats['completed']++;
        if ($a['status'] === 'Returned') $stats['returned']++;
    }

    // Teams list with technician member count
    $teamsStmt = $db->prepare("
        SELECT ct.*, 
          (SELECT COUNT(*) FROM contractor_technicians ct2 WHERE ct2.team_id = ct.id) as member_count,
          u.full_name as creator_name,
          u2.full_name as approver_name
        FROM contractor_teams ct
        LEFT JOIN users u ON ct.created_by = u.id
        LEFT JOIN users u2 ON ct.approved_by = u2.id
        WHERE ct.contractor_partner_id = ?
        ORDER BY ct.id DESC
    ");
    $teamsStmt->execute([$contractorId]);
    $teams = $teamsStmt->fetchAll();

    // Technicians directory
    $techStmt = $db->prepare("
        SELECT tech.*, ct.team_name,
          u.full_name as creator_name,
          u2.full_name as approver_name
        FROM contractor_technicians tech
        LEFT JOIN contractor_teams ct ON tech.team_id = ct.id
        LEFT JOIN users u ON tech.created_by = u.id
        LEFT JOIN users u2 ON tech.approved_by = u2.id
        WHERE tech.contractor_partner_id = ?
        ORDER BY tech.id DESC
    ");
    $techStmt->execute([$contractorId]);
    $technicians = $techStmt->fetchAll();

    $pageTitle = 'Contractor: ' . $contractor['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractors/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Edit form
// ------------------------------------------------------------------
if ($action === 'edit') {
    $contractorId = (int)($_GET['id'] ?? 0);
    try {
        $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status, pka.review_notes,
            pka.registered_name, pka.trading_name, pka.customer_category, pka.industry_sector,
            pka.nature_of_business, pka.registration_number, pka.tin, pka.vat_vrn,
            pka.address, pka.city_region, pka.country,
            pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
            pka.ops_contact_name, pka.ops_contact_phone, pka.ops_contact_email,
            pka.tech_supervisor_name, pka.tech_supervisor_phone, pka.tech_supervisor_email,
            pka.escalation_contact_name, pka.escalation_contact_phone, pka.escalation_contact_email,
            pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number,
            pka.bank_payment_terms, pka.service_regions,
            pka.cap_ftth_install, pka.cap_sme_install, pka.cap_enterprise_install,
            pka.cap_site_survey, pka.cap_maintenance, pka.cap_remote_support
            FROM partners p
            LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
            WHERE p.id = ? AND p.kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $contractor = $stmt->fetch();
    } catch (PDOException $e) {
        try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN review_notes TEXT NULL"); } catch (Throwable $t) {}
        try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN address TEXT NULL"); } catch (Throwable $t) {}
        $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status,
            pka.registered_name, pka.trading_name, pka.customer_category, pka.industry_sector,
            pka.nature_of_business, pka.registration_number, pka.tin, pka.vat_vrn,
            pka.city_region, pka.country
            FROM partners p
            LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
            WHERE p.id = ? AND p.kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $contractor = $stmt->fetch();
    }

    if (!$contractor) {
        setFlash('danger', 'Contractor not found.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    // Ensure a KYC row exists so the form always has an id to save against
    ensureContractorKyc($db, $contractorId);

    $pageTitle = 'Edit Contractor: ' . $contractor['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractors/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List contractors
// ------------------------------------------------------------------
// Auto-sync: Ensure every contractor user & contractor record appears in Contractors Management
try {
    $db->exec("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE partner_type = 'Contractor' OR id IN (SELECT DISTINCT partner_id FROM users WHERE role LIKE '%Contractor%' AND partner_id IS NOT NULL AND partner_id > 0)");

    $unlinkedContractorUsers = $db->query("SELECT u.id, u.full_name, u.email FROM users u WHERE role LIKE '%Contractor%' AND (u.partner_id IS NULL OR u.partner_id = 0 OR u.partner_id NOT IN (SELECT id FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor'))")->fetchAll();

    foreach ($unlinkedContractorUsers as $cu) {
        $cName = trim($cu['full_name']);
        if (!$cName) continue;

        $cCheck = $db->prepare("SELECT id FROM partners WHERE name = ? LIMIT 1");
        $cCheck->execute([$cName]);
        $cId = $cCheck->fetchColumn();

        if (!$cId) {
            $ins = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'Contractor','Contractor','Active','Tanzania')");
            $ins->execute([$cName, $cName]);
            $cId = (int)$db->lastInsertId();
        } else {
            $db->prepare("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE id = ?")->execute([$cId]);
        }

        $db->prepare("UPDATE users SET partner_id = ? WHERE id = ?")->execute([$cId, $cu['id']]);
    }
} catch (Exception $e) {}

// Filters: Search, Status, KYC Status, Jobs Filter, and Period
$search        = trim($_GET['q'] ?? '');
$statusFilter  = trim($_GET['status'] ?? '');
$kycFilter     = trim($_GET['kyc_status'] ?? '');
$jobsFilter    = trim($_GET['jobs_filter'] ?? '');
$filterPreset  = trim($_GET['preset'] ?? '');
$filterStart   = trim($_GET['start_date'] ?? '');
$filterEnd     = trim($_GET['end_date'] ?? '');

// Period preset evaluation
if ($filterPreset === 'today') {
    $filterStart = date('Y-m-d');
    $filterEnd   = date('Y-m-d');
} elseif ($filterPreset === 'this_month') {
    $filterStart = date('Y-m-01');
    $filterEnd   = date('Y-m-t');
} elseif ($filterPreset === 'last_month') {
    $filterStart = date('Y-m-01', strtotime('-1 month'));
    $filterEnd   = date('Y-m-t', strtotime('-1 month'));
} elseif ($filterPreset === 'this_year') {
    $filterStart = date('Y-01-01');
    $filterEnd   = date('Y-12-31');
}

$where = "(p.kyc_type = 'Contractor' OR p.partner_type = 'Contractor')";
$params = [];

if ($search !== '') {
    $where .= " AND (p.name LIKE ? OR p.trading_name LIKE ? OR p.registration_number LIKE ? OR p.tin LIKE ? OR p.vat_vrn LIKE ? OR p.city_region LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if (in_array($statusFilter, ['Active', 'Inactive', 'Suspended'])) {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($kycFilter !== '') {
    if ($kycFilter === 'Draft') {
        $where .= " AND (pka.status IS NULL OR pka.status = 'Draft')";
    } else {
        $where .= " AND pka.status = ?";
        $params[] = $kycFilter;
    }
}

if ($jobsFilter === 'has_active') {
    $where .= " AND (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status IN ('Assigned','Accepted','In Progress')) > 0";
} elseif ($jobsFilter === 'has_completed') {
    $where .= " AND (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status = 'Completed') > 0";
} elseif ($jobsFilter === 'no_jobs') {
    $where .= " AND (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id) = 0";
}

// Date Range / Period Filtering on creation date
if ($filterStart && $filterEnd) {
    $where .= " AND p.created_at BETWEEN ? AND ?";
    $params[] = $filterStart . ' 00:00:00';
    $params[] = $filterEnd . ' 23:59:59';
} elseif ($filterStart) {
    $where .= " AND p.created_at >= ?";
    $params[] = $filterStart . ' 00:00:00';
} elseif ($filterEnd) {
    $where .= " AND p.created_at <= ?";
    $params[] = $filterEnd . ' 23:59:59';
}

// ------------------------------------------------------------------
// CSV Export Action
// ------------------------------------------------------------------
if ($action === 'export') {
    $exportStmt = $db->prepare("SELECT p.*, pka.status as kyc_status,
            (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id) as total_jobs,
            (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status IN ('Assigned','Accepted','In Progress')) as active_jobs,
            (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status = 'Completed') as completed_jobs,
            (SELECT COUNT(*) FROM users u WHERE u.partner_id = p.id AND u.role IN ('Contractor', 'Contractor User') AND u.is_active = 1) as user_count
        FROM partners p
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
        WHERE $where
        ORDER BY p.name ASC");
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contractors_export_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'Contractor Name',
        'Trading Name',
        'Type',
        'KYC Status',
        'Status',
        'Total Jobs',
        'Active Jobs',
        'Completed Jobs',
        'Registered Users',
        'TIN',
        'VAT / VRN',
        'Registration Number',
        'City / Region',
        'Address',
        'Country',
        'Created Date'
    ]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['name'] ?? '',
            $r['trading_name'] ?? '',
            $r['partner_type'] ?? 'Contractor',
            $r['kyc_status'] ?? 'Draft',
            $r['status'] ?? 'Active',
            (int)($r['total_jobs'] ?? 0),
            (int)($r['active_jobs'] ?? 0),
            (int)($r['completed_jobs'] ?? 0),
            (int)($r['user_count'] ?? 0),
            $r['tin'] ?? '',
            $r['vat_vrn'] ?? '',
            $r['registration_number'] ?? '',
            $r['city_region'] ?? '',
            $r['address'] ?? '',
            $r['country'] ?? 'Tanzania',
            !empty($r['created_at']) ? date('Y-m-d H:i', strtotime($r['created_at'])) : ''
        ]);
    }
    fclose($out);
    exit;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$totalStmt = $db->prepare("SELECT COUNT(*) FROM partners p 
    LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor' 
    WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT p.*, pka.status as kyc_status,
        pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
        (SELECT COUNT(*) FROM contractor_teams ct WHERE ct.contractor_partner_id = p.id) as total_teams,
        (SELECT COUNT(*) FROM contractor_technicians tech WHERE tech.contractor_partner_id = p.id) as total_technicians,
        (SELECT COUNT(*) FROM contractor_technicians tech WHERE tech.contractor_partner_id = p.id AND tech.status = 'Pending') as pending_technicians,
        (SELECT COUNT(*) FROM users u WHERE u.partner_id = p.id AND u.role IN ('Contractor', 'Contractor User') AND u.is_active = 1) as user_count
    FROM partners p
    LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$contractors = $stmt->fetchAll();

// Global contractor summary statistics
$activeContractors = (int)$db->query("SELECT COUNT(*) FROM partners WHERE (kyc_type = 'Contractor' OR partner_type = 'Contractor') AND status = 'Active'")->fetchColumn();
$totalTeams = (int)$db->query("SELECT COUNT(*) FROM contractor_teams")->fetchColumn();
$totalTechnicians = (int)$db->query("SELECT COUNT(*) FROM contractor_technicians")->fetchColumn();
$pendingTechs = (int)$db->query("SELECT COUNT(*) FROM contractor_technicians WHERE status = 'Pending'")->fetchColumn();

$pageTitle = 'Contractors';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/contractors/list.php';
include APP_DIR . '/views/layout/footer.php';
