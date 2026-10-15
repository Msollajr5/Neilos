<?php
// ============================================================
// Neilos Partner Portal — Workflow & Automated Status Evaluator
// ============================================================

/**
 * Ensure system_events table exists for real-time SSE event publishing.
 */
function initSystemEventsTable(): void {
    static $initialized = false;
    if ($initialized) return;
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS system_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            order_id INT UNSIGNED NULL,
            assignment_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            payload LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_order (order_id)
        ) ENGINE=InnoDB;");
        $initialized = true;
    } catch (Exception $e) {}
}

/**
 * Publish a real-time event to system_events for SSE streaming.
 */
function publishSystemEvent(string $eventType, ?int $orderId = null, ?array $extraData = null): void {
    initSystemEventsTable();
    try {
        $db = getDB();
        $user = currentUser();
        $userId = $user ? (int)$user['id'] : null;

        $payload = [
            'event_type' => $eventType,
            'order_id'   => $orderId,
            'timestamp'  => date('Y-m-d H:i:s'),
            'data'       => $extraData ?? []
        ];

        if ($orderId > 0) {
            $stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if ($order) {
                $comm = getOrderCommercialSummary($order);
                $payload['order_number']        = $order['order_number'];
                $payload['status']              = $order['status'];
                $payload['service_type']        = $order['service_type'];
                $payload['customer_name']       = $order['customer_name'];
                $payload['partner_name']        = $order['partner_name'];
                $payload['total_nrc']           = $comm['total_nrc'];
                $payload['total_mrc']           = $comm['total_mrc'];
                $payload['total_revenue']       = $comm['total_revenue'];
                $payload['billing_start_date']  = $order['billing_start_date'];
                $payload['is_billing_active']   = ($order['status'] === 'Closed' && !empty($order['billing_start_date']) && strtotime($order['billing_start_date']) <= time());
            }
        }

        $db->prepare("INSERT INTO system_events (event_type, order_id, user_id, payload) VALUES (?, ?, ?, ?)")
           ->execute([$eventType, $orderId, $userId, json_encode($payload)]);
    } catch (Exception $e) {}
}

/**
 * Single Source of Truth Workflow Status Evaluator.
 * Evaluates DB state, calculates required evidence vs uploaded evidence,
 * updates order status automatically, and publishes SSE real-time events.
 */
