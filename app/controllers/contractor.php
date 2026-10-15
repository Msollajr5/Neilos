<?php
// ============================================================
// Contractor Module Controller — v1.0
// Handles contractor-specific dashboard, job acceptance,
// progress updates, evidence upload, and completion submission
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'dashboard';

// Access Guard
if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin() && !hasRole('Management') && !hasPermission('contractors.view')) {
    setFlash('danger', 'Access denied.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

// For non-contractor internal users (Admin, PM, Management, etc.), default view is the Contractors directory, not jobs
if (!isContractorUser() && ($action === 'dashboard' || empty($_GET['action']))) {
    header('Location: ' . APP_URL . '/?page=contractors');
    exit;
}

// Build partner filter for contractor users
$partnerId = $user['partner_id'] ?? null;

// ------------------------------------------------------------------
// POST: Add Contractor Team
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_team') {
    verifyCsrf();
    $contractorPartnerId = (int)($_POST['contractor_partner_id'] ?? $partnerId ?? 0);
    if (!$contractorPartnerId && isContractorUser()) {
        $contractorPartnerId = (int)($user['partner_id'] ?? 0);
    }
    if (!$contractorPartnerId) {
        setFlash('danger', 'Contractor Partner ID required.');
        header('Location: ' . APP_URL . '/?page=contractor&action=teams');
        exit;
    }
    $teamName       = trim($_POST['team_name'] ?? '');
    $leaderName     = trim($_POST['leader_name'] ?? '');
    $leaderPhone    = trim($_POST['leader_phone'] ?? '');
    $coverageRegion = trim($_POST['coverage_region'] ?? '');

    if (!$teamName) {
        setFlash('danger', 'Team Name is required.');
        header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams')));
        exit;
    }

    $db->prepare("INSERT INTO contractor_teams (contractor_partner_id, team_name, leader_name, leader_phone, coverage_region, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())")
       ->execute([$contractorPartnerId, $teamName, $leaderName ?: null, $leaderPhone ?: null, $coverageRegion ?: null, $user['id']]);
    $teamId = (int)$db->lastInsertId();

    auditLog("Created new contractor team #$teamId '$teamName' (Pending Approval)", 'contractor_teams', $teamId);
    setFlash('success', "Team '$teamName' registered successfully. Status is Pending Approval from Project Coordinator/Manager.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams')));
    exit;
}

// ------------------------------------------------------------------
// POST: Edit Contractor Team
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_team') {
    verifyCsrf();
    $teamId         = (int)($_POST['team_id'] ?? 0);
    $teamName       = trim($_POST['team_name'] ?? '');
    $leaderName     = trim($_POST['leader_name'] ?? '');
    $leaderPhone    = trim($_POST['leader_phone'] ?? '');
    $coverageRegion = trim($_POST['coverage_region'] ?? '');

    $db->prepare("UPDATE contractor_teams SET team_name = ?, leader_name = ?, leader_phone = ?, coverage_region = ? WHERE id = ?")
       ->execute([$teamName, $leaderName ?: null, $leaderPhone ?: null, $coverageRegion ?: null, $teamId]);
    
    setFlash('success', "Team '$teamName' details updated successfully.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams')));
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Contractor Team
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_team') {
    verifyCsrf();
    $teamId = (int)($_POST['team_id'] ?? 0);
    $db->prepare("UPDATE contractor_technicians SET team_id = NULL WHERE team_id = ?")->execute([$teamId]);
    $db->prepare("DELETE FROM contractor_teams WHERE id = ?")->execute([$teamId]);
    setFlash('success', "Team deleted successfully.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams')));
    exit;
}

// ------------------------------------------------------------------
// POST: Add Technician
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_technician') {
    verifyCsrf();
    $contractorPartnerId = (int)($_POST['contractor_partner_id'] ?? $partnerId ?? 0);
    if (!$contractorPartnerId && isContractorUser()) {
        $contractorPartnerId = (int)($user['partner_id'] ?? 0);
    }
    if (!$contractorPartnerId) {
        setFlash('danger', 'Contractor Partner ID required.');
        header('Location: ' . APP_URL . '/?page=contractor&action=teams');
        exit;
    }

    $fullName   = trim($_POST['full_name'] ?? '');
    $nidaNumber = trim($_POST['nida_number'] ?? '');
    $gender     = in_array($_POST['gender'] ?? '', ['Male', 'Female']) ? $_POST['gender'] : 'Male';
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $skillLevel = trim($_POST['skill_level'] ?? 'Technician');
    $teamId     = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;

    if (!$fullName || !$nidaNumber || !$phone) {
        setFlash('danger', 'Full Name, NIDA Number, and Phone Number are required.');
        header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams&tab=technicians')));
        exit;
    }

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        try {
            $up = uploadFile($_FILES['photo'], 'contractors/' . $contractorPartnerId . '/technicians');
            $photoPath = $up['path'];
        } catch (Exception $e) {}
    }

    $nidaPhotoPath = null;
    if (!empty($_FILES['nida_photo']['name'])) {
        try {
            $upNida = uploadFile($_FILES['nida_photo'], 'contractors/' . $contractorPartnerId . '/technicians/nida');
            $nidaPhotoPath = $upNida['path'];
        } catch (Exception $e) {}
    }

    $db->prepare("INSERT INTO contractor_technicians (contractor_partner_id, team_id, full_name, nida_number, gender, phone, email, photo_path, nida_photo_path, skill_level, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW())")
       ->execute([$contractorPartnerId, $teamId, $fullName, $nidaNumber, $gender, $phone, $email ?: null, $photoPath, $nidaPhotoPath, $skillLevel, $user['id']]);
    $techId = (int)$db->lastInsertId();

    auditLog("Registered technician #$techId '$fullName' (NIDA: $nidaNumber) (Pending Approval)", 'contractor_technicians', $techId);
    setFlash('success', "Technician '$fullName' registered successfully. Status is Pending Approval from Project Coordinator/Manager.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams&tab=technicians')));
    exit;
}

// ------------------------------------------------------------------
// POST: Edit Technician
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_technician') {
    verifyCsrf();
    $techId     = (int)($_POST['technician_id'] ?? 0);
    $fullName   = trim($_POST['full_name'] ?? '');
    $nidaNumber = trim($_POST['nida_number'] ?? '');
    $gender     = in_array($_POST['gender'] ?? '', ['Male', 'Female']) ? $_POST['gender'] : 'Male';
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $skillLevel = trim($_POST['skill_level'] ?? 'Technician');
    $teamId     = !empty($_POST['team_id']) ? (int)$_POST['team_id'] : null;

    $stmt = $db->prepare("SELECT * FROM contractor_technicians WHERE id = ?");
    $stmt->execute([$techId]);
    $existingTech = $stmt->fetch();
    if (!$existingTech) {
        setFlash('danger', 'Technician not found.');
        header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams&tab=technicians')));
        exit;
    }

    $photoPath = $existingTech['photo_path'];
    if (!empty($_FILES['photo']['name'])) {
        try {
            $up = uploadFile($_FILES['photo'], 'contractors/' . $existingTech['contractor_partner_id'] . '/technicians');
            $photoPath = $up['path'];
        } catch (Exception $e) {}
    }

    $nidaPhotoPath = $existingTech['nida_photo_path'];
    if (!empty($_FILES['nida_photo']['name'])) {
        try {
            $upNida = uploadFile($_FILES['nida_photo'], 'contractors/' . $existingTech['contractor_partner_id'] . '/technicians/nida');
            $nidaPhotoPath = $upNida['path'];
        } catch (Exception $e) {}
    }

    $db->prepare("UPDATE contractor_technicians SET full_name = ?, nida_number = ?, gender = ?, phone = ?, email = ?, photo_path = ?, nida_photo_path = ?, skill_level = ?, team_id = ? WHERE id = ?")
       ->execute([$fullName, $nidaNumber, $gender, $phone, $email ?: null, $photoPath, $nidaPhotoPath, $skillLevel, $teamId, $techId]);

    setFlash('success', "Technician '$fullName' updated successfully.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams&tab=technicians')));
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Technician
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_technician') {
    verifyCsrf();
    $techId = (int)($_POST['technician_id'] ?? 0);
    $db->prepare("DELETE FROM contractor_technicians WHERE id = ?")->execute([$techId]);
    setFlash('success', "Technician deleted successfully.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractor&action=teams&tab=technicians')));
    exit;
}

// ------------------------------------------------------------------
// POST: PM/Admin Approve/Reject Team
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['approve_team', 'reject_team'])) {
    verifyCsrf();
    if (!hasRole('Project Manager') && !isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. PM/Admin only.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }
    $teamId = (int)($_POST['team_id'] ?? 0);
    $newStatus = ($action === 'approve_team') ? 'Approved' : 'Inactive';

    $db->prepare("UPDATE contractor_teams SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")
       ->execute([$newStatus, $user['id'], $teamId]);

    auditLog("Team #$teamId marked as $newStatus by {$user['full_name']}", 'contractor_teams', $teamId);
    setFlash('success', "Team has been {$newStatus}.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractors')));
    exit;
}

// ------------------------------------------------------------------
// POST: PM/Admin Approve/Reject Technician
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['approve_technician', 'reject_technician'])) {
    verifyCsrf();
    if (!hasRole('Project Manager') && !isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. PM/Admin only.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }
    $techId = (int)($_POST['technician_id'] ?? 0);
    $newStatus = ($action === 'approve_technician') ? 'Approved' : 'Rejected';
    $rejectReason = trim($_POST['rejection_reason'] ?? '');

    $db->prepare("UPDATE contractor_technicians SET status = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?")
       ->execute([$newStatus, $user['id'], $rejectReason ?: null, $techId]);

    auditLog("Technician #$techId marked as $newStatus by {$user['full_name']}", 'contractor_technicians', $techId);
    setFlash('success', "Technician has been {$newStatus}.");
    header('Location: ' . ($_POST['redirect_to'] ?? (APP_URL . '/?page=contractors')));
    exit;
}

