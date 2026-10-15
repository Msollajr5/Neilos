<?php
// ============================================================
// Partners Controller — Manage partner organizations
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// Allow Partner users to view their own partner details; KAM, Admin, Management can view and manage
if (!isAdmin() && !hasRole('Management', 'KAM') && !hasPermission('partners.view')) {
    if (isPartnerUser() && $action === 'detail' && (int)($_GET['id'] ?? 0) === (int)($user['partner_id'] ?? -1)) {
        // Allowed
    } else {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=dashboard');
        exit;
    }
}

// Ensure kam_id, assigned_kam_name, partner_category, and contact columns exist
try {
    $pCols = $db->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kam_id', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN kam_id INT NULL");
    }
    if (!in_array('assigned_kam_name', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN assigned_kam_name VARCHAR(100) NULL");
    }
    if (!in_array('partner_category', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN partner_category VARCHAR(50) NOT NULL DEFAULT 'Partner' AFTER partner_type");
    }
    if (!in_array('contact_person_name', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN contact_person_name VARCHAR(200) NULL AFTER address");
    }
    if (!in_array('contact_phone', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN contact_phone VARCHAR(50) NULL AFTER contact_person_name");
    }
    if (!in_array('contact_email', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN contact_email VARCHAR(200) NULL AFTER contact_phone");
    }
} catch (Exception $e) {}

// ------------------------------------------------------------------
// POST: Create partner
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $kamId = (int)($_POST['kam_id'] ?? 0) ?: null;
    if (!$kamId && isKamUser()) {
        $kamId = (int)$user['id'];
    }
    $kamName = null;
    if ($kamId) {
        $kStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $kStmt->execute([$kamId]);
        $kamName = $kStmt->fetchColumn() ?: null;
    }

    $partnerCategory = trim($_POST['partner_category'] ?? 'Partner');
    if (!in_array($partnerCategory, ['Partner', 'Enterprise Client'])) {
        $partnerCategory = 'Partner';
    }
    $partnerType = $_POST['partner_type'] ?? ($partnerCategory === 'Enterprise Client' ? 'Enterprise' : 'ISP');

    $stmt = $db->prepare("INSERT INTO partners (name, trading_name, partner_category, partner_type, status, address, city_region, country, registration_number, tin, contact_person_name, contact_phone, contact_email, kam_id, assigned_kam_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['name'] ?? '',
        $_POST['trading_name'] ?? '',
        $partnerCategory,
        $partnerType,
        $_POST['status'] ?? 'Active',
        $_POST['address'] ?? '',
        $_POST['city_region'] ?? '',
        $_POST['country'] ?? 'Tanzania',
        $_POST['registration_number'] ?? '',
        $_POST['tin'] ?? '',
        $_POST['contact_person_name'] ?? '',
        $_POST['contact_phone'] ?? '',
        $_POST['contact_email'] ?? '',
        $kamId,
        $kamName
    ]);
    $partnerId = $db->lastInsertId();

    $typeLabel = $partnerCategory === 'Enterprise Client' ? 'Enterprise Client' : 'Partner';
    auditLog("Created $typeLabel {$_POST['name']}", 'partners', $partnerId);
    setFlash('success', "$typeLabel <strong>" . e($_POST['name']) . "</strong> created successfully.");
    header('Location: ' . APP_URL . '/?page=partners&action=detail&id=' . $partnerId);
    exit;
}

// ------------------------------------------------------------------
// POST: Edit partner
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    verifyCsrf();

    $partnerId = (int)($_POST['id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Invalid partner ID.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    $stmtOld = $db->prepare("SELECT * FROM partners WHERE id = ?");
    $stmtOld->execute([$partnerId]);
    $existingPartner = $stmtOld->fetch();

    if (!$existingPartner) {
        setFlash('danger', 'Partner not found.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    // Authorization: Admin, Management, or KAM assigned to this partner
    if (isKamUser() && !isAdmin() && !hasRole('Management')) {
        if ((int)($existingPartner['kam_id'] ?? 0) !== (int)$user['id'] && ($existingPartner['assigned_kam_name'] ?? '') !== $user['full_name']) {
            setFlash('danger', 'Access denied. You can only edit partners in your assigned portfolio.');
            header('Location: ' . APP_URL . '/?page=partners');
            exit;
        }
    }

    $kamId = (int)($_POST['kam_id'] ?? 0) ?: null;
    if (!$kamId && isKamUser()) {
        $kamId = (int)$user['id'];
    }
    $kamName = null;
    if ($kamId) {
        $kStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $kStmt->execute([$kamId]);
        $kamName = $kStmt->fetchColumn() ?: null;
    }

    $partnerCategory = trim($_POST['partner_category'] ?? ($existingPartner['partner_category'] ?? 'Partner'));
    if (!in_array($partnerCategory, ['Partner', 'Enterprise Client'])) {
        $partnerCategory = 'Partner';
    }
    $partnerType = $_POST['partner_type'] ?? ($partnerCategory === 'Enterprise Client' ? 'Enterprise' : ($existingPartner['partner_type'] ?? 'ISP'));

    $stmt = $db->prepare("UPDATE partners SET name=?, trading_name=?, partner_category=?, partner_type=?, status=?, address=?, city_region=?, country=?, registration_number=?, tin=?, contact_person_name=?, contact_phone=?, contact_email=?, kam_id=?, assigned_kam_name=? WHERE id=?");
    $stmt->execute([
        $_POST['name'] ?? '',
        $_POST['trading_name'] ?? '',
        $partnerCategory,
        $partnerType,
        $_POST['status'] ?? 'Active',
        $_POST['address'] ?? '',
        $_POST['city_region'] ?? '',
        $_POST['country'] ?? 'Tanzania',
        $_POST['registration_number'] ?? '',
        $_POST['tin'] ?? '',
        $_POST['contact_person_name'] ?? '',
        $_POST['contact_phone'] ?? '',
        $_POST['contact_email'] ?? '',
        $kamId,
        $kamName,
        $partnerId,
    ]);

    auditLog("Updated partner {$_POST['name']}", 'partners', $partnerId);
    setFlash('success', 'Partner details updated successfully.');
    header('Location: ' . APP_URL . '/?page=partners&action=detail&id=' . $partnerId);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete partner (Admin / Management / Assigned KAM)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();

    $partnerId = (int)($_POST['id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Invalid partner ID.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$partnerId]);
    $partnerToDelete = $stmt->fetch();

    if (!$partnerToDelete) {
        setFlash('danger', 'Partner not found.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    // Check authorization: Admin, Management, or KAM who owns this partner
    $canDelete = false;
    if (isAdmin() || hasRole('Management')) {
        $canDelete = true;
    } elseif (isKamUser()) {
        if ((int)($partnerToDelete['kam_id'] ?? 0) === (int)$user['id'] || ($partnerToDelete['assigned_kam_name'] ?? '') === $user['full_name']) {
            $canDelete = true;
        }
    }

    if (!$canDelete) {
        setFlash('danger', 'Access denied. You can only delete partners in your assigned portfolio.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    // Check if partner has associated service orders
    $ocStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE partner_id = ?");
    $ocStmt->execute([$partnerId]);
    $orderCount = (int)$ocStmt->fetchColumn();

    if ($orderCount > 0) {
        setFlash('danger', "Cannot delete partner <strong>" . e($partnerToDelete['name']) . "</strong> because they have {$orderCount} associated service order(s).");
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    // Clean up associated KYC applications and KYC history if any
    try {
        $kycIds = $db->query("SELECT id FROM partner_kyc_applications WHERE partner_id = " . (int)$partnerId)->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($kycIds)) {
            $inKyc = implode(',', array_map('intval', $kycIds));
            $db->exec("DELETE FROM kyc_history WHERE kyc_id IN ($inKyc)");
            $db->exec("DELETE FROM partner_kyc_applications WHERE partner_id = " . (int)$partnerId);
        }
    } catch (Exception $e) {}

    $db->prepare("DELETE FROM partners WHERE id = ?")->execute([$partnerId]);
    auditLog("Deleted partner {$partnerToDelete['name']} (#$partnerId)", 'partners', $partnerId);
    setFlash('success', "Partner <strong>" . e($partnerToDelete['name']) . "</strong> deleted successfully.");
    header('Location: ' . APP_URL . '/?page=partners');
    exit;
}

// ------------------------------------------------------------------
// Detail view
// ------------------------------------------------------------------
if ($action === 'detail') {
    $partnerId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$partnerId]);
    $partner = $stmt->fetch();

    if (!$partner) {
        http_response_code(404);
        echo '<p style="padding:40px">Partner not found.</p>';
        exit;
    }

    $ucStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE partner_id = ?");
    $ucStmt->execute([$partnerId]);
    $userCount = (int)$ucStmt->fetchColumn();
    $ocStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE partner_id = ?");
    $ocStmt->execute([$partnerId]);
    $orderCount = (int)$ocStmt->fetchColumn();

    // Fetch KYC application for this partner
    $kycStmt = $db->prepare("SELECT pka.*, u.full_name as reviewer_name
        FROM partner_kyc_applications pka
        LEFT JOIN users u ON pka.reviewed_by = u.id
        WHERE pka.partner_id = ?
        ORDER BY pka.updated_at DESC, pka.id DESC LIMIT 1");
    $kycStmt->execute([$partnerId]);
    $kycApp = $kycStmt->fetch();

    $kycHistory = [];
    if ($kycApp) {
        $khStmt = $db->prepare("SELECT kh.*, u.full_name as actor_name 
            FROM kyc_history kh 
            LEFT JOIN users u ON kh.action_by = u.id 
            WHERE kh.kyc_id = ? 
            ORDER BY kh.created_at DESC");
        $khStmt->execute([$kycApp['id']]);
        $kycHistory = $khStmt->fetchAll();
    }

    // Fetch clients / orders for this partner
    $clientsStmt = $db->prepare("
        SELECT o.*,
            (SELECT ce.serial_number 
             FROM contractor_evidence ce 
             WHERE ce.order_id = o.id AND ce.serial_number IS NOT NULL AND ce.serial_number != '' 
             ORDER BY ce.id DESC LIMIT 1) as onu_serial_number,
            (SELECT ce.notes 
             FROM contractor_evidence ce 
             WHERE ce.order_id = o.id AND (ce.evidence_type LIKE '%ONT%' OR ce.evidence_type LIKE '%ONU%' OR ce.evidence_type LIKE '%Serial%')
             ORDER BY ce.id DESC LIMIT 1) as onu_evidence_notes
        FROM orders o
        WHERE o.partner_id = ?
        ORDER BY o.created_at DESC
    ");
    $clientsStmt->execute([$partnerId]);
    $partnerClients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $pageTitle = 'Partner: ' . $partner['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/partners/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Create / Edit form
// ------------------------------------------------------------------
if ($action === 'create' || $action === 'edit') {
    $partner = null;
    $partnerId = (int)($_GET['id'] ?? 0);
    if ($action === 'edit' && $partnerId) {
        $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $stmt->execute([$partnerId]);
        $partner = $stmt->fetch();
        if (!$partner) {
            setFlash('danger', 'Partner not found.');
            header('Location: ' . APP_URL . '/?page=partners');
            exit;
        }
    }

    $kamList = $db->query("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 ORDER BY full_name")->fetchAll();

    $pageTitle = $action === 'create' ? 'New Partner' : 'Edit Partner';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/partners/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List partners
// ------------------------------------------------------------------
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$typeFilter = trim($_GET['type'] ?? $_GET['category'] ?? 'all');
if (!in_array($typeFilter, ['partner', 'enterprise', 'all'])) {
    $typeFilter = 'all';
}

$baseWhere = "(kyc_type IS NULL OR kyc_type != 'Contractor') AND (partner_type IS NULL OR partner_type != 'Contractor')";
$baseParams = [];
if (isKamUser()) {
    $baseWhere .= " AND (kam_id = ? OR assigned_kam_name = ?)";
    $baseParams = [(int)$user['id'], $user['full_name']];
}

// Counts for tabs & badges
$cntAllStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE $baseWhere");
$cntAllStmt->execute($baseParams);
$countAll = (int)$cntAllStmt->fetchColumn();

$partnerWhere = $baseWhere . " AND (partner_category = 'Partner' OR (partner_category IS NULL AND (partner_type IS NULL OR partner_type != 'Enterprise')))";
$cntPartnerStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE $partnerWhere");
$cntPartnerStmt->execute($baseParams);
$countPartners = (int)$cntPartnerStmt->fetchColumn();

$enterpriseWhere = $baseWhere . " AND (partner_category = 'Enterprise Client' OR partner_type = 'Enterprise')";
$cntEnterpriseStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE $enterpriseWhere");
$cntEnterpriseStmt->execute($baseParams);
$countEnterprise = (int)$cntEnterpriseStmt->fetchColumn();

// Apply filter for list
$where = $baseWhere;
$params = $baseParams;
if ($typeFilter === 'partner') {
    $where = $partnerWhere;
} elseif ($typeFilter === 'enterprise') {
    $where = $enterpriseWhere;
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT * FROM partners WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$partners = $stmt->fetchAll();

if ($typeFilter === 'enterprise') {
    $pageTitle = 'Enterprise Clients';
} elseif ($typeFilter === 'partner') {
    $pageTitle = 'All Partners';
} else {
    $pageTitle = 'Partners & Clients';
}
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/partners/list.php';
include APP_DIR . '/views/layout/footer.php';