function evaluateAndSyncOrderStatus(int $orderId, string $triggerAction = ''): array {
    if (!$orderId) return ['status_changed' => false];

    initSystemEventsTable();
    $db = getDB();

    $stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return ['status_changed' => false];

    $currentStatus = $order['status'];
    $newStatus     = $currentStatus;

    // 1. Service Type Evidence Checklist Matching
    $rawSvc = trim($order['service_type'] ?? '');
    $svcTypes = [];
    if (!empty($rawSvc)) {
        $svcTypes[] = $rawSvc;
        if (stripos($rawSvc, 'FTTH') !== false) { $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'FTTB') !== false) { $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'FTTE') !== false) { $svcTypes[] = 'FTTE'; $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'DIA') !== false) { $svcTypes[] = 'DIA'; }
        if (stripos($rawSvc, 'BIA') !== false || stripos($rawSvc, 'Broadband') !== false) { $svcTypes[] = 'BIA (Broadband Internet Access)'; $svcTypes[] = 'BIA'; $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'Layer 2') !== false || stripos($rawSvc, 'Layer2') !== false || stripos($rawSvc, 'Ethernet') !== false) { $svcTypes[] = 'Layer 2 ( last mile)'; $svcTypes[] = 'Dedicated Layer 2'; $svcTypes[] = 'Layer 2 (last mile)'; }
        if (stripos($rawSvc, 'Remote Hands') !== false) { $svcTypes[] = 'Remote Hands Only'; $svcTypes[] = 'Remote Hands'; }
        if (stripos($rawSvc, 'Dark') !== false || stripos($rawSvc, 'DF') !== false) { $svcTypes[] = 'Dark Fiber'; $svcTypes[] = 'Dark Fibre'; $svcTypes[] = 'DF'; $svcTypes[] = 'Other'; }
        $svcTypes[] = 'Other';
    }
    $svcTypes = array_values(array_unique(array_filter($svcTypes)));
    if (empty($svcTypes)) {
        $svcTypes = ['FTTH', 'Other'];
    }

    $placeholders = implode(',', array_fill(0, count($svcTypes), '?'));
    $chkStmt = $db->prepare("
        SELECT ecc.evidence_type, ecc.is_mandatory, ecc.is_noc_conditional,
          (SELECT COUNT(*) FROM contractor_evidence ce 
           WHERE (ce.order_id = ?) 
             AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(ecc.evidence_type))) as uploaded_count,
          (SELECT COUNT(*) FROM order_documents od
           WHERE od.order_id = ?
             AND LOWER(TRIM(od.document_type)) = LOWER(TRIM(ecc.evidence_type))) as doc_uploaded_count
        FROM evidence_checklist_config ecc 
        WHERE ecc.service_type IN ($placeholders)
        ORDER BY ecc.id ASC
    ");
    $chkStmt->execute(array_merge([$orderId, $orderId], $svcTypes));
    $rawItems = $chkStmt->fetchAll();

    // Deduplicate checklist by evidence_type
    $uniqueChecklist = [];
    foreach ($rawItems as $item) {
        $key = strtolower(trim($item['evidence_type']));
        if (!isset($uniqueChecklist[$key])) {
            $uniqueChecklist[$key] = $item;
        }
    }

    $totalMandatory    = 0;
    $completedMandatory= 0;
    $totalItems        = count($uniqueChecklist);
    $totalUploadedItems= 0;
    $nocIpConfigured   = (bool)$order['noc_ip_configured'];

    foreach ($uniqueChecklist as $item) {
        $isUploaded = ($item['uploaded_count'] > 0 || $item['doc_uploaded_count'] > 0);
        if ($isUploaded) $totalUploadedItems++;

        // Skip conditional items if NOC hasn't configured IP yet
        if ($item['is_noc_conditional'] && !$nocIpConfigured) {
            continue;
        }

        if ($item['is_mandatory']) {
            $totalMandatory++;
            if ($isUploaded) $completedMandatory++;
        }
    }

    $allMandatoryComplete = ($totalMandatory > 0 && $completedMandatory === $totalMandatory);
    $hasMissingMandatory  = ($totalMandatory > 0 && $completedMandatory < $totalMandatory);

    // 2. Workflow Transitions
    // Order changes from "Installation" to "Testing" ONLY after the contractor has uploaded all mandatory evidence AND submitted job completion
    if ($triggerAction === 'submit_completion' && $allMandatoryComplete && in_array($currentStatus, ['Installation', 'Assigned', 'Evidence Pending', 'Pending Evidence', 'Job Completed'])) {
        $newStatus = 'Testing';
    }
    // If mandatory evidence is missing/deleted while in Testing (and not actively approving/completing) -> Revert to Installation
    elseif ($hasMissingMandatory && $currentStatus === 'Testing' && !in_array($triggerAction, ['submit_completion', 'approve_testing'])) {
        $newStatus = 'Installation';
    }

    $statusChanged = ($newStatus !== $currentStatus);

    if ($statusChanged) {
        $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$newStatus, $orderId]);
        auditLog("Automated status transition for Order #{$order['order_number']}: {$currentStatus} -> {$newStatus} (Evidence: {$completedMandatory}/{$totalMandatory} mandatory complete)", 'orders', $orderId);
        $order['status'] = $newStatus;
    }

    // 3. Emit Real-Time System Event for SSE
    publishSystemEvent('order_status_sync', $orderId, [
        'trigger_action'        => $triggerAction,
        'old_status'            => $currentStatus,
        'new_status'            => $newStatus,
        'status_changed'        => $statusChanged,
        'total_items'           => $totalItems,
        'uploaded_items'        => $totalUploadedItems,
        'total_mandatory'       => $totalMandatory,
        'completed_mandatory'   => $completedMandatory,
        'all_mandatory_complete'=> $allMandatoryComplete
    ]);

    return [
        'order_id'              => $orderId,
        'status_changed'        => $statusChanged,
        'old_status'            => $currentStatus,
        'current_status'        => $newStatus,
        'completed_mandatory'   => $completedMandatory,
        'total_mandatory'       => $totalMandatory,
        'all_mandatory_complete'=> $allMandatoryComplete
    ];
}

// ============================================================
// SLA Pause / Resume Helpers
// ============================================================

/**
 * Pause the SLA clock for an order (called when contractor posts a blocker).
 * Idempotent — if already paused, does nothing.
 */