// ------------------------------------------------------------------
// GET: Teams & Technicians View
// ------------------------------------------------------------------
if ($action === 'teams') {
    $effPartnerId = $partnerId ?: (int)($_GET['contractor_id'] ?? 0);
    if (!$effPartnerId && !isContractorUser()) {
        $firstContractor = $db->query("SELECT id FROM partners WHERE kyc_type = 'Contractor' LIMIT 1")->fetchColumn();
        $effPartnerId = $firstContractor ? (int)$firstContractor : 0;
    }

    $contractorPartner = null;
    if ($effPartnerId) {
        $cpStmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $cpStmt->execute([$effPartnerId]);
        $contractorPartner = $cpStmt->fetch();
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
    $teamsStmt->execute([$effPartnerId]);
    $teams = $teamsStmt->fetchAll();

    // All technicians directory
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
    $techStmt->execute([$effPartnerId]);
    $technicians = $techStmt->fetchAll();

    $pageTitle = 'Teams & Technicians' . ($contractorPartner ? ' — ' . $contractorPartner['name'] : '');
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractor/teams.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'accept_job') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    // Verify assignment
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.*, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.*, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    if (($a['order_status'] ?? '') === 'Closed') {
        setFlash('danger', 'Job cannot be accepted because this order is Closed.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    $db->prepare("UPDATE contractor_assignments SET status = 'Accepted', accepted_by = ?, accepted_at = NOW() WHERE id = ?")
       ->execute([$user['id'], $assignmentId]);

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $a['order_id'], $user['id'], 'In Progress', 'Job accepted and proceeding to site.']);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$a['order_id'], 'Installation', 'Job assignment accepted.', $user['id']]);

    auditLog("Accepted job assignment #$assignmentId for order #{$a['order_id']}", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Job accepted successfully.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}

