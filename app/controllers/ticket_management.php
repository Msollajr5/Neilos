<?php
// ============================================================
// Neilos Portal — Ticket Management Controller
// Native module: Incident ticketing, SLA monitoring, L1->L2->Tech
// escalation, services inventory, on-hold workflow & resolution.
// ============================================================
requireLogin();

$user = currentUser();
$db = getDB();

// 1. Fetch real active services & circuit inventory from Neilos database
$activeServices = [];
try {
    $stmt = $db->query("
        SELECT s.*, p.name AS partner_name, o.order_number, o.sof_number, o.pop_location, 
               o.gps_coordinates, o.customer_contact_phone, o.customer_contact_email, o.closed_date
        FROM active_services s
        LEFT JOIN partners p ON s.partner_id = p.id
        LEFT JOIN orders o ON s.order_id = o.id
        ORDER BY s.id DESC
    ");
    $activeServices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// Fallback: If active_services is empty in local DB, fetch from closed/active orders
if (empty($activeServices)) {
    try {
        $stmt = $db->query("
            SELECT o.id AS order_id, o.service_id, o.order_number, o.sof_number, o.customer_name, 
                   o.service_type, o.circuit_id, o.bandwidth AS bandwidth_capacity, 
                   o.customer_location AS location, o.building_name, o.activation_date, o.status,
                   p.name AS partner_name, o.customer_contact_phone, o.customer_contact_email
            FROM orders o
            LEFT JOIN partners p ON o.partner_id = p.id
            WHERE o.status IN ('Closed', 'Installation', 'UAT')
            ORDER BY o.id DESC
        ");
        $activeServices = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {}
}

// 2. Fetch team members (NOC, KAM, Management)
$teamUsers = [];
try {
    $stmt = $db->query("SELECT id, full_name, email, role FROM users WHERE is_active = 1 ORDER BY full_name ASC");
    $teamUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// 3. Fetch SLA configuration from Neilos sla_config table
$slaConfigs = [];
try {
    $stmt = $db->query("SELECT * FROM sla_config WHERE is_active = 1 ORDER BY id ASC");
    $slaConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

$pageTitle = 'Ticket Management';

include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/ticket_management/index.php';
include APP_DIR . '/views/layout/footer.php';