function pauseOrderSLA(int $orderId, int $userId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT sla_paused FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!$row || $row['sla_paused']) return; // already paused

        $db->prepare("UPDATE orders SET sla_paused = 1, sla_paused_at = NOW(), updated_at = NOW() WHERE id = ?")
           ->execute([$orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by)
            SELECT id, status, 'SLA paused — installation blocker posted.', ? FROM orders WHERE id = ?")
           ->execute([$userId, $orderId]);

        auditLog("SLA clock paused for order #$orderId due to blocker", 'orders', $orderId);
    } catch (Exception $e) {
        error_log('SLA pause error: ' . $e->getMessage());
    }
}

/**
 * Resume the SLA clock for an order (called on "Installation Resumed" update).
 * Accumulates elapsed paused time into sla_paused_hours.
 * Idempotent — if already running, does nothing.
 */
function resumeOrderSLA(int $orderId, int $userId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT sla_paused, sla_paused_at, sla_paused_hours FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!$row || !$row['sla_paused']) return; // already running

        $pausedSince = $row['sla_paused_at'] ? strtotime($row['sla_paused_at']) : time();
        $elapsedHours = round((time() - $pausedSince) / 3600, 2);
        $totalPausedHours = round((float)$row['sla_paused_hours'] + $elapsedHours, 2);

        $db->prepare("UPDATE orders SET
            sla_paused = 0, sla_paused_at = NULL,
            sla_paused_hours = ?, updated_at = NOW()
            WHERE id = ?")->execute([$totalPausedHours, $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by)
            SELECT id, status, CONCAT('SLA resumed — paused for ', ?, ' hours. Total paused: ', ?, ' hrs.'), ?
            FROM orders WHERE id = ?")->execute([$elapsedHours, $totalPausedHours, $userId, $orderId]);

        auditLog("SLA clock resumed for order #$orderId (paused {$elapsedHours}h, total paused: {$totalPausedHours}h)", 'orders', $orderId);
    } catch (Exception $e) {
        error_log('SLA resume error: ' . $e->getMessage());
    }
}

/**
 * Retrieve the authoritative active/approved Partner KYC record for a partner.
 * Prioritizes: Approved > Submitted for Approval > Under Review > Submitted > Draft.
 * Returns structured KYC contact & company details.
 */