// ------------------------------------------------------------------
// POST: Post Progress Update (with optional SLA pause/resume)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'progress_update') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId   = (int)($_POST['assignment_id'] ?? 0);
    $orderId        = (int)($_POST['order_id'] ?? 0);
    $progressStatus = $_POST['progress_status'] ?? 'In Progress';
    $delayReason    = $_POST['delay_reason'] ?? null;
    $notes          = trim($_POST['notes'] ?? '');
    $isBlocker      = (int)($_POST['is_blocker'] ?? 0);
    $isResumed      = (int)($_POST['is_resumed'] ?? 0);

    if (!$notes) { setFlash('danger','Notes are required for progress update.'); header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit; }

    // Verify ownership/access
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.id, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.id, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }
    $chkA = $aStmt->fetch();
    if (!$chkA) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    if (($chkA['order_status'] ?? '') === 'Closed') {
        setFlash('danger', 'Progress updates cannot be posted because this order is Closed.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, is_blocker, is_resumed, delay_reason, notes) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$assignmentId, $orderId, $user['id'], $progressStatus, $isBlocker, $isResumed, $delayReason ?: null, $notes]);

    $db->prepare("UPDATE contractor_assignments SET status = 'In Progress' WHERE id = ? AND status = 'Accepted'")
       ->execute([$assignmentId]);

    // Auto-trigger SLA pause/resume
    if ($isBlocker && !$isResumed) {
        pauseOrderSLA($orderId, $user['id']);
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) SELECT id, status, ?, ? FROM orders WHERE id = ?")
           ->execute(["Contractor blocker: $notes" . ($delayReason ? " [Reason: $delayReason]" : ''), $user['id'], $orderId]);
    } elseif ($isResumed) {
        resumeOrderSLA($orderId, $user['id']);
    }

    auditLog("Progress update on assignment #$assignmentId" . ($isBlocker ? ' [BLOCKER]' : '') . ($isResumed ? ' [RESUMED]' : ''), 'contractor_assignments', $assignmentId);
    setFlash('success', 'Progress update submitted.' . ($isBlocker ? ' SLA clock paused.' : '') . ($isResumed ? ' SLA clock resumed.' : ''));
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}

