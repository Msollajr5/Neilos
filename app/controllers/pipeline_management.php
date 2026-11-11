<?php
// ============================================================
// Neilos Portal — Pipeline Management Controller
// Native module: Sales pipeline, Lead capture, Opportunity lifecycle,
// SO Management, and Project Handover.
// ============================================================
requireLogin();

$user = currentUser();
$db = getDB();

// 1. Fetch real active partners from Neilos database
$partners = [];
try {
    $stmt = $db->query("SELECT id, name, trading_name, partner_type, kyc_type, city_region FROM partners WHERE status = 'Active' ORDER BY name ASC");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// 2. Fetch real active service catalogue
$services = [];
try {
    $stmt = $db->query("SELECT id, name, code, category, unit, base_nrc, base_mrc FROM services WHERE is_active = 1 ORDER BY name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// 3. Fetch real Neilos team members / users (KAMs, Management, BSA, PMs)
$teamUsers = [];
try {
    $stmt = $db->query("SELECT id, full_name, email, role FROM users WHERE is_active = 1 ORDER BY full_name ASC");
    $teamUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// 4. Fetch existing orders that progressed into SO or Projects
$existingOrders = [];
try {
    $stmt = $db->query("
        SELECT o.id, o.order_number, o.sof_number, o.customer_name, o.service_type, o.status, 
               o.building_name, o.customer_location, o.mrc_usd, o.nrc_usd, o.total_mrc_incl_vat,
               o.created_at, o.closed_date, p.name AS partner_name, u.full_name AS kam_name
        FROM orders o
        LEFT JOIN partners p ON o.partner_id = p.id
        LEFT JOIN users u ON o.kam_id = u.id
        ORDER BY o.id DESC
    ");
    $existingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

$pageTitle = 'Pipeline Management';

include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/pipeline_management/index.php';
include APP_DIR . '/views/layout/footer.php';