function getAuthoritativePartnerKyc(PDO $db, int $partnerId): ?array {
    if (!$partnerId) return null;

    $stmt = $db->prepare("SELECT pka.*, p.name as partner_company_name, p.tin as partner_tin, p.registration_number as partner_reg_no
        FROM partner_kyc_applications pka
        JOIN partners p ON p.id = pka.partner_id
        WHERE pka.partner_id = ?
        ORDER BY 
            CASE pka.status 
                WHEN 'Approved' THEN 1 
                WHEN 'Submitted for Approval' THEN 2 
                WHEN 'Under Review' THEN 3 
                WHEN 'Submitted' THEN 4 
                WHEN 'Draft' THEN 5 
                ELSE 6 
            END,
            pka.updated_at DESC
        LIMIT 1");
    $stmt->execute([$partnerId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kyc) {
        $pStmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $pStmt->execute([$partnerId]);
        $partner = $pStmt->fetch(PDO::FETCH_ASSOC);
        if (!$partner) return null;

        return [
            'id'                     => null,
            'partner_id'             => $partnerId,
            'status'                 => 'Missing KYC',
            'company_name'           => trim($partner['name'] ?? ''),
            'tech_contact_name'      => '',
            'tech_contact_phone'     => '',
            'tech_contact_email'     => '',
            'billing_contact_name'   => '',
            'billing_contact_phone'  => '',
            'billing_contact_email'  => '',
            'auth_signatory_name'    => '',
            'auth_signatory_mobile'  => '',
            'auth_signatory_email'   => '',
            'is_complete'            => false,
            'missing_fields'         => ['Technical Contact Name', 'Technical Contact Phone', 'Technical Contact Email', 'Billing Contact Name', 'Billing Contact Phone', 'Billing Contact Email']
        ];
    }

    // 1. Company Name
    $companyName = trim(($kyc['registered_name'] ?? '') ?: (($kyc['trading_name'] ?? '') ?: (($kyc['partner_company_name'] ?? '') ?: '')));

    // 2. Technical Contact (tech_supervisor_* or tech_contact_*)
    $techName  = trim(($kyc['tech_supervisor_name'] ?? '') ?: (($kyc['tech_contact_name'] ?? '') ?: ''));
    $techPhone = trim(($kyc['tech_supervisor_phone'] ?? '') ?: (($kyc['tech_contact_mobile'] ?? '') ?: (($kyc['tech_contact_phone'] ?? '') ?: '')));
    $techEmail = trim(($kyc['tech_supervisor_email'] ?? '') ?: (($kyc['tech_contact_email'] ?? '') ?: ''));

    // 3. Billing Contact (billing_contact_* or finance_contact_*)
    $billingName  = trim(($kyc['billing_contact_name'] ?? '') ?: (($kyc['finance_contact_name'] ?? '') ?: ''));
    $billingPhone = trim(($kyc['billing_contact_phone'] ?? '') ?: (($kyc['finance_contact_mobile'] ?? '') ?: (($kyc['finance_contact_phone'] ?? '') ?: '')));
    $billingEmail = trim(($kyc['billing_contact_email'] ?? '') ?: (($kyc['finance_contact_email'] ?? '') ?: (($kyc['billing_email'] ?? '') ?: '')));

    // Validate completeness
    $missing = [];
    if (!$companyName)  $missing[] = 'Company Name';
    if (!$techName)     $missing[] = 'Technical Contact Name';
    if (!$techPhone)    $missing[] = 'Technical Contact Phone';
    if (!$techEmail)    $missing[] = 'Technical Contact Email';
    if (!$billingName)  $missing[] = 'Billing Contact Name';
    if (!$billingPhone) $missing[] = 'Billing Contact Phone';
    if (!$billingEmail) $missing[] = 'Billing Contact Email';

    return [
        'id'                     => (int)$kyc['id'],
        'partner_id'             => (int)$kyc['partner_id'],
        'status'                 => $kyc['status'],
        'company_name'           => $companyName,
        'tech_contact_name'      => $techName,
        'tech_contact_phone'     => $techPhone,
        'tech_contact_email'     => $techEmail,
        'billing_contact_name'   => $billingName,
        'billing_contact_phone'  => $billingPhone,
        'billing_contact_email'  => $billingEmail,
        'auth_signatory_name'    => trim($kyc['auth_signatory_name'] ?? ''),
        'auth_signatory_mobile'  => trim($kyc['auth_signatory_mobile'] ?? ''),
        'auth_signatory_email'   => trim($kyc['auth_signatory_email'] ?? ''),
        'is_complete'            => empty($missing),
        'missing_fields'         => $missing,
        'raw'                    => $kyc
    ];
}

/**
 * Ensure database schema for Survey Management, Designated Field Teams, and Installation tracking.
 */
function ensureSurveysSchema(PDO $db): void {
    static $ensured = false;
    if ($ensured) return;

    try {
        // 1. Ensure contractor_teams
        $db->exec("CREATE TABLE IF NOT EXISTS `contractor_teams` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `contractor_partner_id` int UNSIGNED NOT NULL,
            `team_name` varchar(150) NOT NULL,
            `leader_name` varchar(150) DEFAULT NULL,
            `leader_phone` varchar(50) DEFAULT NULL,
            `coverage_region` varchar(150) DEFAULT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'Approved',
            `created_by` int UNSIGNED DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `approved_by` int UNSIGNED DEFAULT NULL,
            `approved_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_contractor` (`contractor_partner_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Ensure contractor_technicians
        $db->exec("CREATE TABLE IF NOT EXISTS `contractor_technicians` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `contractor_partner_id` int UNSIGNED NOT NULL,
            `team_id` int UNSIGNED DEFAULT NULL,
            `full_name` varchar(200) NOT NULL,
            `nida_number` varchar(100) NOT NULL,
            `gender` enum('Male','Female') DEFAULT 'Male',
            `phone` varchar(50) NOT NULL,
            `email` varchar(200) DEFAULT NULL,
            `photo_path` varchar(500) DEFAULT NULL,
            `nida_photo_path` varchar(500) DEFAULT NULL,
            `skill_level` varchar(100) DEFAULT 'Technician',
            `status` varchar(50) NOT NULL DEFAULT 'Approved',
            `rejection_reason` text DEFAULT NULL,
            `created_by` int UNSIGNED DEFAULT NULL,
            `approved_by` int UNSIGNED DEFAULT NULL,
            `approved_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_team` (`team_id`),
            KEY `idx_contractor` (`contractor_partner_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. Ensure survey_tasks
        $db->exec("CREATE TABLE IF NOT EXISTS `survey_tasks` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `survey_number` varchar(50) NOT NULL UNIQUE,
            `order_id` int UNSIGNED DEFAULT NULL,
            `customer_name` varchar(200) NOT NULL,
            `customer_contact_name` varchar(200) DEFAULT NULL,
            `customer_contact_phone` varchar(50) DEFAULT NULL,
            `customer_contact_email` varchar(200) DEFAULT NULL,
            `service_type` varchar(100) NOT NULL DEFAULT 'FTTH',
            `site_location` varchar(255) NOT NULL,
            `building_name` varchar(200) DEFAULT NULL,
            `floor_number` varchar(50) DEFAULT NULL,
            `apartment_number` varchar(50) DEFAULT NULL,
            `city_region` varchar(100) DEFAULT NULL,
            `gps_coordinates` varchar(100) DEFAULT NULL,
            `contractor_partner_id` int UNSIGNED NOT NULL,
            `contractor_team_id` int UNSIGNED NOT NULL,
            `assigned_by` int UNSIGNED NOT NULL,
            `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `target_date` date DEFAULT NULL,
            `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
            `scope_notes` text DEFAULT NULL,
            `status` enum('Assigned','Accepted','In Progress','Completed','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Assigned',
            `accepted_by` int UNSIGNED DEFAULT NULL,
            `accepted_at` datetime DEFAULT NULL,
            `survey_date` date DEFAULT NULL,
            `completed_by` int UNSIGNED DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `feasibility_result` enum('Feasible','Feasible with Extension/Civils','Not Feasible') DEFAULT NULL,
            `fiber_distance_meters` decimal(10,2) DEFAULT NULL,
            `nearest_dp_or_splitter` varchar(150) DEFAULT NULL,
            `cable_route_type` enum('Aerial','Underground','Hybrid') DEFAULT NULL,
            `civil_works_required` tinyint(1) NOT NULL DEFAULT '0',
            `civil_works_details` text DEFAULT NULL,
            `power_available` varchar(50) DEFAULT NULL,
            `building_access_status` varchar(100) DEFAULT NULL,
            `surveyor_notes` text DEFAULT NULL,
            `reviewed_by` int UNSIGNED DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `pm_review_remarks` text DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_survey_number` (`survey_number`),
            KEY `idx_order` (`order_id`),
            KEY `idx_team` (`contractor_team_id`),
            KEY `idx_contractor` (`contractor_partner_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 4. Ensure survey_evidence
        $db->exec("CREATE TABLE IF NOT EXISTS `survey_evidence` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `survey_id` int UNSIGNED NOT NULL,
            `file_type` varchar(100) NOT NULL,
            `file_name` varchar(255) NOT NULL,
            `file_path` varchar(500) NOT NULL,
            `file_size` int UNSIGNED DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `uploaded_by` int UNSIGNED NOT NULL,
            `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_survey` (`survey_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 5. Ensure survey_timeline
        $db->exec("CREATE TABLE IF NOT EXISTS `survey_timeline` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `survey_id` int UNSIGNED NOT NULL,
            `status` varchar(100) NOT NULL,
            `note` text NOT NULL,
            `changed_by` int UNSIGNED NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_survey_time` (`survey_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 6. Ensure team columns on contractor_assignments
        $caCols = $db->query("SHOW COLUMNS FROM contractor_assignments")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('contractor_team_id', $caCols)) {
            $db->exec("ALTER TABLE contractor_assignments ADD COLUMN contractor_team_id INT UNSIGNED NULL");
        }
        if (!in_array('contractor_team_name', $caCols)) {
            $db->exec("ALTER TABLE contractor_assignments ADD COLUMN contractor_team_name VARCHAR(150) NULL");
        }
        if (!in_array('pickup_location', $caCols)) {
            $db->exec("ALTER TABLE contractor_assignments ADD COLUMN pickup_location VARCHAR(255) NULL");
        }

        // 7. Ensure team columns on orders
        $ordCols = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('contractor_team_id', $ordCols)) {
            $db->exec("ALTER TABLE orders ADD COLUMN contractor_team_id INT UNSIGNED NULL");
        }
        if (!in_array('contractor_team_name', $ordCols)) {
            $db->exec("ALTER TABLE orders ADD COLUMN contractor_team_name VARCHAR(150) NULL");
        }
        if (!in_array('installation_date', $ordCols)) {
            $db->exec("ALTER TABLE orders ADD COLUMN installation_date DATE NULL");
        }

        // 8. Auto-seed default approved teams if none exist
        $teamCount = (int)$db->query("SELECT COUNT(*) FROM contractor_teams")->fetchColumn();
        if ($teamCount === 0) {
            $contractorPartnerId = (int)$db->query("SELECT id FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor' LIMIT 1")->fetchColumn();
            if (!$contractorPartnerId) {
                // Create a default contractor organization if missing
                $db->exec("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, city_region, country, created_at)
                    VALUES ('Fiber Works Ltd', 'FiberWorks', 'Contractor', 'Contractor', 'Active', 'Dar es Salaam', 'Tanzania', NOW())");
                $contractorPartnerId = (int)$db->lastInsertId();
            }

            $adminId = (int)($db->query("SELECT id FROM users WHERE role IN ('System Admin', 'Admin', 'Project Manager') LIMIT 1")->fetchColumn() ?: 1);

            $teamsToCreate = [
                [
                    'name'   => 'Alpha Field Squad (Dar Central)',
                    'leader' => 'Juma Bakari',
                    'phone'  => '+255 711 223 344',
                    'region' => 'Ilala / CBD / Posta'
                ],
                [
                    'name'   => 'Coastal Fiber Crew (Kinondoni)',
                    'leader' => 'Hamisi Rajabu',
                    'phone'  => '+255 754 889 911',
                    'region' => 'Kinondoni / Mikocheni / Masaki'
                ],
                [
                    'name'   => 'Apex FastTrack Splicers',
                    'leader' => 'Baraka Mwenda',
                    'phone'  => '+255 788 334 455',
                    'region' => 'Ubungo / Mwenge / Sinza'
                ]
            ];

            foreach ($teamsToCreate as $t) {
                $db->prepare("INSERT INTO contractor_teams (contractor_partner_id, team_name, leader_name, leader_phone, coverage_region, status, created_by, approved_by, approved_at, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Approved', ?, ?, NOW(), NOW())")
                   ->execute([$contractorPartnerId, $t['name'], $t['leader'], $t['phone'], $t['region'], $adminId, $adminId]);
                $newTeamId = (int)$db->lastInsertId();

                // Add technicians
                $db->prepare("INSERT INTO contractor_technicians (contractor_partner_id, team_id, full_name, nida_number, gender, phone, skill_level, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, 'Male', ?, 'Senior Technician', 'Approved', ?, NOW())")
                   ->execute([$contractorPartnerId, $newTeamId, $t['leader'], '19900101-12345-0000' . $newTeamId, $t['phone'], $adminId]);
                $db->prepare("INSERT INTO contractor_technicians (contractor_partner_id, team_id, full_name, nida_number, gender, phone, skill_level, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, 'Male', ?, 'Fiber Splicer', 'Approved', ?, NOW())")
                   ->execute([$contractorPartnerId, $newTeamId, 'Kelvin S. Mushi', '19940512-54321-0000' . $newTeamId, '+255 712 998 87' . $newTeamId, $adminId]);
            }
        }

        // 9. Auto-seed sample surveys if table is empty
        $surveyCount = (int)$db->query("SELECT COUNT(*) FROM survey_tasks")->fetchColumn();
        if ($surveyCount === 0) {
            $pmUserId = (int)($db->query("SELECT id FROM users WHERE role = 'Project Manager' LIMIT 1")->fetchColumn() ?: 11);
            $teams = $db->query("SELECT id, contractor_partner_id, team_name FROM contractor_teams ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($teams)) {
                $team1 = $teams[0];
                $team2 = $teams[1] ?? $teams[0];
                $team3 = $teams[2] ?? $teams[0];

                // Survey 1: Completed & Feasible
                $db->prepare("INSERT INTO survey_tasks (
                    survey_number, order_id, customer_name, customer_contact_name, customer_contact_phone,
                    service_type, site_location, building_name, city_region, gps_coordinates,
                    contractor_partner_id, contractor_team_id, assigned_by, assigned_at, target_date,
                    priority, scope_notes, status, accepted_by, accepted_at, survey_date, completed_by, completed_at,
                    feasibility_result, fiber_distance_meters, nearest_dp_or_splitter, cable_route_type,
                    civil_works_required, civil_works_details, power_available, building_access_status, surveyor_notes,
                    reviewed_by, reviewed_at, pm_review_remarks, created_at
                ) VALUES (
                    'SRV-261015-001', 1, 'Twiga Cement Corporate HQ', 'Emmanuel Mushi', '+255 754 112 233',
                    'Dedicated Layer 2', 'Bagamoyo Road, Tegeta Wazo', 'Twiga Industrial Complex Block B', 'Dar es Salaam', '-6.6632, 39.1620',
                    ?, ?, ?, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY),
                    'High', 'Verify route clearance from main DP-04 to server room. Note rack space and power feed availability.',
                    'Approved', ?, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), ?, DATE_SUB(NOW(), INTERVAL 4 DAY),
                    'Feasible', 145.50, 'DP-TEGETA-04 (Port 3 Available)', 'Aerial',
                    0, 'No heavy civil works needed. Pre-existing cable tray from pole to server room is in good order.',
                    'AC 220V + UPS backup', 'Easy Access (Security gate sign-in only)', 'Client IT manager provided server room access. Signal path tested clean with OTDR. Fiber patch panel has 4 free duplex ports.',
                    ?, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Site survey confirmed feasible per Neilos network standards. Technical scope approved.', DATE_SUB(NOW(), INTERVAL 5 DAY)
                )")->execute([$team1['contractor_partner_id'], $team1['id'], $pmUserId, $pmUserId, $pmUserId, $pmUserId]);

                $s1Id = (int)$db->lastInsertId();
                $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES
                    (?, 'Assigned', 'Survey task assigned to team " . addslashes($team1['team_name']) . " by Project Manager.', ?, DATE_SUB(NOW(), INTERVAL 5 DAY)),
                    (?, 'Accepted', 'Survey accepted by team leader.', ?, DATE_SUB(NOW(), INTERVAL 5 DAY)),
                    (?, 'In Progress', 'Team dispatched to site for physical survey and route tracing.', ?, DATE_SUB(NOW(), INTERVAL 4 DAY)),
                    (?, 'Completed', 'Survey findings and route report submitted by surveyor.', ?, DATE_SUB(NOW(), INTERVAL 4 DAY)),
                    (?, 'Approved', 'Project Manager reviewed and approved site survey feasibility report.', ?, DATE_SUB(NOW(), INTERVAL 3 DAY))
                ")->execute([$s1Id, $pmUserId, $s1Id, $pmUserId, $s1Id, $pmUserId, $s1Id, $pmUserId, $s1Id, $pmUserId]);

                // Survey 2: In Progress
                $db->prepare("INSERT INTO survey_tasks (
                    survey_number, order_id, customer_name, customer_contact_name, customer_contact_phone,
                    service_type, site_location, building_name, city_region, gps_coordinates,
                    contractor_partner_id, contractor_team_id, assigned_by, assigned_at, target_date,
                    priority, scope_notes, status, accepted_by, accepted_at, created_at
                ) VALUES (
                    'SRV-261015-002', NULL, 'Kilimanjaro Logistics Hub', 'Salim Said', '+255 784 990 011',
                    'DIA (Dedicated Internet Access)', 'Nyerere Road, Industrial Area Plot 42', 'Kilimanjaro Logistics Main Depot', 'Dar es Salaam', '-6.8521, 39.2415',
                    ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY),
                    'Urgent', 'Conduct detailed site survey for DIA 50Mbps. Check trenching requirements across the entry gate tarmac.',
                    'In Progress', ?, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
                )")->execute([$team2['contractor_partner_id'], $team2['id'], $pmUserId, $pmUserId]);

                $s2Id = (int)$db->lastInsertId();
                $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES
                    (?, 'Assigned', 'Survey task assigned to team " . addslashes($team2['team_name']) . " by Project Manager.', ?, DATE_SUB(NOW(), INTERVAL 1 DAY)),
                    (?, 'Accepted', 'Team accepted task.', ?, DATE_SUB(NOW(), INTERVAL 1 DAY)),
                    (?, 'In Progress', 'Surveyors on site verifying ODF location and trenching route.', ?, NOW())
                ")->execute([$s2Id, $pmUserId, $s2Id, $pmUserId, $s2Id, $pmUserId]);

                // Survey 3: Newly Assigned
                $db->prepare("INSERT INTO survey_tasks (
                    survey_number, order_id, customer_name, customer_contact_name, customer_contact_phone,
                    service_type, site_location, building_name, city_region, gps_coordinates,
                    contractor_partner_id, contractor_team_id, assigned_by, assigned_at, target_date,
                    priority, scope_notes, status, created_at
                ) VALUES (
                    'SRV-261015-003', NULL, 'Victoria Medical Plaza', 'Dr. Grace Mrema', '+255 713 445 566',
                    'FTTB', 'New Bagamoyo Road, Victoria', 'Victoria Medical Plaza 4th Floor', 'Dar es Salaam', '-6.7790, 39.2530',
                    ?, ?, ?, NOW(), DATE_ADD(CURDATE(), INTERVAL 2 DAY),
                    'Medium', 'Inspect MDU riser and vertical cabling ducts. Determine floor distribution box placement.',
                    'Assigned', NOW()
                )")->execute([$team3['contractor_partner_id'], $team3['id'], $pmUserId]);

                $s3Id = (int)$db->lastInsertId();
                $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES
                    (?, 'Assigned', 'Survey task assigned to team " . addslashes($team3['team_name']) . " by Project Manager.', ?, NOW())
                ")->execute([$s3Id, $pmUserId]);

                // Survey 4: Completed with Extension / Civils
                $db->prepare("INSERT INTO survey_tasks (
                    survey_number, order_id, customer_name, customer_contact_name, customer_contact_phone,
                    service_type, site_location, building_name, city_region, gps_coordinates,
                    contractor_partner_id, contractor_team_id, assigned_by, assigned_at, target_date,
                    priority, scope_notes, status, accepted_by, accepted_at, survey_date, completed_by, completed_at,
                    feasibility_result, fiber_distance_meters, nearest_dp_or_splitter, cable_route_type,
                    civil_works_required, civil_works_details, power_available, building_access_status, surveyor_notes,
                    reviewed_by, reviewed_at, pm_review_remarks, created_at
                ) VALUES (
                    'SRV-261015-004', NULL, 'Savanna Microfinance HQ', 'Peter Temu', '+255 767 889 900',
                    'FTTH', 'Sam Nujoma Road, Mwenge', 'Savanna Commercial Centre Ground Floor', 'Dar es Salaam', '-6.7845, 39.2312',
                    ?, ?, ?, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY),
                    'Medium', 'Perform feasibility assessment for FTTH last-mile termination.',
                    'Completed', ?, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), ?, DATE_SUB(NOW(), INTERVAL 6 DAY),
                    'Feasible with Extension/Civils', 280.00, 'DP-MWENGE-12 (Distance > 200m)', 'Hybrid',
                    1, 'Requires 45m road cut and micro-trenching under tarmac driveway to reach building perimeter.',
                    'AC 220V', 'Building permit required for trenching across front car park',
                    'Signal at DP is strong (-17.2 dBm). 2 poles needed to avoid sag across driveway or road cut permit from municipal council.',
                    NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 7 DAY)
                )")->execute([$team1['contractor_partner_id'], $team1['id'], $pmUserId, $pmUserId, $pmUserId]);
            }
        }

        // 10. Update existing orders with contractor_team_id if missing
        $sampleTeam = $db->query("SELECT id, team_name FROM contractor_teams LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sampleTeam) {
            $db->prepare("UPDATE orders SET contractor_team_id = ?, contractor_team_name = ?, installation_date = COALESCE(installation_date, CURDATE()) WHERE contractor_team_id IS NULL AND status IN ('Installation', 'Testing', 'UAT', 'Closed')")
               ->execute([$sampleTeam['id'], $sampleTeam['team_name']]);
            $db->prepare("UPDATE contractor_assignments SET contractor_team_id = ?, contractor_team_name = ? WHERE contractor_team_id IS NULL")
               ->execute([$sampleTeam['id'], $sampleTeam['team_name']]);
        }

        $ensured = true;
    } catch (Throwable $e) {
        error_log("ensureSurveysSchema error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    }
}