// ------------------------------------------------------------------
// POST: Upload Evidence
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId   = (int)($_POST['assignment_id'] ?? 0);
    $orderId        = (int)($_POST['order_id'] ?? 0);
    $evidenceType   = trim($_POST['evidence_type'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');
    $serialNum      = trim($_POST['serial_number'] ?? '');
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $costAmount     = (!empty($_POST['cost_amount']) && is_numeric($_POST['cost_amount'])) ? (float)$_POST['cost_amount'] : null;

    try {
        $db->exec("ALTER TABLE contractor_evidence ADD COLUMN cost_amount DECIMAL(14,2) NULL AFTER notes");
    } catch (Throwable $e) {}

    // Backend Rule: Serial number is strictly exclusive to Serial Number / ONT/ONU Serial
    if ($evidenceType !== 'Serial Number' && $evidenceType !== 'ONT/ONU Serial') {
        $serialNum = '';
    }

    if ($orderId > 0 && !empty($pickupLocation)) {
        $db->prepare("UPDATE orders SET pickup_location = ? WHERE id = ?")->execute([$pickupLocation, $orderId]);
    }

    // Verify
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT id FROM contractor_assignments WHERE id = ? AND contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
        if (!$aStmt->fetch()) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    }

    // Prohibit all users from uploading evidence when order is closed
    if ($orderId) {
        $chkOrder = $db->prepare("SELECT status FROM orders WHERE id = ?");
        $chkOrder->execute([$orderId]);
        $ordStatus = $chkOrder->fetchColumn();
        if ($ordStatus === 'Closed') {
            setFlash('danger', 'Evidence cannot be uploaded because this order is Closed.');
            header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
            exit;
        }
    }

    $uploadedCount = 0;

    // Support multiple files under evidence_files[]
    if (!empty($_FILES['evidence_files']['name']) && is_array($_FILES['evidence_files']['name'])) {
        $count = count($_FILES['evidence_files']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['evidence_files']['error'][$i] === UPLOAD_ERR_OK && !empty($_FILES['evidence_files']['name'][$i])) {
                $fileArr = [
                    'name'     => $_FILES['evidence_files']['name'][$i],
                    'type'     => $_FILES['evidence_files']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['evidence_files']['tmp_name'][$i],
                    'error'    => $_FILES['evidence_files']['error'][$i],
                    'size'     => $_FILES['evidence_files']['size'][$i]
                ];
                try {
                    $up = uploadFile($fileArr, 'orders/'.$orderId.'/evidence');
                    $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, serial_number, pickup_location, notes, cost_amount, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                       ->execute([$assignmentId, $orderId, $evidenceType, $serialNum ?: null, $pickupLocation ?: null, $notes ?: null, $costAmount, $up['name'], $up['path'], $up['size'], $user['id']]);
                    $uploadedCount++;
                } catch (RuntimeException $e) {
                    error_log('Multi-file upload error: ' . $e->getMessage());
                }
            }
        }
    }

    // Support single file under evidence_file
    if ($uploadedCount === 0 && !empty($_FILES['evidence_file']['name']) && !is_array($_FILES['evidence_file']['name'])) {
        if ($_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
            try {
                $up = uploadFile($_FILES['evidence_file'], 'orders/'.$orderId.'/evidence');
                $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, serial_number, pickup_location, notes, cost_amount, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$assignmentId, $orderId, $evidenceType, $serialNum ?: null, $pickupLocation ?: null, $notes ?: null, $costAmount, $up['name'], $up['path'], $up['size'], $user['id']]);
                $uploadedCount++;
            } catch (RuntimeException $e) {
                setFlash('danger', 'Upload error: ' . e($e->getMessage()));
                header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
            }
        }
    }

    // Support text/serial only evidence if no file was uploaded
    if ($uploadedCount === 0 && ($serialNum || $notes || $pickupLocation || $costAmount !== null)) {
        $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, serial_number, pickup_location, notes, cost_amount, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$assignmentId, $orderId, $evidenceType, $serialNum ?: null, $pickupLocation ?: null, $notes ?: null, $costAmount, null, null, null, $user['id']]);
        $uploadedCount++;
    }

    evaluateAndSyncOrderStatus($orderId, 'upload_evidence');

    auditLog("Evidence uploaded ($uploadedCount file(s)): $evidenceType for assignment #$assignmentId", 'contractor_evidence', 0);
    setFlash('success', "Evidence ($evidenceType) successfully recorded ($uploadedCount item(s)).");
    if (!empty($_POST['redirect_to'])) {
        header('Location: ' . $_POST['redirect_to']);
    } elseif ($assignmentId > 0 && isContractorUser()) {
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    } else {
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    }
    exit;
}

// ------------------------------------------------------------------
// GET: Download Evidence File (Content-Disposition: attachment)
// ------------------------------------------------------------------
if ($action === 'download_evidence') {
    $evidenceId = (int)($_GET['id'] ?? 0);
    if (!$evidenceId) {
        setFlash('danger', 'Invalid file request.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $eStmt = $db->prepare("SELECT ce.*, ca.contractor_partner_id FROM contractor_evidence ce LEFT JOIN contractor_assignments ca ON ce.assignment_id = ca.id WHERE ce.id = ?");
    $eStmt->execute([$evidenceId]);
    $ev = $eStmt->fetch();

    if (!$ev) {
        setFlash('danger', 'Evidence file record not found.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    if ($partnerId && $ev['contractor_partner_id'] && (int)$ev['contractor_partner_id'] !== (int)$partnerId) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $relativePath = ltrim($ev['file_path'] ?? '', '/\\');
    $fullPath = PUBLIC_DIR . '/' . $relativePath;
    if (!$relativePath || !file_exists($fullPath) || !is_file($fullPath)) {
        $fullPath = dirname(dirname(__DIR__)) . '/public/' . $relativePath;
    }

    if (!$relativePath || !file_exists($fullPath) || !is_file($fullPath)) {
        setFlash('danger', 'Physical file non-existent on server.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($ev['assignment_id'] ?? 0));
        exit;
    }

    $fileName = $ev['file_name'] ?: basename($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    if (ob_get_level()) { ob_end_clean(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "'", "\r", "\n"], '', $fileName) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));

    readfile($fullPath);
    exit;
}

// ------------------------------------------------------------------
// POST: Replace Evidence File
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'replace_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $evidenceId   = (int)($_POST['evidence_id'] ?? 0);
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');
    $serialNum    = trim($_POST['serial_number'] ?? '');

    $eStmt = $db->prepare("SELECT * FROM contractor_evidence WHERE id = ?");
    $eStmt->execute([$evidenceId]);
    $oldEv = $eStmt->fetch();

    if (!$oldEv) {
        setFlash('danger', 'Original evidence file record not found.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    // Prohibit all users from replacing evidence when order is closed
    $chkOrderId = $orderId ?: (int)($oldEv['order_id'] ?? 0);
    if ($chkOrderId) {
        $chkOrder = $db->prepare("SELECT status FROM orders WHERE id = ?");
        $chkOrder->execute([$chkOrderId]);
        $ordStatus = $chkOrder->fetchColumn();
        if ($ordStatus === 'Closed') {
            setFlash('danger', 'Evidence cannot be replaced because this order is Closed.');
            header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: ($oldEv['assignment_id'] ?? 0)));
            exit;
        }
    }

    $filePath = $oldEv['file_path'];
    $fileName = $oldEv['file_name'];
    $fileSize = $oldEv['file_size'];

    if (!empty($_FILES['evidence_file']['name'])) {
        try {
            $up = uploadFile($_FILES['evidence_file'], 'orders/' . ($orderId ?: $oldEv['order_id']) . '/evidence');
            if (!empty($oldEv['file_path'])) {
                $oldFullPath = PUBLIC_DIR . '/' . ltrim($oldEv['file_path'], '/\\');
                if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }
            $filePath = $up['path'];
            $fileName = $up['name'];
            $fileSize = $up['size'];
        } catch (RuntimeException $e) {
            setFlash('danger', 'Upload error during file replacement: ' . e($e->getMessage()));
            header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: $oldEv['assignment_id']));
            exit;
        }
    }

    $db->prepare("UPDATE contractor_evidence SET 
        serial_number = ?, 
        notes = ?, 
        file_name = ?, 
        file_path = ?, 
        file_size = ?, 
        uploaded_by = ?, 
        uploaded_at = NOW() 
        WHERE id = ?")
       ->execute([$serialNum ?: null, $notes ?: null, $fileName, $filePath, $fileSize, $user['id'], $evidenceId]);

    evaluateAndSyncOrderStatus($orderId ?: $oldEv['order_id'], 'replace_evidence');

    auditLog("Replaced evidence file #$evidenceId ({$oldEv['evidence_type']})", 'contractor_evidence', $evidenceId);
    setFlash('success', "Evidence file for {$oldEv['evidence_type']} replaced successfully.");
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: $oldEv['assignment_id']));
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Evidence File
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $evidenceId   = (int)($_POST['evidence_id'] ?? 0);
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    $eStmt = $db->prepare("SELECT * FROM contractor_evidence WHERE id = ?");
    $eStmt->execute([$evidenceId]);
    $ev = $eStmt->fetch();

    if ($ev) {
        // Prohibit all users from deleting evidence when order is closed
        $chkOrderId = (int)($ev['order_id'] ?? 0);
        if ($chkOrderId) {
            $chkOrder = $db->prepare("SELECT status FROM orders WHERE id = ?");
            $chkOrder->execute([$chkOrderId]);
            $ordStatus = $chkOrder->fetchColumn();
            if ($ordStatus === 'Closed') {
                setFlash('danger', 'Evidence cannot be deleted because this order is Closed.');
                header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: ($ev['assignment_id'] ?? 0)));
                exit;
            }
        }

        if (!empty($ev['file_path'])) {
            $relativePath = ltrim($ev['file_path'], '/\\');
            $fullPath = PUBLIC_DIR . '/' . $relativePath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        $db->prepare("DELETE FROM contractor_evidence WHERE id = ?")->execute([$evidenceId]);
        evaluateAndSyncOrderStatus($ev['order_id'], 'delete_evidence');
        auditLog("Deleted evidence file #$evidenceId ({$ev['evidence_type']})", 'contractor_evidence', $evidenceId);
        setFlash('success', "Evidence file ({$ev['evidence_type']}) deleted successfully.");
    } else {
        setFlash('warning', "Evidence record not found.");
    }

    if (!empty($_POST['redirect_to'])) {
        header('Location: ' . $_POST['redirect_to']);
    } elseif ($assignmentId > 0 && isContractorUser()) {
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    } else {
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . ($ev['order_id'] ?? $chkOrderId));
    }
    exit;
}

