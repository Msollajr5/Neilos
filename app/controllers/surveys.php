<?php
// ============================================================
// Survey Management & Team Assignment Controller — v1.0
// Neilos Partner Portal
// Manages:
// - PM survey task creation & assignment to designated field teams
// - Field team allocation view, task acceptance, and progress tracking
// - On-site survey data collection & technical findings submission
// - Surveys conducted reporting per team
// - Installations completed tracking per team
// - PM field team activity monitoring hub & performance analytics
// ============================================================
requireLogin();

$db   = getDB();
$user = currentUser();
$action = $_GET['action'] ?? 'list';

// Initialize schema and migrations
ensureSurveysSchema($db);

$isPM = isProjectManager();
$isAdminUser = isAdmin() || hasRole('Management');
$isContractor = isContractorUser();
$partnerId = $user['partner_id'] ?? null;

// Access guard
if (!$isPM && !$isAdminUser && !$isContractor && !hasPermission('surveys.view')) {
    setFlash('danger', 'Access denied.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

// Helper: generate unique survey reference number
function generateSurveyNumber(PDO $db): string {
    $datePart = date('ymd');
    $prefix = "SRV-{$datePart}-";
    $stmt = $db->prepare("SELECT survey_number FROM survey_tasks WHERE survey_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        $lastSeq = (int)substr($last, strlen($prefix));
        $nextSeq = str_pad((string)($lastSeq + 1), 3, '0', STR_PAD_LEFT);
    } else {
        $nextSeq = '001';
    }
    return $prefix . $nextSeq;
}

// ------------------------------------------------------------------
// POST: Assign New Survey Task (Project Manager / Admin)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();
    if (!$isPM && !$isAdminUser && !hasPermission('surveys.assign')) {
        setFlash('danger', 'Only Project Manager or Administrator can assign survey tasks.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $teamId           = (int)($_POST['contractor_team_id'] ?? 0);
    $orderId          = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $customerName     = trim($_POST['customer_name'] ?? '');
    $contactName      = trim($_POST['customer_contact_name'] ?? '');
    $contactPhone     = trim($_POST['customer_contact_phone'] ?? '');
    $contactEmail     = trim($_POST['customer_contact_email'] ?? '');
    $serviceType      = trim($_POST['service_type'] ?? 'FTTH');
    $siteLocation     = trim($_POST['site_location'] ?? '');
    $buildingName     = trim($_POST['building_name'] ?? '');
    $floorNumber      = trim($_POST['floor_number'] ?? '');
    $apartmentNumber  = trim($_POST['apartment_number'] ?? '');
    $cityRegion       = trim($_POST['city_region'] ?? 'Dar es Salaam');
    $gpsCoordinates   = trim($_POST['gps_coordinates'] ?? '');
    $targetDate       = trim($_POST['target_date'] ?? '');
    $priority         = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High', 'Urgent']) ? $_POST['priority'] : 'Medium';
    $scopeNotes       = trim($_POST['scope_notes'] ?? '');

    // Validation
    if (!$teamId) {
        setFlash('danger', 'Please select a designated field team.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }
    if (!$customerName || !$siteLocation) {
        setFlash('danger', 'Customer Name and Site Location are required.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    // Fetch team & contractor partner ID
    $teamStmt = $db->prepare("SELECT ct.*, p.name as contractor_name FROM contractor_teams ct JOIN partners p ON ct.contractor_partner_id = p.id WHERE ct.id = ?");
    $teamStmt->execute([$teamId]);
    $team = $teamStmt->fetch();
    if (!$team) {
        setFlash('danger', 'Selected field team not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    // Auto-fill from Order if orderId provided and customer fields empty
    if ($orderId && (!$contactName || !$siteLocation)) {
        $oStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $oStmt->execute([$orderId]);
        $o = $oStmt->fetch();
        if ($o) {
            $customerName    = $customerName ?: $o['customer_name'];
            $contactName     = $contactName ?: $o['customer_contact_name'];
            $contactPhone    = $contactPhone ?: $o['customer_contact_phone'];
            $contactEmail    = $contactEmail ?: $o['customer_contact_email'];
            $serviceType     = $serviceType ?: $o['service_type'];
            $siteLocation    = $siteLocation ?: $o['customer_location'];
            $buildingName    = $buildingName ?: $o['building_name'];
            $gpsCoordinates  = $gpsCoordinates ?: $o['gps_coordinates'];
        }
    }

    $surveyNumber = generateSurveyNumber($db);

    $insStmt = $db->prepare("INSERT INTO survey_tasks (
        survey_number, order_id, customer_name, customer_contact_name, customer_contact_phone, customer_contact_email,
        service_type, site_location, building_name, floor_number, apartment_number, city_region, gps_coordinates,
        contractor_partner_id, contractor_team_id, assigned_by, assigned_at, target_date, priority, scope_notes, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, 'Assigned', NOW())");

    $insStmt->execute([
        $surveyNumber, $orderId, $customerName, $contactName ?: null, $contactPhone ?: null, $contactEmail ?: null,
        $serviceType, $siteLocation, $buildingName ?: null, $floorNumber ?: null, $apartmentNumber ?: null, $cityRegion,
        $gpsCoordinates ?: null, $team['contractor_partner_id'], $teamId, $user['id'],
        $targetDate ?: null, $priority, $scopeNotes ?: null
    ]);
    $surveyId = (int)$db->lastInsertId();

    // Timeline event
    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, 'Assigned', ?, ?, NOW())")
       ->execute([$surveyId, "Survey task assigned to team '{$team['team_name']}' ({$team['contractor_name']}) by {$user['full_name']}.", $user['id']]);

    // If linked to order, also record in order timeline
    if ($orderId) {
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?, 'Feasibility Review', ?, ?)")
           ->execute([$orderId, "Project Manager assigned site survey ($surveyNumber) to field team '{$team['team_name']}'.", $user['id']]);
    }

    auditLog("Assigned survey #$surveyNumber to field team #$teamId ({$team['team_name']})", 'survey_tasks', $surveyId);
    setFlash('success', "Survey <strong>$surveyNumber</strong> assigned successfully to team <strong>" . e($team['team_name']) . "</strong>.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// POST: Field Team Accepts Survey Task
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'accept') {
    verifyCsrf();
    $surveyId = (int)($_POST['survey_id'] ?? 0);
    $stmt = $db->prepare("SELECT s.*, ct.team_name FROM survey_tasks s JOIN contractor_teams ct ON s.contractor_team_id = ct.id WHERE s.id = ?");
    $stmt->execute([$surveyId]);
    $s = $stmt->fetch();

    if (!$s) {
        setFlash('danger', 'Survey task not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    if ($isContractor && $partnerId && (int)$s['contractor_partner_id'] !== (int)$partnerId) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $db->prepare("UPDATE survey_tasks SET status = 'Accepted', accepted_by = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?")
       ->execute([$user['id'], $surveyId]);

    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, 'Accepted', ?, ?, NOW())")
       ->execute([$surveyId, "Survey task accepted by {$user['full_name']} for team '{$s['team_name']}'.", $user['id']]);

    auditLog("Accepted survey #{$s['survey_number']}", 'survey_tasks', $surveyId);
    setFlash('success', "Survey task accepted. Proceed to site and start survey.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// POST: Field Team Marks Survey In Progress (On Site)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'start') {
    verifyCsrf();
    $surveyId = (int)($_POST['survey_id'] ?? 0);
    $notes    = trim($_POST['start_notes'] ?? '');

    $stmt = $db->prepare("SELECT * FROM survey_tasks WHERE id = ?");
    $stmt->execute([$surveyId]);
    $s = $stmt->fetch();

    if (!$s) {
        setFlash('danger', 'Survey not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $db->prepare("UPDATE survey_tasks SET status = 'In Progress', survey_date = CURDATE(), updated_at = NOW() WHERE id = ?")
       ->execute([$surveyId]);

    $timelineNote = "Field team arrived on site and commenced physical survey." . ($notes ? " Notes: $notes" : "");
    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, 'In Progress', ?, ?, NOW())")
       ->execute([$surveyId, $timelineNote, $user['id']]);

    auditLog("Survey #{$s['survey_number']} marked In Progress", 'survey_tasks', $surveyId);
    setFlash('success', "Survey is now In Progress. Complete on-site inspection and submit technical findings.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// POST: Field Team Submits Survey Findings & Site Evidence
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_findings') {
    verifyCsrf();
    $surveyId             = (int)($_POST['survey_id'] ?? 0);
    $feasibilityResult    = $_POST['feasibility_result'] ?? '';
    $fiberDistance        = !empty($_POST['fiber_distance_meters']) ? (float)$_POST['fiber_distance_meters'] : null;
    $nearestDp            = trim($_POST['nearest_dp_or_splitter'] ?? '');
    $cableRouteType       = in_array($_POST['cable_route_type'] ?? '', ['Aerial', 'Underground', 'Hybrid']) ? $_POST['cable_route_type'] : 'Aerial';
    $civilWorksRequired   = (int)($_POST['civil_works_required'] ?? 0);
    $civilWorksDetails    = trim($_POST['civil_works_details'] ?? '');
    $powerAvailable       = trim($_POST['power_available'] ?? '');
    $buildingAccessStatus = trim($_POST['building_access_status'] ?? '');
    $surveyorNotes        = trim($_POST['surveyor_notes'] ?? '');
    $gpsCoordinates       = trim($_POST['gps_coordinates'] ?? '');

    $stmt = $db->prepare("SELECT s.*, ct.team_name FROM survey_tasks s JOIN contractor_teams ct ON s.contractor_team_id = ct.id WHERE s.id = ?");
    $stmt->execute([$surveyId]);
    $s = $stmt->fetch();

    if (!$s) {
        setFlash('danger', 'Survey task not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    if (!in_array($feasibilityResult, ['Feasible', 'Feasible with Extension/Civils', 'Not Feasible'])) {
        setFlash('danger', 'Please select a valid feasibility verdict.');
        header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
        exit;
    }

    if (!$nearestDp || !$surveyorNotes) {
        setFlash('danger', 'Nearest DP/Splitter ID and Surveyor Technical Notes are required.');
        header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
        exit;
    }

    // Process photo uploads
    $uploadCount = 0;
    if (!empty($_FILES['evidence_files']['name'][0])) {
        $files = $_FILES['evidence_files'];
        $numFiles = count($files['name']);
        for ($i = 0; $i < $numFiles; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    $singleFile = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $up = uploadFile($singleFile, 'surveys/' . $surveyId);
                    $fileType = trim($_POST['evidence_types'][$i] ?? 'Site Photo') ?: 'Site Photo';
                    $fileNote = trim($_POST['evidence_notes'][$i] ?? '') ?: null;

                    $db->prepare("INSERT INTO survey_evidence (survey_id, file_type, file_name, file_path, file_size, notes, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
                       ->execute([$surveyId, $fileType, $up['name'], $up['path'], $up['size'], $fileNote, $user['id']]);
                    $uploadCount++;
                } catch (Throwable $e) {}
            }
        }
    }

    $db->prepare("UPDATE survey_tasks SET
        status = 'Completed',
        completed_by = ?,
        completed_at = NOW(),
        survey_date = COALESCE(survey_date, CURDATE()),
        feasibility_result = ?,
        fiber_distance_meters = ?,
        nearest_dp_or_splitter = ?,
        cable_route_type = ?,
        civil_works_required = ?,
        civil_works_details = ?,
        power_available = ?,
        building_access_status = ?,
        surveyor_notes = ?,
        gps_coordinates = COALESCE(NULLIF(?, ''), gps_coordinates),
        updated_at = NOW()
        WHERE id = ?")->execute([
        $user['id'],
        $feasibilityResult,
        $fiberDistance,
        $nearestDp,
        $cableRouteType,
        $civilWorksRequired,
        $civilWorksDetails ?: null,
        $powerAvailable ?: null,
        $buildingAccessStatus ?: null,
        $surveyorNotes,
        $gpsCoordinates,
        $surveyId
    ]);

    $timelineNote = "Survey completed by {$user['full_name']} (Team: '{$s['team_name']}'). Result: $feasibilityResult." . ($uploadCount > 0 ? " ($uploadCount site photo(s) attached)" : "");
    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, 'Completed', ?, ?, NOW())")
       ->execute([$surveyId, $timelineNote, $user['id']]);

    // If linked to order, also update order timeline
    if (!empty($s['order_id'])) {
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?, 'Feasibility Review', ?, ?)")
           ->execute([$s['order_id'], "Field team '{$s['team_name']}' completed site survey #{$s['survey_number']}. Verdict: $feasibilityResult. Awaiting PM review.", $user['id']]);
    }

    auditLog("Submitted survey findings for #{$s['survey_number']} (Verdict: $feasibilityResult)", 'survey_tasks', $surveyId);
    setFlash('success', "Survey findings submitted successfully. Status updated to <strong>Completed</strong>. Awaiting Project Manager review.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// POST: PM Reviews & Approves / Rejects Survey
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['pm_approve', 'pm_reject'])) {
    verifyCsrf();
    if (!$isPM && !$isAdminUser && !hasPermission('surveys.manage')) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $surveyId    = (int)($_POST['survey_id'] ?? 0);
    $reviewNotes = trim($_POST['pm_review_remarks'] ?? '');
    $syncToOrder = (int)($_POST['sync_to_order'] ?? 0);

    $stmt = $db->prepare("SELECT s.*, ct.team_name FROM survey_tasks s JOIN contractor_teams ct ON s.contractor_team_id = ct.id WHERE s.id = ?");
    $stmt->execute([$surveyId]);
    $s = $stmt->fetch();

    if (!$s) {
        setFlash('danger', 'Survey not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $newStatus = ($action === 'pm_approve') ? 'Approved' : 'Rejected';

    $db->prepare("UPDATE survey_tasks SET status = ?, reviewed_by = ?, reviewed_at = NOW(), pm_review_remarks = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$newStatus, $user['id'], $reviewNotes ?: null, $surveyId]);

    $tNote = "Project Manager {$user['full_name']} marked survey report as $newStatus." . ($reviewNotes ? " Remarks: $reviewNotes" : "");
    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())")
       ->execute([$surveyId, $newStatus, $tNote, $user['id']]);

    // Optional: Synchronize directly with linked Order Feasibility Review
    if ($syncToOrder && !empty($s['order_id']) && $action === 'pm_approve') {
        $orderId = (int)$s['order_id'];
        $technicalResult = ($s['feasibility_result'] === 'Not Feasible') ? 'Technically Not Feasible' : 'Technically Feasible';
        $specialConditions = "Site Survey {$s['survey_number']} verified feasible by Team {$s['team_name']}. Fiber distance: {$s['fiber_distance_meters']}m from nearest DP ({$s['nearest_dp_or_splitter']}). Route: {$s['cable_route_type']}." . ($s['civil_works_required'] ? " Civils required: {$s['civil_works_details']}." : "");

        if ($technicalResult === 'Technically Feasible') {
            $db->prepare("UPDATE orders SET 
                bsa_technical_result = 'Technically Feasible',
                bsa_remarks = ?,
                bsa_special_conditions = ?,
                bsa_reviewed_by = ?,
                bsa_reviewed_at = NOW(),
                status = 'Await Commercial Approval',
                updated_at = NOW()
                WHERE id = ? AND status = 'Feasibility Review'")
               ->execute([$specialConditions, $specialConditions, $user['id'], $orderId]);

            $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?, 'Await Commercial Approval', ?, ?)")
               ->execute([$orderId, "Site survey #{$s['survey_number']} findings approved by PM and synced to order technical feasibility.", $user['id']]);
        }
    }

    auditLog("PM {$newStatus} survey #{$s['survey_number']}", 'survey_tasks', $surveyId);
    setFlash('success', "Survey report marked as <strong>{$newStatus}</strong>.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// POST: PM Reassigns Survey to Different Team
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reassign') {
    verifyCsrf();
    if (!$isPM && !$isAdminUser && !hasPermission('surveys.manage')) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $surveyId    = (int)($_POST['survey_id'] ?? 0);
    $newTeamId   = (int)($_POST['new_contractor_team_id'] ?? 0);
    $reassignNote= trim($_POST['reassign_note'] ?? '');

    $sStmt = $db->prepare("SELECT s.*, ct.team_name as old_team_name FROM survey_tasks s JOIN contractor_teams ct ON s.contractor_team_id = ct.id WHERE s.id = ?");
    $sStmt->execute([$surveyId]);
    $s = $sStmt->fetch();

    $newTeamStmt = $db->prepare("SELECT ct.*, p.name as contractor_name FROM contractor_teams ct JOIN partners p ON ct.contractor_partner_id = p.id WHERE ct.id = ?");
    $newTeamStmt->execute([$newTeamId]);
    $newTeam = $newTeamStmt->fetch();

    if (!$s || !$newTeam) {
        setFlash('danger', 'Survey or target team not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    $db->prepare("UPDATE survey_tasks SET
        contractor_team_id = ?,
        contractor_partner_id = ?,
        status = 'Assigned',
        accepted_by = NULL,
        accepted_at = NULL,
        updated_at = NOW()
        WHERE id = ?")->execute([$newTeamId, $newTeam['contractor_partner_id'], $surveyId]);

    $tNote = "Reassigned from '{$s['old_team_name']}' to '{$newTeam['team_name']}' by {$user['full_name']}." . ($reassignNote ? " Reason: $reassignNote" : "");
    $db->prepare("INSERT INTO survey_timeline (survey_id, status, note, changed_by, created_at) VALUES (?, 'Assigned', ?, ?, NOW())")
       ->execute([$surveyId, $tNote, $user['id']]);

    auditLog("Reassigned survey #{$s['survey_number']} to team #$newTeamId ({$newTeam['team_name']})", 'survey_tasks', $surveyId);
    setFlash('info', "Survey reallocated to team <strong>" . e($newTeam['team_name']) . "</strong>.");
    header('Location: ' . APP_URL . '/?page=surveys&action=detail&id=' . $surveyId);
    exit;
}

// ------------------------------------------------------------------
// GET: CSV Export (Surveys or Team Performance)
// ------------------------------------------------------------------
// GET: CSV Export (Surveys or Team Performance)
// ------------------------------------------------------------------
if ($action === 'export') {
    $exportType = $_GET['export_type'] ?? 'surveys';

    if ($exportType === 'teams') {
        // Export team performance matrix
        $teamScopeWhere = ($isContractor && $partnerId) ? "WHERE ct.contractor_partner_id = " . (int)$partnerId : "";
        $sql = "
            SELECT 
                ct.id as team_id,
                ct.team_name,
                p.name as contractor_name,
                ct.leader_name,
                ct.leader_phone,
                ct.coverage_region,
                ct.status,
                (SELECT COUNT(*) FROM contractor_technicians tech WHERE tech.team_id = ct.id AND tech.status = 'Approved') as technician_count,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id) as total_surveys,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.status = 'In Progress') as surveys_in_progress,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.status IN ('Completed', 'Approved')) as completed_surveys,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Feasible') as feasible_surveys,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Feasible with Extension/Civils') as feasible_extension_surveys,
                (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Not Feasible') as not_feasible_surveys,
                (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_team_id = ct.id AND ca.status IN ('Completed', 'Completed Submitted')) as completed_installations,
                (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_team_id = ct.id AND ca.status IN ('Assigned', 'Accepted', 'In Progress')) as active_installations
            FROM contractor_teams ct
            JOIN partners p ON ct.contractor_partner_id = p.id
            {$teamScopeWhere}
            ORDER BY ct.team_name ASC
        ";
        $data = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="team_performance_report_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel on Windows
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($out, [
            'Team ID', 'Team Name', 'Contractor Org', 'Leader Name', 'Leader Phone', 
            'Coverage Region', 'Status', 'Certified Technicians', 'Total Surveys Assigned', 
            'Surveys in Progress', 'Surveys Conducted', 'Feasible Surveys', 'Feasible with Extension', 
            'Not Feasible', 'Feasibility Rate (%)', 'Completed Installations', 'Active Workload'
        ]);

        foreach ($data as $r) {
            $totalDone = (int)$r['completed_surveys'];
            $feasTotal = (int)$r['feasible_surveys'] + (int)$r['feasible_extension_surveys'];
            $feasRate  = ($totalDone > 0) ? round(($feasTotal / $totalDone) * 100) : 0;
            $activeLoad = (int)$r['surveys_in_progress'] + (int)$r['active_installations'];

            fputcsv($out, [
                $r['team_id'],
                $r['team_name'],
                $r['contractor_name'],
                $r['leader_name'] ?: 'Not assigned',
                $r['leader_phone'] ?: '—',
                $r['coverage_region'] ?: 'General',
                $r['status'],
                $r['technician_count'],
                $r['total_surveys'],
                $r['surveys_in_progress'],
                $r['completed_surveys'],
                $r['feasible_surveys'],
                $r['feasible_extension_surveys'],
                $r['not_feasible_surveys'],
                $feasRate . '%',
                $r['completed_installations'],
                $activeLoad
            ]);
        }
        fclose($out);
        exit;
    } else {
        // Export surveys list with active filters applied and full technical findings
        $statusFilter  = trim($_GET['status'] ?? '');
        $teamFilter    = (int)($_GET['team_id'] ?? 0);
        $priorityFilter= trim($_GET['priority'] ?? '');
        $serviceFilter = trim($_GET['service_type'] ?? '');
        $search        = trim($_GET['search'] ?? '');
        $dateStart     = trim($_GET['date_start'] ?? '');
        $dateEnd       = trim($_GET['date_end'] ?? '');

        $whereConditions = ['1=1'];
        $params = [];

        if ($isContractor && $partnerId) {
            $whereConditions[] = "s.contractor_partner_id = ?";
            $params[] = (int)$partnerId;
        }
        if ($statusFilter !== '') {
            $whereConditions[] = "s.status = ?";
            $params[] = $statusFilter;
        }
        if ($teamFilter > 0) {
            $whereConditions[] = "s.contractor_team_id = ?";
            $params[] = $teamFilter;
        }
        if ($priorityFilter !== '') {
            $whereConditions[] = "s.priority = ?";
            $params[] = $priorityFilter;
        }
        if ($serviceFilter !== '') {
            $whereConditions[] = "s.service_type = ?";
            $params[] = $serviceFilter;
        }
        if ($dateStart !== '') {
            $whereConditions[] = "DATE(s.assigned_at) >= ?";
            $params[] = $dateStart;
        }
        if ($dateEnd !== '') {
            $whereConditions[] = "DATE(s.assigned_at) <= ?";
            $params[] = $dateEnd;
        }
        if ($search !== '') {
            $whereConditions[] = "(s.survey_number LIKE ? OR s.customer_name LIKE ? OR s.site_location LIKE ? OR ct.team_name LIKE ?)";
            $term = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        }

        $whereSql = implode(' AND ', $whereConditions);

        $sql = "
            SELECT 
                s.*,
                o.order_number,
                ct.team_name,
                ct.leader_name,
                ct.leader_phone,
                ct.coverage_region,
                p.name as contractor_name,
                u_assign.full_name as assigned_by_name,
                u_comp.full_name as completed_by_name,
                u_rev.full_name as reviewed_by_name
            FROM survey_tasks s
            LEFT JOIN orders o ON s.order_id = o.id
            LEFT JOIN contractor_teams ct ON s.contractor_team_id = ct.id
            LEFT JOIN partners p ON s.contractor_partner_id = p.id
            LEFT JOIN users u_assign ON s.assigned_by = u_assign.id
            LEFT JOIN users u_comp ON s.completed_by = u_comp.id
            LEFT JOIN users u_rev ON s.reviewed_by = u_rev.id
            WHERE {$whereSql}
            ORDER BY s.id DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="surveys_master_report_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel on Windows
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($out, [
            'Survey #',
            'Linked Order #',
            'Customer Name',
            'Contact Person',
            'Contact Phone',
            'Contact Email',
            'Service Type',
            'Site Physical Location',
            'Building & Floor',
            'City / Region',
            'GPS Coordinates',
            'Designated Team',
            'Contractor Org',
            'Team Lead',
            'Team Lead Phone',
            'Priority',
            'Status',
            'Feasibility Verdict',
            'Measured Fiber Distance (m)',
            'Nearest DP / Splitter ID',
            'Cable Route Type',
            'Civil Works Required',
            'Civil Works Details',
            'Power Available at Site',
            'Building Access Status',
            'Target Due Date',
            'Assigned Date',
            'Assigned By',
            'Survey Date',
            'Completed Date',
            'Completed By',
            'Surveyor Technical Notes & Observations',
            'PM Review Remarks',
            'PM Reviewed By',
            'PM Review Date',
            'PM Scope Instructions'
        ]);

        foreach ($data as $r) {
            $building = trim(($r['building_name'] ?? '') . ($r['floor_number'] ? ' (Floor ' . $r['floor_number'] . ')' : ''));
            $civils = !empty($r['civil_works_required']) ? 'Yes' : 'No';

            fputcsv($out, [
                $r['survey_number'] ?? '',
                !empty($r['order_number']) ? $r['order_number'] : (!empty($r['order_id']) ? '#' . $r['order_id'] : 'Standalone'),
                $r['customer_name'] ?? '',
                $r['customer_contact_name'] ?? '',
                $r['customer_contact_phone'] ?? '',
                $r['customer_contact_email'] ?? '',
                $r['service_type'] ?? '',
                $r['site_location'] ?? '',
                $building ?: '—',
                $r['city_region'] ?: 'Dar es Salaam',
                $r['gps_coordinates'] ?: '—',
                $r['team_name'] ?: 'General Field Team',
                $r['contractor_name'] ?: '—',
                $r['leader_name'] ?: 'Not assigned',
                $r['leader_phone'] ?: '—',
                $r['priority'] ?? 'Medium',
                $r['status'] ?? 'Assigned',
                $r['feasibility_result'] ?: 'Pending Site Visit',
                !is_null($r['fiber_distance_meters']) && $r['fiber_distance_meters'] !== '' ? number_format((float)$r['fiber_distance_meters'], 1) : '—',
                $r['nearest_dp_or_splitter'] ?: '—',
                $r['cable_route_type'] ?: 'Aerial',
                $civils,
                $r['civil_works_details'] ?: '—',
                $r['power_available'] ?: '—',
                $r['building_access_status'] ?: '—',
                !empty($r['target_date']) ? date('Y-m-d', strtotime($r['target_date'])) : '—',
                !empty($r['assigned_at']) ? date('Y-m-d H:i', strtotime($r['assigned_at'])) : '—',
                $r['assigned_by_name'] ?: 'Project Manager',
                !empty($r['survey_date']) ? date('Y-m-d', strtotime($r['survey_date'])) : '—',
                !empty($r['completed_at']) ? date('Y-m-d H:i', strtotime($r['completed_at'])) : '—',
                $r['completed_by_name'] ?: '—',
                $r['surveyor_notes'] ?: '—',
                $r['pm_review_remarks'] ?: '—',
                $r['reviewed_by_name'] ?: '—',
                !empty($r['reviewed_at']) ? date('Y-m-d H:i', strtotime($r['reviewed_at'])) : '—',
                $r['scope_notes'] ?: '—'
            ]);
        }
        fclose($out);
        exit;
    }
}

// ------------------------------------------------------------------
// GET: Survey Detail View
// ------------------------------------------------------------------
if ($action === 'detail') {
    $surveyId = (int)($_GET['id'] ?? 0);

    $stmt = $db->prepare("
        SELECT 
            s.*,
            ct.team_name, ct.leader_name, ct.leader_phone, ct.coverage_region, ct.status as team_status,
            p.name as contractor_name, p.trading_name as contractor_trading_name,
            u_assign.full_name as assigned_by_name,
            u_accept.full_name as accepted_by_name,
            u_comp.full_name as completed_by_name,
            u_rev.full_name as reviewed_by_name,
            o.order_number, o.status as order_status, o.customer_location as order_location
        FROM survey_tasks s
        JOIN contractor_teams ct ON s.contractor_team_id = ct.id
        JOIN partners p ON s.contractor_partner_id = p.id
        LEFT JOIN users u_assign ON s.assigned_by = u_assign.id
        LEFT JOIN users u_accept ON s.accepted_by = u_accept.id
        LEFT JOIN users u_comp ON s.completed_by = u_comp.id
        LEFT JOIN users u_rev ON s.reviewed_by = u_rev.id
        LEFT JOIN orders o ON s.order_id = o.id
        WHERE s.id = ?
    ");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();

    if (!$survey) {
        setFlash('danger', 'Survey not found.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    if ($isContractor && $partnerId && (int)$survey['contractor_partner_id'] !== (int)$partnerId) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=surveys');
        exit;
    }

    // Evidence / Photos
    $evStmt = $db->prepare("SELECT se.*, u.full_name as uploader_name FROM survey_evidence se LEFT JOIN users u ON se.uploaded_by = u.id WHERE se.survey_id = ? ORDER BY se.id ASC");
    $evStmt->execute([$surveyId]);
    $evidenceFiles = $evStmt->fetchAll();

    // Timeline events
    $timeStmt = $db->prepare("SELECT st.*, u.full_name as actor_name FROM survey_timeline st LEFT JOIN users u ON st.changed_by = u.id WHERE st.survey_id = ? ORDER BY st.id ASC");
    $timeStmt->execute([$surveyId]);
    $timeline = $timeStmt->fetchAll();

    // Available teams for reassign (PM / Admin only)
    $allTeams = [];
    if ($isPM || $isAdminUser) {
        $allTeams = $db->query("SELECT ct.*, p.name as contractor_name FROM contractor_teams ct JOIN partners p ON ct.contractor_partner_id = p.id WHERE ct.status = 'Approved' ORDER BY ct.team_name ASC")->fetchAll();
    }

    $pageTitle = "Survey Task — " . e($survey['survey_number']);
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/surveys/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// GET: Surveys Management Hub & Team Monitoring (Default List View)
// ------------------------------------------------------------------
$activeTab     = $_GET['tab'] ?? 'surveys'; // 'surveys', 'monitoring', 'surveys_by_team', 'installations_by_team', 'activity'
$statusFilter  = trim($_GET['status'] ?? '');
$teamFilter    = (int)($_GET['team_id'] ?? 0);
$priorityFilter= trim($_GET['priority'] ?? '');
$serviceFilter = trim($_GET['service_type'] ?? '');
$search        = trim($_GET['search'] ?? '');
$dateStart     = trim($_GET['date_start'] ?? '');
$dateEnd       = trim($_GET['date_end'] ?? '');

// Base query for surveys
$whereConditions = ['1=1'];
$params = [];

if ($isContractor && $partnerId) {
    $whereConditions[] = "s.contractor_partner_id = ?";
    $params[] = (int)$partnerId;
}

if ($statusFilter !== '') {
    $whereConditions[] = "s.status = ?";
    $params[] = $statusFilter;
}

if ($teamFilter > 0) {
    $whereConditions[] = "s.contractor_team_id = ?";
    $params[] = $teamFilter;
}

if ($priorityFilter !== '') {
    $whereConditions[] = "s.priority = ?";
    $params[] = $priorityFilter;
}

if ($serviceFilter !== '') {
    $whereConditions[] = "s.service_type = ?";
    $params[] = $serviceFilter;
}

if ($dateStart !== '') {
    $whereConditions[] = "DATE(s.assigned_at) >= ?";
    $params[] = $dateStart;
}

if ($dateEnd !== '') {
    $whereConditions[] = "DATE(s.assigned_at) <= ?";
    $params[] = $dateEnd;
}

if ($search !== '') {
    $whereConditions[] = "(s.survey_number LIKE ? OR s.customer_name LIKE ? OR s.site_location LIKE ? OR ct.team_name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $whereConditions);

// Fetch Surveys
$surveysStmt = $db->prepare("
    SELECT 
        s.*,
        ct.team_name, ct.leader_name, ct.leader_phone, ct.coverage_region,
        p.name as contractor_name,
        (SELECT COUNT(*) FROM survey_evidence se WHERE se.survey_id = s.id) as evidence_count
    FROM survey_tasks s
    JOIN contractor_teams ct ON s.contractor_team_id = ct.id
    JOIN partners p ON s.contractor_partner_id = p.id
    WHERE {$whereSql}
    ORDER BY s.id DESC
");
$surveysStmt->execute($params);
$surveys = $surveysStmt->fetchAll();

// KPI Summary counts
if ($isContractor && $partnerId) {
    $pId = (int)$partnerId;
    $countStmt = $db->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN feasibility_result = 'Feasible' THEN 1 ELSE 0 END) as feasible
    FROM survey_tasks WHERE contractor_partner_id = ?");
    $countStmt->execute([$pId]);
    $rawKpis = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];
} else {
    $rawKpis = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN feasibility_result = 'Feasible' THEN 1 ELSE 0 END) as feasible
    FROM survey_tasks")->fetch(PDO::FETCH_ASSOC) ?: [];
}

$kpiCounts = [
    'total'       => (int)($rawKpis['total'] ?? 0),
    'assigned'    => (int)($rawKpis['assigned'] ?? 0),
    'in_progress' => (int)($rawKpis['in_progress'] ?? 0),
    'completed'   => (int)($rawKpis['completed'] ?? 0),
    'approved'    => (int)($rawKpis['approved'] ?? 0),
    'feasible'    => (int)($rawKpis['feasible'] ?? 0),
];

// All available teams for filter dropdown and assignment
$teamsQuery = "SELECT ct.*, p.name as contractor_name FROM contractor_teams ct JOIN partners p ON ct.contractor_partner_id = p.id WHERE ct.status = 'Approved'";
if ($isContractor && $partnerId) {
    $teamsQuery .= " AND ct.contractor_partner_id = " . (int)$partnerId;
}
$teamsQuery .= " ORDER BY ct.team_name ASC";
$teamsList = $db->query($teamsQuery)->fetchAll();

// Orders in Feasibility Review (for linking surveys)
$ordersForSurvey = [];
if ($isPM || $isAdminUser) {
    $ordersForSurvey = $db->query("SELECT id, order_number, customer_name, customer_location, service_type FROM orders WHERE status = 'Feasibility Review' ORDER BY id DESC LIMIT 50")->fetchAll();
}

// ------------------------------------------------------------------
// Per-Team Comprehensive Activity & Performance Metrics
// Surveys Conducted + Installations Completed
// ------------------------------------------------------------------
$teamMetrics = [];
$teamScopeWhere = ($isContractor && $partnerId) ? "WHERE ct.contractor_partner_id = " . (int)$partnerId : "";

$teamMetricsStmt = $db->query("
    SELECT 
        ct.id as team_id,
        ct.team_name,
        ct.leader_name,
        ct.leader_phone,
        ct.coverage_region,
        ct.status as team_status,
        p.name as contractor_name,
        p.id as contractor_partner_id,
        (SELECT COUNT(*) FROM contractor_technicians tech WHERE tech.team_id = ct.id AND tech.status = 'Approved') as technician_count,
        -- Surveys Breakdown
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id) as total_surveys_assigned,
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.status = 'In Progress') as surveys_in_progress,
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.status IN ('Completed', 'Approved')) as surveys_completed,
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Feasible') as surveys_feasible,
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Feasible with Extension/Civils') as surveys_feasible_extension,
        (SELECT COUNT(*) FROM survey_tasks st WHERE st.contractor_team_id = ct.id AND st.feasibility_result = 'Not Feasible') as surveys_not_feasible,
        -- Installations Breakdown (from contractor_assignments & orders)
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_team_id = ct.id) as total_installations_assigned,
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_team_id = ct.id AND ca.status = 'In Progress') as installations_in_progress,
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_team_id = ct.id AND ca.status IN ('Completed Submitted', 'Completed')) as installations_completed
    FROM contractor_teams ct
    JOIN partners p ON ct.contractor_partner_id = p.id
    {$teamScopeWhere}
    ORDER BY ct.team_name ASC
");
$teamMetrics = $teamMetricsStmt->fetchAll();

// ------------------------------------------------------------------
// Installations Completed by Each Team (Detailed Records)
// ------------------------------------------------------------------
$instWhere = "WHERE (ca.status IN ('Completed Submitted', 'Completed') OR o.status IN ('Testing', 'UAT', 'Closed', 'Completed'))";
if ($isContractor && $partnerId) {
    $instWhere .= " AND ca.contractor_partner_id = " . (int)$partnerId;
}

$teamInstallationsStmt = $db->query("
    SELECT 
        ca.id as assignment_id,
        ca.order_id,
        ca.status as assignment_status,
        ca.completed_at,
        ca.pickup_location,
        ca.target_date,
        ca.work_order_notes,
        ct.id as team_id,
        COALESCE(ct.team_name, 'General Field Team') as team_name,
        COALESCE(ct.leader_name, '—') as leader_name,
        p.name as contractor_name,
        o.order_number,
        o.customer_name,
        o.service_type,
        o.circuit_id,
        o.status as order_status,
        o.installation_date
    FROM contractor_assignments ca
    LEFT JOIN contractor_teams ct ON ca.contractor_team_id = ct.id
    JOIN partners p ON ca.contractor_partner_id = p.id
    JOIN orders o ON ca.order_id = o.id
    {$instWhere}
    ORDER BY ca.completed_at DESC, ca.id DESC
    LIMIT 100
");
$completedInstallations = $teamInstallationsStmt->fetchAll();

// ------------------------------------------------------------------
// Combined Live Activity Feed (Surveys + Installations)
// ------------------------------------------------------------------
$activityFeed = [];
try {
    $surveyFeedWhere = ($isContractor && $partnerId) ? "WHERE s.contractor_partner_id = " . (int)$partnerId : "";
    $installFeedWhere = ($isContractor && $partnerId) ? "WHERE ca.contractor_partner_id = " . (int)$partnerId : "";

    $feedSql = "
        SELECT 
            'survey' as item_type,
            st.id as activity_id,
            st.survey_id as ref_id,
            s.survey_number as ref_code,
            s.customer_name,
            ct.team_name,
            st.status,
            st.note,
            st.created_at,
            u.full_name as actor_name
        FROM survey_timeline st
        JOIN survey_tasks s ON st.survey_id = s.id
        JOIN contractor_teams ct ON s.contractor_team_id = ct.id
        LEFT JOIN users u ON st.changed_by = u.id
        {$surveyFeedWhere}
        UNION ALL
        SELECT 
            'installation' as item_type,
            cpu.id as activity_id,
            cpu.order_id as ref_id,
            o.order_number as ref_code,
            o.customer_name,
            COALESCE(ct.team_name, 'Field Team') as team_name,
            cpu.progress_status as status,
            cpu.notes as note,
            cpu.created_at,
            u.full_name as actor_name
        FROM contractor_progress_updates cpu
        JOIN orders o ON cpu.order_id = o.id
        LEFT JOIN contractor_assignments ca ON cpu.assignment_id = ca.id
        LEFT JOIN contractor_teams ct ON ca.contractor_team_id = ct.id
        LEFT JOIN users u ON cpu.updated_by = u.id
        {$installFeedWhere}
        ORDER BY created_at DESC
        LIMIT 60
    ";
    $activityFeed = $db->query($feedSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$pageTitle = isContractorUser() ? 'Allocated Surveys' : 'Survey Management & Field Teams';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/surveys/index.php';
include APP_DIR . '/views/layout/footer.php';