// ------------------------------------------------------------------
// POST: Submit Completion
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_completion') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $remarks      = trim($_POST['completion_remarks'] ?? '');
    $pickupLoc    = trim($_POST['pickup_location'] ?? '');
    $teamId       = !empty($_POST['contractor_team_id']) ? (int)$_POST['contractor_team_id'] : null;
    $teamName     = null;

    if ($teamId) {
        $tStmt = $db->prepare("SELECT team_name FROM contractor_teams WHERE id = ?");
        $tStmt->execute([$teamId]);
        $teamName = $tStmt->fetchColumn() ?: null;
    }

    // Verify assignment
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.*, o.service_type FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.*, o.service_type FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    if (($a['order_status'] ?? '') === 'Closed') {
        setFlash('danger', 'Job completion cannot be submitted because this order is Closed.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    if (!empty($pickupLoc)) {
        $db->prepare("UPDATE orders SET pickup_location = ? WHERE id = ?")->execute([$pickupLoc, $orderId]);
        try { $db->prepare("UPDATE contractor_assignments SET pickup_location = ? WHERE id = ?")->execute([$pickupLoc, $assignmentId]); } catch (Throwable $e) {}
    }

    if ($teamId && $teamName) {
        $db->prepare("UPDATE orders SET contractor_team_id = ?, contractor_team_name = ? WHERE id = ?")->execute([$teamId, $teamName, $orderId]);
        $db->prepare("UPDATE contractor_assignments SET contractor_team_id = ?, contractor_team_name = ? WHERE id = ?")->execute([$teamId, $teamName, $assignmentId]);
    }

    // Check if contractor uploaded evidence
    $evStmt = $db->prepare("SELECT COUNT(*) FROM contractor_evidence WHERE assignment_id = ?");
    $evStmt->execute([$assignmentId]);
    $evCount = (int)$evStmt->fetchColumn();

    if ($evCount === 0 && isContractorUser()) {
        setFlash('danger', 'Cannot submit completion. Mandatory evidence checklist is not satisfied (at least one installation photo, ONT serial, or signal test is required).');
        header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
    }

    // Mark assignment as completed submitted
    $cols = $db->query("SHOW COLUMNS FROM contractor_assignments")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('completion_remarks', $cols)) {
        $db->prepare("UPDATE contractor_assignments SET status = 'Completed Submitted', completed_at = NOW(), completion_remarks = ? WHERE id = ?")
           ->execute([$remarks, $assignmentId]);
    } else {
        $db->prepare("UPDATE contractor_assignments SET status = 'Completed', completion_notes = ?, completed_at = NOW() WHERE id = ?")
           ->execute([$remarks, $assignmentId]);
    }
    
    $db->prepare("UPDATE orders SET status = 'Testing', installation_date = COALESCE(installation_date, CURDATE()), updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'submit_completion');

    $teamNote = $teamName ? " [Team: $teamName]" : "";
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', "Job completion submitted$teamNote. " . ($remarks ? "Remarks: $remarks" : "Awaiting PM/BSA testing review."), $user['id']]);

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $orderId, $user['id'], 'Completed', "Installation completed and submitted for review$teamNote. $remarks"]);

    queueOrderNotification($orderId, 'Contractor Completed');
    setFlash('success', 'Installation submitted. Order is now in <strong>Testing — Internal Review</strong>.');
    if (!isContractorUser() && (hasRole('Project Manager', 'BSA') || isAdmin())) {
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    } else {
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    }
    exit;
}

// ------------------------------------------------------------------
// POST: Admin / PM Update Contractor Job Status & Details
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin_update_job') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Project Manager')) {
        setFlash('danger', 'Access denied. Admin or Project Manager only.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $assignmentId   = (int)($_POST['assignment_id'] ?? 0);
    $status         = $_POST['status'] ?? '';
    $targetDate     = $_POST['target_date'] ?? null;
    $workOrderNotes = trim($_POST['work_order_notes'] ?? '');

    $aStmt = $db->prepare("SELECT ca.*, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
    $aStmt->execute([$assignmentId]);
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    if (($a['order_status'] ?? '') === 'Closed') {
        setFlash('danger', 'Job management status and details cannot be modified because this order is Closed.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    $allowedStatuses = ['Assigned','Accepted','In Progress','Completed Submitted','Completed','Returned','Cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        setFlash('danger','Invalid status.');
        header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
    }

    $completedAtSql = ($status === 'Completed' || $status === 'Completed Submitted') ? ", completed_at = IFNULL(completed_at, NOW())" : "";

    $db->prepare("UPDATE contractor_assignments SET status = ?, target_date = ?, work_order_notes = ? $completedAtSql WHERE id = ?")
       ->execute([$status, $targetDate ?: null, $workOrderNotes, $assignmentId]);

    // Log progress update
    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $a['order_id'], $user['id'], $status, "Admin/PM updated job status to '$status'." . ($workOrderNotes ? " Notes: $workOrderNotes" : '')]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$a['order_id'], $a['order_status'] ?? 'Installation', "Admin/PM updated contractor assignment status to '$status'.", $user['id']]);

    auditLog("Admin updated assignment #$assignmentId status to '$status'", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Contractor job status and details updated successfully.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}


// ------------------------------------------------------------------
// Job Detail View
// ------------------------------------------------------------------
if ($action === 'job') {
    $assignmentId = (int)($_GET['id'] ?? 0);

    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, o.order_number, o.status as order_status,
            o.customer_name, o.customer_location, o.service_type, o.fttx_package, o.aggregate_capacity, o.bandwidth,
            o.remote_hands_nrc_usd, o.base_nrc_usd, o.standard_nrc, o.gps_coordinates, o.building_name,
            o.special_requirements, o.media_type, o.bsa_special_conditions,
            u.full_name as assigned_by_name, u2.full_name as accepted_by_name,
            partner.name as partner_name
            FROM contractor_assignments ca
            JOIN partners p ON ca.contractor_partner_id = p.id
            JOIN orders o ON ca.order_id = o.id
            JOIN partners partner ON o.partner_id = partner.id
            LEFT JOIN users u ON ca.assigned_by = u.id
            LEFT JOIN users u2 ON ca.accepted_by = u2.id
            WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, o.order_number, o.status as order_status,
            o.customer_name, o.customer_location, o.service_type, o.fttx_package, o.aggregate_capacity, o.bandwidth,
            o.remote_hands_nrc_usd, o.base_nrc_usd, o.standard_nrc, o.gps_coordinates, o.building_name,
            o.special_requirements, o.media_type, o.bsa_special_conditions,
            u.full_name as assigned_by_name, u2.full_name as accepted_by_name,
            partner.name as partner_name
            FROM contractor_assignments ca
            JOIN partners p ON ca.contractor_partner_id = p.id
            JOIN orders o ON ca.order_id = o.id
            JOIN partners partner ON o.partner_id = partner.id
            LEFT JOIN users u ON ca.assigned_by = u.id
            LEFT JOIN users u2 ON ca.accepted_by = u2.id
            WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }

    $assignment = $aStmt->fetch();
    if (!$assignment) { setFlash('danger','Job not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $evidenceStmt = $db->prepare("SELECT ce.*, u.full_name FROM contractor_evidence ce LEFT JOIN users u ON ce.uploaded_by = u.id WHERE ce.assignment_id = ? ORDER BY ce.uploaded_at DESC");
    $evidenceStmt->execute([$assignmentId]);
    $evidence = $evidenceStmt->fetchAll();

    $progressStmt = $db->prepare("SELECT cpu.*, u.full_name FROM contractor_progress_updates cpu LEFT JOIN users u ON cpu.updated_by = u.id WHERE cpu.assignment_id = ? ORDER BY cpu.created_at DESC");
    $progressStmt->execute([$assignmentId]);
    $progressUpdates = $progressStmt->fetchAll();

    // Evidence checklist for this service type (order-specific)
    $rawSvc = trim($assignment['service_type'] ?? '');
    $svcTypes = [];
    if (!empty($rawSvc)) {
        $svcTypes[] = $rawSvc;
        if (stripos($rawSvc, 'FTTH') !== false) { $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'FTTB') !== false) { $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'FTTE') !== false) { $svcTypes[] = 'FTTE'; $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'DIA') !== false) { $svcTypes[] = 'DIA'; }
        if (stripos($rawSvc, 'BIA') !== false || stripos($rawSvc, 'Broadband') !== false) { $svcTypes[] = 'BIA (Broadband Internet Access)'; $svcTypes[] = 'BIA'; $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'Layer 2') !== false || stripos($rawSvc, 'Layer2') !== false || stripos($rawSvc, 'Ethernet') !== false) { $svcTypes[] = 'Layer 2 ( last mile)'; $svcTypes[] = 'Dedicated Layer 2'; }
        if (stripos($rawSvc, 'Remote Hands') !== false) { $svcTypes[] = 'Remote Hands Only'; $svcTypes[] = 'Remote Hands'; }
    }
    $svcTypes = array_values(array_unique(array_filter($svcTypes)));
    if (empty($svcTypes)) {
        $svcTypes = ['FTTH'];
    }

    $placeholders = implode(',', array_fill(0, count($svcTypes), '?'));
    $checklistStmt = $db->prepare("
        SELECT ecc.*, 
          (SELECT COUNT(*) FROM contractor_evidence ce 
           WHERE (ce.assignment_id = ? OR ce.order_id = ?) 
             AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(ecc.evidence_type))) as uploaded 
        FROM evidence_checklist_config ecc 
        WHERE ecc.service_type IN ($placeholders)
        ORDER BY ecc.id ASC
    ");
    $params = array_merge([$assignmentId, $assignment['order_id']], $svcTypes);
    $checklistStmt->execute($params);
    $rawChecklist = $checklistStmt->fetchAll();

    // Deduplicate by evidence_type if multiple match and load detailed uploaded files list
    $uniqueChecklist = [];
    foreach ($rawChecklist as $item) {
        $key = strtolower(trim($item['evidence_type']));
        if (!isset($uniqueChecklist[$key])) {
            $uniqueChecklist[$key] = $item;
        }
    }
    $checklist = array_values($uniqueChecklist);

    foreach ($checklist as &$item) {
        $filesStmt = $db->prepare("
            SELECT ce.*, u.full_name as uploader_name 
            FROM contractor_evidence ce 
            LEFT JOIN users u ON ce.uploaded_by = u.id 
            WHERE (ce.assignment_id = ? OR ce.order_id = ?) 
              AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(?))
            ORDER BY ce.uploaded_at DESC, ce.id DESC
        ");
        $filesStmt->execute([$assignmentId, $assignment['order_id'], $item['evidence_type']]);
        $item['files'] = $filesStmt->fetchAll();
        $item['uploaded'] = count($item['files']);
    }
    unset($item);

    // Load approved contractor teams for selection dropdown
    $contractorTeams = [];
    if (!empty($assignment['contractor_partner_id'])) {
        $tStmt = $db->prepare("SELECT id, team_name, leader_name, leader_phone FROM contractor_teams WHERE contractor_partner_id = ? AND status = 'Approved' ORDER BY team_name ASC");
        $tStmt->execute([$assignment['contractor_partner_id']]);
        $contractorTeams = $tStmt->fetchAll();
    }

    $pageTitle = 'Job ' . $assignment['order_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractor/job_detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Contractor Closed Jobs View (Privacy-Preserving / Restricted Data)
// ------------------------------------------------------------------
if ($action === 'closed_jobs') {
    $search = trim($_GET['q'] ?? '');
    $contractorIdFilter = (int)($_GET['contractor_id'] ?? 0);

    $whereClosed = ["(o.status = 'Closed' OR ca.status = 'Completed')"];
    $cParams = [];

    if ($partnerId) {
        $whereClosed[] = "ca.contractor_partner_id = ?";
        $cParams[] = $partnerId;
    } elseif ($contractorIdFilter > 0) {
        $whereClosed[] = "ca.contractor_partner_id = ?";
        $cParams[] = $contractorIdFilter;
    }

    if ($search !== '') {
        $whereClosed[] = "(o.order_number LIKE ? OR ca.contractor_team_name LIKE ?)";
        $cParams[] = "%$search%";
        $cParams[] = "%$search%";
    }

    $whereClosedSql = implode(' AND ', $whereClosed);
    $closedStmt = $db->prepare("
        SELECT ca.id, ca.assigned_at, ca.completed_at, ca.contractor_team_name,
               o.id as order_id, o.order_number, o.closed_date, o.updated_at,
               cp.name as contractor_name
        FROM contractor_assignments ca
        JOIN orders o ON ca.order_id = o.id
        JOIN partners cp ON ca.contractor_partner_id = cp.id
        WHERE $whereClosedSql
        ORDER BY COALESCE(o.closed_date, ca.completed_at, ca.assigned_at) DESC
    ");
    $closedStmt->execute($cParams);
    $closedJobs = $closedStmt->fetchAll();

    $pageTitle = 'Closed Jobs';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractor/closed_jobs.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Contractor Dashboard (Active / Ongoing jobs only)
// ------------------------------------------------------------------
$search             = trim($_GET['q'] ?? '');
$statusFilter       = trim($_GET['status'] ?? '');
$serviceFilter      = trim($_GET['service_type'] ?? '');
$contractorIdFilter = (int)($_GET['contractor_id'] ?? 0);
$filterPreset       = trim($_GET['preset'] ?? '');
$filterStart        = trim($_GET['start_date'] ?? '');
$filterEnd          = trim($_GET['end_date'] ?? '');

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

$whereClauses = ["o.status != 'Closed' AND ca.status != 'Completed'"];
$params = [];

if ($partnerId) {
    $whereClauses[] = "ca.contractor_partner_id = ?";
    $params[] = $partnerId;
} elseif ($contractorIdFilter > 0) {
    $whereClauses[] = "ca.contractor_partner_id = ?";
    $params[] = $contractorIdFilter;
}

if ($search !== '') {
    $whereClauses[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_location LIKE ? OR cp.name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

if ($statusFilter !== '') {
    $whereClauses[] = "ca.status = ?";
    $params[] = $statusFilter;
}

if ($serviceFilter !== '') {
    $whereClauses[] = "o.service_type = ?";
    $params[] = $serviceFilter;
}

// Period / Date Range filter on ca.assigned_at
if ($filterStart && $filterEnd) {
    $whereClauses[] = "ca.assigned_at BETWEEN ? AND ?";
    $params[] = $filterStart . ' 00:00:00';
    $params[] = $filterEnd . ' 23:59:59';
} elseif ($filterStart) {
    $whereClauses[] = "ca.assigned_at >= ?";
    $params[] = $filterStart . ' 00:00:00';
} elseif ($filterEnd) {
    $whereClauses[] = "ca.assigned_at <= ?";
    $params[] = $filterEnd . ' 23:59:59';
}

$whereSql = implode(' AND ', $whereClauses);

// List of all contractors for the contractor filter dropdown (when viewed by PM/Admin)
$contractorsDropdown = [];
if (!$partnerId) {
    try {
        $contractorsDropdown = $db->query("SELECT id, name, trading_name FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor' ORDER BY name ASC")->fetchAll();
    } catch (Exception $e) {}
}

// Export CSV
if ($action === 'export' || (!empty($_GET['export']) && $_GET['export'] === 'csv')) {
    $exportStmt = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.service_type, o.fttx_package,
        o.aggregate_capacity, o.bandwidth, o.remote_hands_nrc_usd, o.base_nrc_usd, o.standard_nrc,
        o.customer_location, o.status as order_status, partner.name as partner_name, cp.name as contractor_name
        FROM contractor_assignments ca
        JOIN orders o ON ca.order_id = o.id
        JOIN partners partner ON o.partner_id = partner.id
        JOIN partners cp ON ca.contractor_partner_id = cp.id
        WHERE $whereSql
        ORDER BY ca.assigned_at DESC");
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contractor_jobs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'Job ID',
        'Order Number',
        'Customer Name',
        'Service Type',
        'Location',
        'Contractor',
        'Job Status',
        'Order Status',
        'Assigned Date',
        'Target Due Date',
        'Completed Date'
    ]);

    foreach ($rows as $r) {
        $svc = trim($r['service_type'] ?? '');
        if ($svc === '') {
            if (!empty($r['fttx_package'])) $svc = 'FTTH';
            elseif (!empty($r['aggregate_capacity'])) $svc = 'Layer 2 (last mile)';
            elseif (!empty($r['bandwidth'])) $svc = 'BIA (Broadband Internet Access)';
            else $svc = 'Standard';
        }
        fputcsv($out, [
            $r['id'],
            $r['order_number'] ?? '',
            $r['customer_name'] ?? '',
            $svc,
            $r['customer_location'] ?? '',
            $r['contractor_name'] ?? '',
            $r['status'] ?? '',
            $r['order_status'] ?? '',
            !empty($r['assigned_at']) ? date('Y-m-d H:i', strtotime($r['assigned_at'])) : '',
            !empty($r['target_date']) ? date('Y-m-d', strtotime($r['target_date'])) : '',
            !empty($r['completed_at']) ? date('Y-m-d H:i', strtotime($r['completed_at'])) : ''
        ]);
    }
    fclose($out);
    exit;
}

$myJobs = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.service_type, o.fttx_package,
    o.aggregate_capacity, o.bandwidth, o.remote_hands_nrc_usd, o.base_nrc_usd, o.standard_nrc,
    o.customer_location, o.status as order_status, partner.name as partner_name, cp.name as contractor_name
    FROM contractor_assignments ca
    JOIN orders o ON ca.order_id = o.id
    JOIN partners partner ON o.partner_id = partner.id
    JOIN partners cp ON ca.contractor_partner_id = cp.id
    WHERE $whereSql
    ORDER BY ca.assigned_at DESC");
$myJobs->execute($params);
$assignments = $myJobs->fetchAll();

// Counts
$counts = ['Assigned'=>0,'Accepted'=>0,'In Progress'=>0,'Completed Submitted'=>0,'Completed'=>0,'Returned'=>0,'SLA Due Today'=>0];
$todayStr = date('Y-m-d');
foreach ($assignments as $a) {
    $counts[$a['status']] = ($counts[$a['status']] ?? 0) + 1;
    if (!empty($a['target_date']) && $a['target_date'] <= $todayStr && !in_array($a['status'], ['Completed', 'Completed Submitted'])) {
        $counts['SLA Due Today']++;
    }
}

$pageTitle = isContractorUser() ? 'My Jobs' : 'Contractors';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/contractor/dashboard.php';
include APP_DIR . '/views/layout/footer.php';
