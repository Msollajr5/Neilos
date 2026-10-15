<?php
// ============================================================
// Orders Controller — v1.0 Full Lifecycle
// Statuses: Feasibility Review → Await Commercial Approval →
//           Management Approval → Pending SOF → SOF Review →
//           Installation → Testing → UAT → Closed
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? ($_GET['page'] === 'new_order' ? 'new' : ($_GET['page'] === 'generate_sof' ? 'generate_sof' : 'list'));

// Include SLA helpers and run reactive UAT auto-approval checks
require_once APP_DIR . '/helpers/sla.php';
checkAndAutoApproveUatOrders($db);

// ------------------------------------------------------------------
// Auto-migration: add is_active column if not present
// ------------------------------------------------------------------
try {
    $cols = $db->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_active', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT \'1 = Active, 0 = Inactive\' AFTER billing_start_date');
    }
    if (!in_array('pickup_location', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN pickup_location VARCHAR(255) NULL AFTER customer_location');
    }
    $ceCols = $db->query('SHOW COLUMNS FROM contractor_evidence')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('uat_signoff_filename', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN uat_signoff_filename VARCHAR(255) NULL AFTER uat_signoff_file');
    }
    if (!in_array('uat_uploaded_by', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN uat_uploaded_by INT NULL AFTER uat_signoff_filename');
    }
    if (!in_array('uat_uploaded_at', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN uat_uploaded_at DATETIME NULL AFTER uat_uploaded_by');
    }
    if (!in_array('uat_return_reason', $cols)) {
        $db->exec('ALTER TABLE orders ADD COLUMN uat_return_reason TEXT NULL');
    }
} catch (Throwable $_migEx) { /* non-fatal: column may already exist */ }

// Block contractor users from listing all orders
if (isContractorUser() && !in_array($action, ['list','detail'])) {
    // Contractors can only use contractor module
    header('Location: ' . APP_URL . '/?page=contractor');
    exit;
}

// ------------------------------------------------------------------
// POST: Create new feasibility request
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();
    requirePermission('orders.create');

    $serviceType = trim($_POST['service_type'] ?? '');
    
    // Dynamically retrieve active service types from DB (supports Dark Fiber, custom types, etc.)
    $activeTypes = getActiveServiceTypes($db);
    $allKnownTypes = array_merge(
        $activeTypes,
        ['Layer 2 ( last mile)', 'FTTH', 'FTTB', 'BIA (Broadband Internet Access)', 'Remote Hands Only', 'DIA', 'Dedicated Layer 2', 'Remote Hands', 'Dark Fiber', 'Dark Fibre', 'DF']
    );
    try {
        $stDbCodes = $db->query("SELECT code FROM service_types WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $stDbNames = $db->query("SELECT name FROM service_types WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $allKnownTypes = array_merge($allKnownTypes, $stDbCodes, $stDbNames);
    } catch (Throwable $t) {}

    $isValid = false;
    foreach ($allKnownTypes as $known) {
        if (strcasecmp($serviceType, trim($known)) === 0) {
            $isValid = true;
            break;
        }
    }

    if (!$isValid || empty($serviceType)) {
        setFlash('danger', 'Invalid service type.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    $partnerId = isPartnerUser() ? $user['partner_id'] : (int)($_POST['partner_id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Partner is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    // Disallow FTTH and FTTB for Enterprise corporate clients
    try {
        $pCheckStmt = $db->prepare("SELECT partner_category, partner_type FROM partners WHERE id = ?");
        $pCheckStmt->execute([$partnerId]);
        $pData = $pCheckStmt->fetch();
        $isEnterprisePartner = ($pData && (($pData['partner_category'] ?? '') === 'Enterprise Client' || ($pData['partner_type'] ?? '') === 'Enterprise' || ($pData['partner_type'] ?? '') === 'Government'));
        if ($isEnterprisePartner) {
            $svcUpper = strtoupper($serviceType);
            if (str_contains($svcUpper, 'FTTH') || str_contains($svcUpper, 'FTTB')) {
                setFlash('danger', 'FTTH and FTTB shared broadband products are not available for Enterprise Clients. Please select an Enterprise Dedicated Service (Layer 2, DIA, BIA, Dark Fiber).');
                header('Location: ' . APP_URL . '/?page=new_order' . ($partnerId ? '&partner_id=' . $partnerId : ''));
                exit;
            }
        }
    } catch (Throwable $e) {}

    $custName  = trim($_POST['customer_name'] ?? '');
    $contName  = trim($_POST['customer_contact_name'] ?? '');
    $contPhone = trim($_POST['customer_contact_phone'] ?? '');
    $contEmail = trim($_POST['customer_contact_email'] ?? '');

    $custLoc   = trim($_POST['customer_location'] ?? '');
    $gpsCoords = trim($_POST['gps_coordinates'] ?? '');

    if (!$custName) {
        setFlash('danger', 'Customer name is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!$custLoc) {
        setFlash('danger', 'Customer location is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!$gpsCoords) {
        setFlash('danger', 'GPS coordinates are mandatory.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!$contName || !$contPhone || !$contEmail) {
        setFlash('danger', 'Customer contact name, phone number, and email address are all mandatory.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!filter_var($contEmail, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Please provide a valid customer contact email address.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    // Resolve KAM: Partner Users automatically use their Partner's assigned KAM
    $pKam = [];
    try {
        $pKamStmt = $db->prepare("SELECT kam_id, assigned_kam_name FROM partners WHERE id = ?");
        $pKamStmt->execute([$partnerId]);
        $pKam = $pKamStmt->fetch() ?: [];
    } catch (Throwable $e) {}

    if (isPartnerUser()) {
        $kamName = $pKam['assigned_kam_name'] ?? '';
        $kamId   = $pKam['kam_id'] ?? null;
    } elseif (isKamUser()) {
        $kamName = $user['full_name'];
        $kamId   = (int)$user['id'];

        // Strict scoping: KAM can only create orders for their own partners
        $kamCheckStmt = $db->prepare("SELECT id FROM partners WHERE id = ? AND (kam_id = ? OR assigned_kam_name = ?)");
        $kamCheckStmt->execute([$partnerId, $user['id'], $user['full_name']]);
        if (!$kamCheckStmt->fetchColumn()) {
            setFlash('danger', 'You can only create service orders for partners registered in your KAM portfolio.');
            header('Location: ' . APP_URL . '/?page=new_order');
            exit;
        }
    } else {
        $kamName = $_POST['assigned_kam'] ?? ($pKam['assigned_kam_name'] ?? '');
        $kamStmt = $db->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'KAM' LIMIT 1");
        $kamStmt->execute([$kamName]);
        $kamId = $kamStmt->fetchColumn() ?: ($pKam['kam_id'] ?? null);
    }

    $orderNum = generateOrderNumber($partnerId, $serviceType);

    $stmt = $db->prepare("INSERT INTO orders (
        order_number, partner_id, user_id, kam_id, assigned_kam_name,
        customer_name, customer_location, gps_coordinates, site_category,
        building_name, floor_number, apartment_number,
        customer_contact_name, customer_contact_phone, customer_contact_email,
        service_type, fttx_package, bandwidth, nni_location, nni_port_size, aggregate_capacity,
        contract_term, special_requirements,
        usd_tzs_rate, base_nrc_usd, remote_hands_nrc_usd, nrc_subtotal_usd,
        vat_on_nrc, total_nrc_incl_vat,
        base_mrc, mrc_currency, discount_pct, discount_amount, vat_on_mrc, total_mrc_incl_vat,
        standard_nrc, standard_mrc,
        status, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $comm = calculateCommercials($_POST);

    $inserted = false;
    $attempts = 0;
    $lastError = null;
    while (!$inserted && $attempts < 5) {
        $attempts++;
        try {
            $stmt->execute([
                $orderNum, $partnerId, $user['id'], $kamId, $kamName,
                $_POST['customer_name'] ?? '', $_POST['customer_location'] ?? '',
                $_POST['gps_coordinates'] ?? '', !empty($_POST['site_category']) ? trim($_POST['site_category']) : null,
                $_POST['building_name'] ?? '', $_POST['floor_number'] ?? '', $_POST['apartment_number'] ?? '',
                $_POST['customer_contact_name'] ?? '', $_POST['customer_contact_phone'] ?? '',
                $_POST['customer_contact_email'] ?? '',
                $serviceType,
                $_POST['fttx_package'] ?? null, $_POST['bandwidth'] ?? null,
                $_POST['nni_location'] ?? null, !empty($_POST['nni_port_size']) ? trim($_POST['nni_port_size']) : '10G', $_POST['aggregate_capacity'] ?? null,
                $_POST['contract_term'] ?? null, $_POST['special_requirements'] ?? null,
                USD_TZS_RATE,
                $comm['base_nrc_usd'], $comm['remote_hands_nrc_usd'], $comm['nrc_subtotal_usd'],
                $comm['vat_on_nrc'], $comm['total_nrc_incl_vat'],
                $comm['base_mrc'], $comm['mrc_currency'], $comm['discount_pct'],
                $comm['discount_amount'], $comm['vat_on_mrc'], $comm['total_mrc_incl_vat'],
                $comm['base_nrc_usd'],  // standard_nrc = system-calculated
                $comm['base_mrc'],      // standard_mrc = system-calculated
                'Feasibility Review', $user['id'],
            ]);
            $inserted = true;
        } catch (PDOException $e) {
            $lastError = $e;
            // Only retry if it's specifically a duplicate order_number entry (MySQL 1062)
            if (($e->errorInfo[1] ?? 0) == 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                $orderNum = generateOrderNumber($partnerId, $serviceType);
            } else {
                throw $e;
            }
        }
    }

    $orderId = (int)$db->lastInsertId();
    if (!$inserted || !$orderId) {
        throw ($lastError ?: new Exception('Failed to create order.'));
    }

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Feasibility Review', 'Feasibility request submitted by partner.', $user['id']]);

    // Handle file uploads (with backend deduplication)
    if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $filesCount = count($_FILES['documents']['name']);
        $processedFiles = [];
        for ($i = 0; $i < $filesCount; $i++) {
            $fname = $_FILES['documents']['name'][$i] ?? '';
            $err   = $_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $fsize = $_FILES['documents']['size'][$i] ?? 0;
            if (!$fname || $err === UPLOAD_ERR_NO_FILE) continue;

            $fileDedupeKey = $fname . '_' . $fsize;
            if (isset($processedFiles[$fileDedupeKey])) continue;
            $processedFiles[$fileDedupeKey] = true;

            $file = [
                'name'     => $fname,
                'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                'error'    => $err,
                'size'     => $fsize
            ];
            try {
                $up = uploadFile($file, 'orders/' . $orderId);
                $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$orderId, 'other', 'Supporting Document', $up['name'], $up['path'], $up['size'], $user['id']]);
            } catch (Exception $e) {
                setFlash('warning', 'Document upload note: ' . e($e->getMessage()));
            }
        }
    }

    queueOrderNotification($orderId, 'Feasibility Submitted');
    auditLog("Created feasibility request $orderNum", 'orders', $orderId);
    setFlash('success', "Feasibility request <strong>$orderNum</strong> submitted. BSA will review shortly.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA — Technically Feasible
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_feasible') {
    verifyCsrf();
    if (!hasRole('BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId          = (int)($_POST['order_id'] ?? 0);
    $revisedNrcRaw    = trim($_POST['revised_nrc'] ?? '');
    $nrcJustification = trim($_POST['nrc_justification'] ?? '');
    $technicalRemarks = trim($_POST['technical_remarks'] ?? '');
    $nrcBreakdownJson = trim($_POST['nrc_breakdown_json'] ?? '');
    $nrcBreakdownValid = null;
    if ($nrcBreakdownJson) {
        $decoded = json_decode($nrcBreakdownJson, true);
        if (is_array($decoded) && !empty($decoded)) {
            $nrcBreakdownValid = json_encode($decoded);
        }
    }

    // Validation
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // Parse TZS input — allow zero for Remote Hands-only NRC
    $revisedNrc = null;
    if ($revisedNrcRaw !== '') {
        try {
            $revisedNrc = parseTZSInput($revisedNrcRaw, true, true, MAX_NRC_AMOUNT, 'Revised NRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage());
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }

    $standardNrc = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);

    // Justification mandatory when NRC differs from standard
    if ($revisedNrc !== null && $revisedNrc != $standardNrc && !$nrcJustification) {
        setFlash('danger','NRC justification is mandatory when revising the standard NRC.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if (!$technicalRemarks && $revisedNrc !== null) {
        setFlash('danger','Technical remarks are mandatory when NRC is revised.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Spec rule 7.2: Any discount in NRC triggers Director / Management approval
    if ($revisedNrc !== null && $revisedNrc < $standardNrc) {
        if (!$nrcJustification) {
            setFlash('danger','NRC justification is mandatory when offering an NRC discount.');
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }

        $effectiveNrc = ($revisedNrc !== null && $revisedNrc !== '') ? (float)$revisedNrc : $standardNrc;
        $rhNrc        = (float)($o['remote_hands_nrc_usd'] ?? 0);
        $nrcSub       = $effectiveNrc + $rhNrc;
        $vatNrc       = round($nrcSub * 0.18, 2);
        $totNrc       = round($nrcSub + $vatNrc, 2);

        $cktId = !empty($o['circuit_id']) ? $o['circuit_id'] : ('CKT-' . $o['order_number']);
        $db->prepare("UPDATE orders SET
            bsa_technical_result = 'Technically Feasible',
            revised_nrc = ?, nrc_justification = ?, nrc_breakdown = ?,
            nrc_subtotal_usd = ?, vat_on_nrc = ?, total_nrc_incl_vat = ?,
            bsa_special_conditions = ?,
            bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
            circuit_id = ?,
            status = 'Management Approval', updated_at = NOW()
            WHERE id = ?")->execute([
            $revisedNrc, $nrcJustification, $nrcBreakdownValid, $nrcSub, $vatNrc, $totNrc, $technicalRemarks, $user['id'], $cktId, $orderId
        ]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Management Approval', "BSA provided NRC discount ($revisedNrc vs standard $standardNrc) — routed to Management Approval.", $user['id']]);

        evaluateAndSyncOrderStatus($orderId, 'bsa_feasible');

        queueOrderNotification($orderId, 'KAM Requires Further Approval');
        auditLog("BSA marked technically feasible with NRC discount for order #$orderId — routed to Management", 'orders', $orderId);
        setFlash('warning', 'NRC discount offered — order routed to Management Approval queue.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $effectiveNrc = ($revisedNrc !== null && $revisedNrc !== '') ? (float)$revisedNrc : $standardNrc;
    $rhNrc        = (float)($o['remote_hands_nrc_usd'] ?? 0);
    $nrcSub       = $effectiveNrc + $rhNrc;
    $vatNrc       = round($nrcSub * 0.18, 2);
    $totNrc       = round($nrcSub + $vatNrc, 2);

    $cktId = !empty($o['circuit_id']) ? $o['circuit_id'] : ('CKT-' . $o['order_number']);
    $db->prepare("UPDATE orders SET
        bsa_technical_result = 'Technically Feasible',
        revised_nrc = ?, nrc_justification = ?, nrc_breakdown = ?,
        nrc_subtotal_usd = ?, vat_on_nrc = ?, total_nrc_incl_vat = ?,
        bsa_special_conditions = ?,
        bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
        circuit_id = ?,
        status = 'Await Commercial Approval', updated_at = NOW()
        WHERE id = ?")->execute([
        $revisedNrc, $nrcJustification, $nrcBreakdownValid, $nrcSub, $vatNrc, $totNrc, $technicalRemarks, $user['id'], $cktId, $orderId
    ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Await Commercial Approval', 'BSA marked technically feasible — pending KAM commercial approval.', $user['id']]);

    // Audit price change
    if ($revisedNrc !== null) {
        recordPriceChange($orderId, 'revised_nrc', $standardNrc, $revisedNrc, $user['id'], 'Feasibility Review', $nrcJustification);
    }

    evaluateAndSyncOrderStatus($orderId, 'bsa_feasible');

    queueOrderNotification($orderId, 'BSA Feasibility Approved');
    auditLog("BSA marked technically feasible for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Order marked Technically Feasible. KAM notified for commercial approval.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA — Technically Not Feasible
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_not_feasible') {
    verifyCsrf();
    if (!hasRole('BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason  = trim($_POST['not_feasible_reason'] ?? '');
    if (!$reason) { setFlash('danger','Technical remarks are mandatory for Not Feasible.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $db->prepare("UPDATE orders SET
        bsa_technical_result = 'Technically Not Feasible',
        bsa_not_feasible_reason = ?,
        bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
        status = 'Not Feasible', updated_at = NOW()
        WHERE id = ?")->execute([$reason, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Not Feasible', "BSA: Technically Not Feasible — $reason", $user['id']]);

    queueOrderNotification($orderId, 'Technically Not Feasible');
    auditLog("BSA marked NOT feasible for order #$orderId", 'orders', $orderId);
    setFlash('warning', 'Order marked Technically Not Feasible. Partner has been notified.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM — Commercial approval (standard, exception, or escalate)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'kam_approve') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId            = (int)($_POST['order_id'] ?? 0);
    $proposedNrcRaw     = trim($_POST['kam_proposed_nrc'] ?? '');
    $proposedMrcRaw     = trim($_POST['kam_proposed_mrc'] ?? '');
    $kamJustification   = trim($_POST['kam_commercial_justification'] ?? '');
    $kamRemarks         = trim($_POST['kam_remarks'] ?? '');
    $usdTzsRate         = !empty($_POST['usd_tzs_rate']) ? (float)$_POST['usd_tzs_rate'] : 2600.00;

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // Ensure all required KAM exception columns exist
    $cols = array_keys($o);
    if (!in_array('kam_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_proposed_nrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_proposed_nrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_proposed_mrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_proposed_mrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_commercial_justification', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_commercial_justification TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('usd_tzs_rate', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN usd_tzs_rate DECIMAL(15,2) DEFAULT 2600.00"); } catch (Throwable $e) {}
    }

    $standardNrc = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);
    $standardMrc = (float)($o['standard_mrc'] ?? $o['base_mrc'] ?? 0);
    $bsaRevNrc   = ($o['revised_nrc'] !== null && $o['revised_nrc'] !== '') ? (float)$o['revised_nrc'] : $standardNrc;

    // Parse KAM proposed values (optional — blank means use existing/standard)
    $proposedNrc = null;
    $proposedMrc = null;
    if ($proposedNrcRaw !== '') {
        try {
            $proposedNrc = parseTZSInput($proposedNrcRaw, true, true, MAX_NRC_AMOUNT, 'Proposed NRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage()); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }
    if ($proposedMrcRaw !== '') {
        try {
            $proposedMrc = parseTZSInput($proposedMrcRaw, true, true, MAX_MRC_AMOUNT, 'Proposed MRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage()); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }

    // Exception = any price that deviates from the BSA-set or standard value
    $nrcException = ($proposedNrc !== null && $proposedNrc != $bsaRevNrc);
    $mrcException = ($proposedMrc !== null && $proposedMrc < $standardMrc);
    $isException  = $nrcException || $mrcException;

    if ($isException && !$kamJustification) {
        setFlash('danger','Commercial justification is mandatory when proposing a pricing exception.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if ($isException) {
        // Store KAM exception proposals and route to Management
        $db->prepare("UPDATE orders SET
            kam_proposed_nrc = ?, kam_proposed_mrc = ?,
            kam_commercial_justification = ?, kam_remarks = ?,
            usd_tzs_rate = ?,
            kam_approved_by = ?, kam_approved_at = NOW(),
            status = 'Management Approval', updated_at = NOW()
            WHERE id = ?")->execute([$proposedNrc, $proposedMrc, $kamJustification, $kamRemarks, $usdTzsRate, $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Management Approval', "KAM submitted pricing exception (NRC: " . ($proposedNrc ? '$' . number_format($proposedNrc, 2) : '—') . ", MRC: " . ($proposedMrc ? '$' . number_format($proposedMrc, 2) : '—') . ", Exchange Rate: TZS " . number_format($usdTzsRate, 2) . "/USD) — routed to Management Approval.", $user['id']]);

        // Audit price changes
        if ($nrcException) recordPriceChange($orderId, 'kam_proposed_nrc', $bsaRevNrc, $proposedNrc, $user['id'], 'Await Commercial Approval', $kamJustification);
        if ($mrcException) recordPriceChange($orderId, 'kam_proposed_mrc', $standardMrc, $proposedMrc, $user['id'], 'Await Commercial Approval', $kamJustification);

        queueOrderNotification($orderId, 'KAM Requires Further Approval');
        auditLog("KAM escalated pricing exception to Management for order #$orderId (Exchange Rate: $usdTzsRate)", 'orders', $orderId);
        setFlash('warning', 'Pricing exception submitted — order routed to Management Approval queue.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Standard approval — no exception. Use BSA NRC, standard MRC (or KAM proposed MRC if above standard)
    $effectiveMrc = ($proposedMrc !== null && $proposedMrc >= $standardMrc) ? $proposedMrc : $standardMrc;
    $vatMrc       = round($effectiveMrc * 0.18, 2);
    $totMrc       = round($effectiveMrc + $vatMrc, 2);

    $db->prepare("UPDATE orders SET
        base_mrc = CASE WHEN base_mrc IS NULL OR base_mrc = 0 THEN ? ELSE base_mrc END,
        revised_mrc = ?, 
        vat_on_mrc = ?,
        total_mrc_incl_vat = ?,
        mrc_justification = ?,
        kam_remarks = ?,
        usd_tzs_rate = ?,
        kam_approved_by = ?, kam_approved_at = NOW(),
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([
            $effectiveMrc,
            $effectiveMrc != $standardMrc ? $effectiveMrc : null,
            $vatMrc,
            $totMrc,
            $kamJustification ?: null, 
            $kamRemarks, 
            $usdTzsRate,
            $user['id'], 
            $orderId
        ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'KAM: Commercial approved at standard pricing. Partner notified for SOF signature.', $user['id']]);

    queueOrderNotification($orderId, 'Feasibility Approved');
    auditLog("KAM approved standard commercial for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Commercial approved at standard pricing. Partner notified to sign and upload SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Management — 3-option decision on pricing exception
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'management_decide') {
    verifyCsrf();
    if (!hasRole('Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId       = (int)($_POST['order_id'] ?? 0);
    $decision      = trim($_POST['management_decision'] ?? '');
    $remarks       = trim($_POST['management_remarks'] ?? '');
    $returnRemarks = trim($_POST['management_return_remarks'] ?? '');

    $allowedDecisions = ['Approve as Requested','Approve with Revised Price','Keep Standard Price','Return to KAM'];
    if (!in_array($decision, $allowedDecisions)) {
        setFlash('danger','Invalid decision. Please choose one of the available options.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $cols = array_keys($o);
    if (!in_array('management_decision', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_decision VARCHAR(100) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_return_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_return_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_final_nrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_final_nrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_final_mrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_final_mrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }

    $standardNrc  = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);
    $standardMrc  = (float)($o['standard_mrc'] ?? $o['base_mrc'] ?? 0);
    $kamNrc       = $o['kam_proposed_nrc'] !== null ? (float)$o['kam_proposed_nrc'] : null;
    $kamMrc       = $o['kam_proposed_mrc'] !== null ? (float)$o['kam_proposed_mrc'] : null;
    $bsaRevNrc    = ($o['revised_nrc'] !== null) ? (float)$o['revised_nrc'] : $standardNrc;

    if ($decision === 'Return to KAM') {
        if (!$returnRemarks) {
            setFlash('danger','Return remarks are mandatory when returning to Account Manager.');
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
        $db->prepare("UPDATE orders SET
            management_decision = ?, management_return_remarks = ?,
            management_approved_by = ?, management_approved_at = NOW(),
            kam_proposed_nrc = NULL, kam_proposed_mrc = NULL, kam_commercial_justification = NULL,
            status = 'Await Commercial Approval', updated_at = NOW()
            WHERE id = ?")->execute([$decision, $returnRemarks, $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Await Commercial Approval', "Management returned to KAM for revision. Remarks: $returnRemarks", $user['id']]);

        queueOrderNotification($orderId, 'Management Returned to KAM');
        auditLog("Management returned order #$orderId to KAM: $returnRemarks", 'orders', $orderId);
        setFlash('warning', 'Order returned to Account Manager for revision.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Determine final NRC and MRC based on decision
    if ($decision === 'Approve as Requested') {
        $finalNrc = $kamNrc ?? $bsaRevNrc;
        $finalMrc = $kamMrc ?? $standardMrc;
        $note     = 'Management approved pricing as requested by KAM.';
    } elseif ($decision === 'Approve with Revised Price') {
        // Read management's own revised values (both NRC and MRC required when setting revised price)
        try {
            $finalNrc = parseTZSInput(trim($_POST['management_final_nrc'] ?? ''), false, true, MAX_NRC_AMOUNT, 'Management Final NRC');
            $finalMrc = parseTZSInput(trim($_POST['management_final_mrc'] ?? ''), false, true, MAX_MRC_AMOUNT, 'Management Final MRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage());
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
        $note = 'Management approved with revised pricing: NRC $' . number_format($finalNrc, 2) . ', MRC $' . number_format($finalMrc, 2) . '.';
    } else { // Keep Standard Price fallback
        $finalNrc = $bsaRevNrc; // BSA-set NRC
        $finalMrc = $standardMrc;
        $note     = 'Management kept standard pricing. KAM exception not approved.';
    }

    $vatMrc = round($finalMrc * 0.18, 2);
    $totMrc = round($finalMrc + $vatMrc, 2);

    $db->prepare("UPDATE orders SET
        management_final_nrc = ?, management_final_mrc = ?,
        management_approved_price = ?, management_decision = ?,
        management_remarks = ?, management_remarks_visible = 0,
        management_approved_by = ?, management_approved_at = NOW(),
        revised_nrc = ?, vat_on_mrc = ?, total_mrc_incl_vat = ?,
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([
        $finalNrc, $finalMrc,
        $finalMrc, $decision,
        $remarks, $user['id'],
        $finalNrc !== $standardNrc ? $finalNrc : null,
        $vatMrc, $totMrc, $orderId
    ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', $note, $user['id']]);

    recordPriceChange($orderId, 'management_final_nrc', $kamNrc ?? $bsaRevNrc, $finalNrc, $user['id'], 'Management Approval', $remarks);
    recordPriceChange($orderId, 'management_final_mrc', $kamMrc ?? $standardMrc, $finalMrc, $user['id'], 'Management Approval', $remarks);

    queueOrderNotification($orderId, 'Management Pricing Approved');
    auditLog("Management decision '$decision' for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Decision recorded: ' . $decision . '. Partner notified to sign SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// Legacy alias — management_approve now maps to management_decide internally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'management_approve' || $action === 'management_reject')) {
    $_POST['management_decision'] = ($action === 'management_approve') ? 'Approve as Requested' : 'Keep Standard Price';
    $_GET['action'] = $action = 'management_decide';
    // Fall through handled below — redirect
    header('Location: ' . APP_URL . '/?page=orders&action=management_decide&order_id=' . (int)($_POST['order_id'] ?? 0));
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Generate / Print SOF (mark generation time)
// ------------------------------------------------------------------
if ($action === 'generate_sof') {
    $orderId = (int)($_GET['id'] ?? 0);
    if (isPartnerUser()) {
        $pw = partnerWhere('o');
        $stmt = $db->prepare("SELECT o.*, p.name as partner_name, p.partner_type, p.partner_category
            FROM orders o
            JOIN partners p ON o.partner_id = p.id
            WHERE o.id = ? AND {$pw['condition']}");
        $stmt->execute(array_merge([$orderId], $pw['params']));
    } else {
        // Internal staff (KAM, Management, Admin, PM, BSA) can generate and view SOF for any order
        $stmt = $db->prepare("SELECT o.*, p.name as partner_name, p.partner_type, p.partner_category
            FROM orders o
            JOIN partners p ON o.partner_id = p.id
            WHERE o.id = ?");
        $stmt->execute([$orderId]);
    }
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    $partnerId = (int)$order['partner_id'];
    $partnerKyc = getAuthoritativePartnerKyc($db, $partnerId);

    // Fallback if Partner KYC is missing or draft
    if (!$partnerKyc) {
        $partnerKyc = [
            'id'                     => null,
            'partner_id'             => $partnerId,
            'status'                 => 'Missing KYC',
            'company_name'           => $order['partner_name'] ?? '',
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
            'missing_fields'         => []
        ];
    }
    if (empty($partnerKyc['company_name'])) {
        $partnerKyc['company_name'] = $order['partner_name'] ?? '';
    }

    // Record that SOF was generated and which KYC record was used
    $kycId = $partnerKyc['id'] ?? null;
    $db->prepare("UPDATE orders SET sof_generated_at = NOW() WHERE id = ? AND sof_generated_at IS NULL")
       ->execute([$orderId]);

    auditLog("SOF generated for order #$orderId (Partner: {$partnerKyc['company_name']}, KYC Application ID: " . ($kycId ?: 'N/A') . ")", 'orders', $orderId);

    if (isset($_GET['format']) && $_GET['format'] === 'excel') {
        require_once APP_DIR . '/helpers/sof_excel.php';
        try {
            $excelFile = generateSOFExcel($order, $partnerKyc);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($excelFile) . '"');
            header('Content-Length: ' . filesize($excelFile));
            readfile($excelFile);
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Excel generation note: ' . e($e->getMessage()));
        }
    }

    $isEnterpriseOrder = (
        ($order['partner_category'] ?? '') === 'Enterprise Client' ||
        ($order['partner_type'] ?? '') === 'Enterprise' ||
        ($order['partner_type'] ?? '') === 'Government' ||
        (isset($partnerKyc['raw']['partner_category']) && $partnerKyc['raw']['partner_category'] === 'Enterprise Client') ||
        (isset($partnerKyc['raw']['partner_type']) && in_array($partnerKyc['raw']['partner_type'], ['Enterprise', 'Government']))
    );

    if (isset($_GET['format']) && $_GET['format'] === 'pdf' && $isEnterpriseOrder) {
        $pdfPath = dirname(APP_DIR) . '/SOF_Savanna_Business_Enterprise.pdf';
        if (file_exists($pdfPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="SOF_Savanna_Business_Enterprise.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        }
    }

    if ($isEnterpriseOrder) {
        include APP_DIR . '/views/orders/generate_sof_enterprise.php';
    } else {
        include APP_DIR . '/views/orders/generate_sof.php';
    }
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Upload Signed SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_signed_sof') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin() && !hasRole('KAM', 'Management')) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || !in_array($o['status'], ['Pending SOF', 'SOF Review'])) {
        setFlash('danger','Order must be in Pending SOF status.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if (empty($_FILES['signed_sof']['name']) || $_FILES['signed_sof']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger','Please select a valid signed SOF file to upload.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $ext = strtolower(pathinfo($_FILES['signed_sof']['name'], PATHINFO_EXTENSION));
    $tmpName = $_FILES['signed_sof']['tmp_name'];
    $mime = function_exists('mime_content_type') && file_exists($tmpName) ? mime_content_type($tmpName) : '';
    if ($ext !== 'pdf' || (!empty($mime) && !str_contains(strtolower($mime), 'pdf'))) {
        setFlash('danger', 'Invalid file format. Signed Service Order Form (SOF) must be uploaded as a PDF document (.pdf only).');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    try {
        $up = uploadFile($_FILES['signed_sof'], 'orders/'.$orderId.'/sof');
        $db->prepare("UPDATE orders SET
            sof_signed_file = ?, sof_signed_filename = ?, sof_uploaded_at = NOW(),
            status = 'SOF Review', updated_at = NOW()
            WHERE id = ?")->execute([$up['path'], $up['name'], $orderId]);

        $db->prepare("DELETE FROM order_documents WHERE order_id = ? AND document_type = 'Signed SOF'")
           ->execute([$orderId]);

        $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$orderId, 'sof', 'Signed SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'SOF Review', 'Partner uploaded signed SOF. Awaiting Neilos countersignature.', $user['id']]);

        queueOrderNotification($orderId, 'Signed SOF Uploaded');
        auditLog("Signed SOF uploaded for order #$orderId", 'orders', $orderId);
        setFlash('success', 'Signed SOF uploaded. Neilos will countersign and proceed to installation.');
    } catch (RuntimeException $e) {
        setFlash('danger', 'Upload failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Not Satisfied (return from Pending SOF to KAM)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_feasibility') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId       = (int)($_POST['order_id'] ?? 0);
    $returnRemarks = trim($_POST['return_remarks'] ?? '');

    if (!$returnRemarks) {
        setFlash('danger','Please enter your remarks to explain why you are returning this order.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Partner return ALWAYS routes back to KAM (Await Commercial Approval)
    // KAM can then internally route to BSA or Management
    $newStatus = 'Await Commercial Approval';

    $orderStmt = $db->prepare("SELECT standard_nrc, revised_nrc, standard_mrc, revised_mrc FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $oRow = $orderStmt->fetch();

    $db->prepare("UPDATE orders SET
        status = ?, sof_return_comments = ?,
        return_route = 'KAM', returned_by = ?, returned_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")->execute([$newStatus, $returnRemarks, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to, old_nrc, old_mrc) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$orderId, $user['id'], 'Pending SOF', $newStatus, 'Partner not satisfied with pricing', $returnRemarks, 'KAM', $oRow['revised_nrc'] ?? $oRow['standard_nrc'], $oRow['revised_mrc'] ?? $oRow['standard_mrc']]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, $newStatus, "Partner not satisfied — returned to Account Manager: $returnRemarks", $user['id']]);

    queueOrderNotification($orderId, 'Partner Returned Feasibility');
    auditLog("Partner returned order #$orderId to KAM: $returnRemarks", 'orders', $orderId);
    setFlash('warning', 'Order returned to your Account Manager for revision.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Delete uploaded signed SOF (to re-upload)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_signed_sof') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin() && !hasRole('KAM', 'Management')) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT status, sof_signed_file FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();

    // Allow removal when partner still has control: Pending SOF or SOF Review
    $allowedStatuses = ['Pending SOF', 'SOF Review'];
    if (!$o || !in_array($o['status'], $allowedStatuses)) {
        setFlash('danger','The signed SOF can only be removed while the order is in Pending SOF or SOF Review status.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Clear signed SOF from DB and reset status back to Pending SOF so partner can re-upload
    $db->prepare("UPDATE orders SET sof_signed_file = NULL, sof_signed_filename = NULL, sof_uploaded_at = NULL, status = 'Pending SOF', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);
    $db->prepare("DELETE FROM order_documents WHERE order_id = ? AND document_type = 'Signed SOF'")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'Partner removed uploaded SOF. Please re-upload the correct signed document.', $user['id']]);

    auditLog("Signed SOF deleted for order #$orderId by partner (was: {$o['status']})", 'orders', $orderId);
    setFlash('info', 'Signed SOF removed. You can now upload the correct signed document.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Internal — Return SOF for Correction (KAM & Management only)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_sof') {
    verifyCsrf();
    if (!hasRole('KAM','Management') && !isAdmin()) { setFlash('danger','Only KAM or Management can return SOF for correction.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $comments = trim($_POST['sof_return_comments'] ?? '');
    if (!$comments) { setFlash('danger','Comments required when returning SOF.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    // Set to Pending SOF and clear old rejected file so partner immediately gets the upload form
    $db->prepare("UPDATE orders SET status = 'Pending SOF', sof_return_comments = ?, sof_signed_file = NULL, sof_signed_filename = NULL, sof_uploaded_at = NULL, updated_at = NOW() WHERE id = ?")
       ->execute([$comments, $orderId]);
    $db->prepare("DELETE FROM order_documents WHERE order_id = ? AND document_type = 'Signed SOF'")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', "SOF returned for correction: $comments", $user['id']]);

    auditLog("SOF returned for correction on order #$orderId", 'orders', $orderId);
    setFlash('warning', 'SOF returned to partner for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM or Management — Upload Countersigned SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_countersigned_sof') {
    verifyCsrf();
    if (!hasRole('KAM','Management') && !isAdmin()) { setFlash('danger','Only KAM, Management, or Admin can upload countersigned SOF.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    if (empty($_FILES['countersigned_sof']['name']) || $_FILES['countersigned_sof']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger','Please select a valid countersigned SOF file to upload.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $ext = strtolower(pathinfo($_FILES['countersigned_sof']['name'], PATHINFO_EXTENSION));
    $tmpName = $_FILES['countersigned_sof']['tmp_name'];
    $mime = function_exists('mime_content_type') && file_exists($tmpName) ? mime_content_type($tmpName) : '';
    if ($ext !== 'pdf' || (!empty($mime) && !str_contains(strtolower($mime), 'pdf'))) {
        setFlash('danger', 'Invalid file format. Countersigned Service Order Form (SOF) must be uploaded as a PDF document (.pdf only).');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    try {
        $up = uploadFile($_FILES['countersigned_sof'], 'orders/'.$orderId.'/sof');
        $db->prepare("UPDATE orders SET
            countersigned_sof_file = ?, countersigned_sof_filename = ?,
            countersigned_sof_by = ?, countersigned_sof_at = NOW(),
            updated_at = NOW()
            WHERE id = ?")->execute([$up['path'], $up['name'], $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$orderId, 'countersigned_sof', 'Countersigned SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'SOF Review', 'Countersigned SOF uploaded by ' . ($user['role'] ?? 'internal') . '. Ready to proceed to project.', $user['id']]);

        queueOrderNotification($orderId, 'Countersigned SOF Uploaded');
        auditLog("Countersigned SOF uploaded for order #$orderId by {$user['role']}", 'orders', $orderId);
        setFlash('success', 'Countersigned SOF uploaded successfully. You can now proceed to project.');
    } catch (RuntimeException $e) {
        setFlash('danger', 'Upload failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM or Management — Proceed to Project (Installation)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'proceed_to_project') {
    verifyCsrf();
    if (!hasRole('KAM','Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();

    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }
    if (!$o['countersigned_sof_file']) {
        setFlash('danger','Cannot proceed to project until countersigned SOF is uploaded.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $db->prepare("UPDATE orders SET status = 'Installation', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'proceed_to_project');

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', 'Countersigned SOF verified — order released to Project Installation.', $user['id']]);

    queueOrderNotification($orderId, 'Proceed to Project');
    auditLog("Order #$orderId moved to Installation by {$user['role']}", 'orders', $orderId);
    setFlash('success', 'Order moved to Installation. Project Manager should assign a contractor.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager — Assign Contractor
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign_contractor') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $contractorPartnerId = (int)($_POST['contractor_partner_id'] ?? 0);
    $contractorUserId = (int)($_POST['contractor_user_id'] ?? 0) ?: null;
    $targetDate = $_POST['target_date'] ?? null;
    $workOrderNotes = trim($_POST['work_order_notes'] ?? '');

    if (!$contractorPartnerId) {
        setFlash('danger','Please select a contractor.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Close any existing open assignments
    $db->prepare("UPDATE contractor_assignments SET status = 'Completed' WHERE order_id = ? AND status NOT IN ('Completed')")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO contractor_assignments (order_id, contractor_partner_id, contractor_user_id, assigned_by, target_date, work_order_notes) VALUES (?,?,?,?,?,?)")
       ->execute([$orderId, $contractorPartnerId, $contractorUserId, $user['id'], $targetDate ?: null, $workOrderNotes]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "Contractor assigned (partner #$contractorPartnerId).", $user['id']]);

    queueOrderNotification($orderId, 'Contractor Assigned');
    auditLog("Contractor assigned to order #$orderId", 'orders', $orderId);
    setFlash('success', 'Contractor assigned successfully.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA — Set NOC IP Configured flag
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_noc_ip') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $value   = (int)($_POST['noc_ip_configured'] ?? 0) ? 1 : 0;

    $db->prepare("UPDATE orders SET noc_ip_configured = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$value, $orderId]);

    $label = $value ? 'NOC IP configuration marked as COMPLETE — Speed Test and Ping Test evidence are now required.' : 'NOC IP configuration flag cleared.';
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) SELECT id, status, ?, ? FROM orders WHERE id = ?")
       ->execute([$label, $user['id'], $orderId]);

    auditLog("NOC IP configured flag set to $value for order #$orderId", 'orders', $orderId);
    setFlash('success', $label);
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}


// ------------------------------------------------------------------
// POST: Project Manager/BSA — Submit Job as Complete (Installation → Testing)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['submit_installation_complete', 'submit_completion'])) {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId   = (int)($_POST['order_id'] ?? 0);
    $remarks   = trim($_POST['completion_remarks'] ?? '');
    $pickupLoc = trim($_POST['pickup_location'] ?? '');

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    if (!empty($pickupLoc)) {
        $db->prepare("UPDATE orders SET pickup_location = ? WHERE id = ?")->execute([$pickupLoc, $orderId]);
    }

    // Update assignment if any exists
    $db->prepare("UPDATE contractor_assignments SET status = 'Completed Submitted', completed_at = NOW(), completion_remarks = ? WHERE order_id = ? AND status != 'Completed'")
       ->execute([$remarks, $orderId]);

    // Move order to Testing status
    $db->prepare("UPDATE orders SET status = 'Testing', installation_date = COALESCE(installation_date, CURDATE()), updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'submit_completion');

    $note = "Job completion submitted by Project Manager. " . ($remarks ? "Remarks: $remarks" : "Awaiting Testing — Internal Review.");
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', $note, $user['id']]);

    auditLog("Project Manager submitted job as complete for order #$orderId, moved to Testing", 'orders', $orderId);
    setFlash('success', 'Installation submitted. Order is now in <strong>Testing — Internal Review</strong>.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA/Admin — Update Pickup Location
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_pickup_location') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA','Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId   = (int)($_POST['order_id'] ?? 0);
    $pickupLoc = trim($_POST['pickup_location'] ?? '');

    if ($orderId > 0) {
        $db->prepare("UPDATE orders SET pickup_location = ? WHERE id = ?")->execute([$pickupLoc, $orderId]);
        $db->prepare("UPDATE contractor_assignments SET pickup_location = ? WHERE order_id = ?")->execute([$pickupLoc, $orderId]);
        auditLog("Pickup location updated for order #$orderId to '$pickupLoc'", 'orders', $orderId);
        setFlash('success', 'Pickup location successfully updated.');
    }
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA — Approve Testing (Testing → UAT)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_testing') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId   = (int)($_POST['order_id'] ?? 0);
    $pickupLoc = trim($_POST['pickup_location'] ?? '');

    $orderStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'Testing') { setFlash('danger','Order must be in Testing status.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    if (!empty($pickupLoc)) {
        $db->prepare("UPDATE orders SET pickup_location = ? WHERE id = ?")->execute([$pickupLoc, $orderId]);
        $db->prepare("UPDATE contractor_assignments SET pickup_location = ? WHERE order_id = ?")->execute([$pickupLoc, $orderId]);
    }

    // Ensure columns exist on orders table
    try { $db->exec("ALTER TABLE orders ADD COLUMN uat_notified_at DATETIME NULL"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE orders ADD COLUMN uat_deadline DATETIME NULL"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE orders ADD COLUMN uat_submitted_at DATETIME NULL"); } catch (Throwable $e) {}

    // Calculate 72-Hour Working Days (3 business days) deadline
    $uatDeadline = calculateUatBusinessDeadline(new DateTime(), 3)->format('Y-m-d H:i:s');

    $db->prepare("UPDATE orders SET status = 'UAT', uat_notified_at = NOW(), uat_submitted_at = NOW(), uat_deadline = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$uatDeadline, $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'UAT', 'Testing approved (NOC IP configured). Partner notified for UAT acceptance (72hr working-days window).', $user['id']]);

    queueOrderNotification($orderId, 'Testing Approved');
    auditLog("Testing approved for order #$orderId, moved to UAT (Deadline: $uatDeadline)", 'orders', $orderId);
    setFlash('success', 'Testing approved. Partner notified for UAT (72-hour business days auto-acceptance window active).');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA — Return to Contractor from Testing
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_contractor') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $comments = trim($_POST['return_comments'] ?? '');
    if (!$comments) { setFlash('danger','Comments required when returning to contractor.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    // Ensure rejection_reason column exists on contractor_assignments
    try { $db->exec("ALTER TABLE contractor_assignments ADD COLUMN rejection_reason TEXT NULL"); } catch (Throwable $e) {}

    // Update assignment status and save rejection reason
    $db->prepare("UPDATE contractor_assignments SET status = 'Returned', rejection_reason = ?, completion_remarks = ? WHERE order_id = ? AND status != 'Completed'")
       ->execute([$comments, $comments, $orderId]);

    $db->prepare("UPDATE orders SET status = 'Installation', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "Returned to contractor: $comments", $user['id']]);

    auditLog("Order #$orderId returned to contractor from Testing: $comments", 'orders', $orderId);
    setFlash('warning', 'Job returned to contractor for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA — Upload UAT Form (Enterprise & Route to KAM)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_uat_form') {
    verifyCsrf();
    if (!hasRole('Project Manager', 'BSA') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location:' . APP_URL . '/?page=orders');
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $remarks = trim($_POST['uat_remarks'] ?? '');

    $orderStmt = $db->prepare("SELECT o.*, p.partner_type, p.partner_category FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || !in_array($o['status'], ['Testing', 'UAT', 'Installation'])) {
        setFlash('danger', 'Order must be in Testing or UAT status to upload UAT form.');
        header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    if (empty($_FILES['uat_form']['name']) || $_FILES['uat_form']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Please select a valid UAT form file to upload.');
        header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    try {
        $up = uploadFile($_FILES['uat_form'], 'orders/' . $orderId . '/uat');

        try { $db->exec("ALTER TABLE orders ADD COLUMN uat_signoff_filename VARCHAR(255) NULL"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE orders ADD COLUMN uat_uploaded_by INT NULL"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE orders ADD COLUMN uat_uploaded_at DATETIME NULL"); } catch (Throwable $e) {}

        // Delete previous UAT document if replacing
        $db->prepare("DELETE FROM order_documents WHERE order_id = ? AND doc_type = 'uat_signoff'")
           ->execute([$orderId]);

        // Insert into order_documents
        $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by, is_partner_visible)
            VALUES (?,?,?,?,?,?,?,1)")
           ->execute([$orderId, 'uat_signoff', 'UAT Form', $up['name'], $up['path'], $up['size'], $user['id']]);

        // Update orders: set status to UAT and save file metadata
        $db->prepare("UPDATE orders SET
            status = 'UAT',
            uat_signoff_file = ?,
            uat_signoff_filename = ?,
            uat_uploaded_by = ?,
            uat_uploaded_at = NOW(),
            uat_submitted_at = NOW(),
            updated_at = NOW()
            WHERE id = ?")
           ->execute([$up['path'], $up['name'], $user['id'], $orderId]);

        $timelineNote = "Project Manager ({$user['full_name']}) uploaded completed UAT form ({$up['name']}) and routed order to KAM for review and acceptance.";
        if ($remarks) {
            $timelineNote .= " Remarks: $remarks";
        }
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'UAT', $timelineNote, $user['id']]);

        queueOrderNotification($orderId, 'UAT Form Uploaded');
        auditLog("UAT form uploaded for order #$orderId by PM ({$user['full_name']}). Routed to KAM for acceptance.", 'orders', $orderId);

        setFlash('success', 'UAT form uploaded successfully. Order routed to KAM for review and acceptance.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Failed to upload UAT form: ' . e($e->getMessage()));
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }
}

// ------------------------------------------------------------------
// POST: KAM (Enterprise) / Partner (Standard) — Accept Service (UAT → Closed)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'uat_accept') {
    verifyCsrf();

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT o.*, p.partner_type, p.partner_category FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'UAT') {
        setFlash('danger', 'Order must be in UAT status.');
        header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $isEnterpriseOrder = (
        ($o['partner_category'] ?? '') === 'Enterprise Client' ||
        ($o['partner_type'] ?? '') === 'Enterprise' ||
        ($o['partner_type'] ?? '') === 'Government'
    );

    // Permission check: Enterprise clients require KAM/Management/Admin; standard orders require Partner/Admin
    if ($isEnterpriseOrder) {
        if (!hasRole('KAM', 'Management') && !isAdmin()) {
            setFlash('danger', 'Only the assigned KAM or Management can accept service for Enterprise Clients.');
            header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }
    } else {
        if (!isPartnerUser() && !isAdmin()) {
            setFlash('danger', 'Only the partner can accept service.');
            header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }
    }

    $today = date('Y-m-d');
    try { $db->exec("ALTER TABLE orders ADD COLUMN activation_date DATE NULL"); } catch (Throwable $e) {}

    $db->prepare("UPDATE orders SET
        status = 'Closed',
        uat_accepted_at = NOW(), uat_accepted_by = ?,
        closed_date = ?, billing_start_date = ?,
        activation_date = ?,
        installation_date = COALESCE(installation_date, ?),
        is_active = 1,
        updated_at = NOW()
        WHERE id = ? AND status = 'UAT'")->execute([$user['id'], $today, $today, $today, $today, $orderId]);

    $acceptorTitle = $isEnterpriseOrder ? "KAM ({$user['full_name']})" : "Partner ({$user['full_name']})";
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Closed', "$acceptorTitle accepted service. Order closed. Closed Date: $today. Billing Start Date: $today.", $user['id']]);

    // Create active service record (Service ID SVC-YYMMDD-XXX mirrors Order Number FR-YYMMDD-XXX)
    $serviceId = !empty($o['service_id']) ? $o['service_id'] : generateServiceId($o['order_number'] ?? '');
    $circuitId = !empty($o['circuit_id']) ? $o['circuit_id'] : ('CKT-' . $o['order_number']);
    $bw = $o['bandwidth'] ?: ($o['fttx_package'] ?: ($o['aggregate_capacity'] ? ($o['aggregate_capacity'] . ' Mbps') : 'Standard'));
    $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE 
            customer_name=VALUES(customer_name),
            service_type=VALUES(service_type),
            circuit_id=VALUES(circuit_id),
            bandwidth_capacity=VALUES(bandwidth_capacity),
            location=VALUES(location),
            billing_start_date=VALUES(billing_start_date),
            status='Active'")->execute([
        $serviceId, $orderId, $o['partner_id'], $o['customer_name'], $o['service_type'],
        $circuitId, $bw, $o['customer_location'], $o['building_name'],
        $o['kam_id'], $today, $today, 'Active', 'Unknown'
    ]);

    try {
        $db->exec("ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_3");
    } catch (Throwable $e) {}
    try {
        $db->exec("ALTER TABLE orders MODIFY COLUMN service_id VARCHAR(100) NULL");
    } catch (Throwable $e) {}

    $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ? WHERE id = ?")
       ->execute([$serviceId, $circuitId, $orderId]);

    queueOrderNotification($orderId, $isEnterpriseOrder ? 'KAM Accepted Service' : 'Partner Accepted Service');
    auditLog("$acceptorTitle accepted service — order #$orderId CLOSED. Billing start: $today", 'orders', $orderId, '', "closed_date=$today");
    setFlash('success', "Service accepted successfully. Order is now marked as <strong>Closed</strong>. Billing start date: <strong>$today</strong>.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Return to Installation (from UAT or Testing)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_installation') {
    verifyCsrf();
    if (!isPartnerUser() && !hasRole('Project Manager', 'KAM', 'BSA', 'Management') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location:' . APP_URL . '/?page=orders');
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason  = trim($_POST['return_reason'] ?? '');
    if (!$reason) {
        setFlash('danger', 'Remarks required when returning to installation.');
        header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $orderStmt = $db->prepare("SELECT o.*, p.partner_type, p.partner_category FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || !in_array($o['status'], ['UAT', 'Testing'])) {
        setFlash('danger', 'Order must be in UAT or Testing status.');
        header('Location:' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $fromStatus = $o['status'];
    $db->prepare("UPDATE orders SET status = 'Installation', uat_return_reason = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$reason, $orderId]);

    $userRole = $_SESSION['user_role'] ?? 'User';
    $db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to) VALUES (?,?,?,?,?,?,?)")
       ->execute([$orderId, $user['id'], $fromStatus, 'Installation', 'Quality / UAT issue', $reason, 'Installation']);

    $timelineNote = "$userRole ({$user['full_name']}) returned order to Installation: $reason";
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', $timelineNote, $user['id']]);

    try {
        $db->prepare("UPDATE contractor_assignments SET status = 'Returned', rejection_reason = ?, completion_remarks = ? WHERE order_id = ? AND status != 'Completed'")
           ->execute([$reason, $reason, $orderId]);
    } catch (Throwable $e) {}

    queueOrderNotification($orderId, 'Order Returned to Installation');
    auditLog("Order #$orderId returned to Installation by $userRole ({$user['full_name']}): $reason", 'orders', $orderId);
    setFlash('warning', 'Order returned to Installation for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Disabled Manual Billing Date Adjustment
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'adjust_billing_date') {
    setFlash('danger', 'Manual adjustment of billing start date is not permitted.');
    header('Location: ' . APP_URL . '/?page=orders');
    exit;
}

// ------------------------------------------------------------------
// POST: Edit Order Details (Admin, Management, KAM across all statuses with audit history)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'edit_order' || $action === 'edit_closed_order')) {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management') && !hasRole('KAM') && !hasPermission('orders.edit')) {
        setFlash('danger', 'Access denied. You do not have permission to edit order details.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . (int)($_POST['order_id'] ?? 0));
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('danger', 'Order not found.');
        header('Location: ' . APP_URL . '/?page=orders');
        exit;
    }

    $auditReason = trim($_POST['audit_reason'] ?? '');
    if (empty($auditReason)) {
        setFlash('danger', 'An audit reason is strictly required when editing order details.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Client / Contact Fields
    $customerName   = trim($_POST['customer_name'] ?? $order['customer_name']);
    $contactName    = trim($_POST['customer_contact_name'] ?? ($order['customer_contact_name'] ?? ''));
    $contactPhone   = trim($_POST['customer_contact_phone'] ?? ($order['customer_contact_phone'] ?? ''));
    $contactEmail   = trim($_POST['customer_contact_email'] ?? ($order['customer_contact_email'] ?? ''));

    // Location / Site Fields
    $location       = trim($_POST['customer_location'] ?? ($order['customer_location'] ?? ''));
    $pickupLocation = trim($_POST['pickup_location'] ?? ($order['pickup_location'] ?? ''));
    $buildingName   = trim($_POST['building_name'] ?? ($order['building_name'] ?? ''));
    $floorNumber    = trim($_POST['floor_number'] ?? ($order['floor_number'] ?? ''));
    $apartmentNumber= trim($_POST['apartment_number'] ?? ($order['apartment_number'] ?? ''));
    $cityRegion     = trim($_POST['city_region'] ?? ($order['city_region'] ?? ''));
    $gpsCoordinates = trim($_POST['gps_coordinates'] ?? ($order['gps_coordinates'] ?? ''));
    $siteCategory   = trim($_POST['site_category'] ?? ($order['site_category'] ?? ''));

    // Technical / Service Fields
    $serviceType    = trim($_POST['service_type'] ?? ($order['service_type'] ?? ''));
    $fttxPackage    = trim($_POST['fttx_package'] ?? ($order['fttx_package'] ?? ''));
    $bandwidth      = trim($_POST['bandwidth'] ?? ($order['bandwidth'] ?? ''));
    $capacity       = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : ($order['capacity'] ?? null);
    $capacityUnit   = trim($_POST['capacity_unit'] ?? ($order['capacity_unit'] ?? ''));
    $popLocation    = trim($_POST['pop_location'] ?? ($order['pop_location'] ?? ''));
    $nniLocation    = trim($_POST['nni_location'] ?? ($order['nni_location'] ?? ''));
    $nniPortSize    = trim($_POST['nni_port_size'] ?? ($order['nni_port_size'] ?? '10G'));
    $aggregateCap   = trim($_POST['aggregate_capacity'] ?? ($order['aggregate_capacity'] ?? ''));
    $interfaceType  = trim($_POST['interface_type'] ?? ($order['interface_type'] ?? ''));
    $connectorType  = trim($_POST['connector_type'] ?? ($order['connector_type'] ?? ''));
    $mediaType      = trim($_POST['media_type'] ?? ($order['media_type'] ?? ''));
    $powerType      = trim($_POST['power_type'] ?? ($order['power_type'] ?? ''));
    $rackSpace      = trim($_POST['rack_space'] ?? ($order['rack_space'] ?? ''));
    $circuitId      = trim($_POST['circuit_id'] ?? ($order['circuit_id'] ?? ''));
    $serviceId      = trim($_POST['service_id'] ?? ($order['service_id'] ?? ''));
    $sofNumber      = trim($_POST['sof_number'] ?? ($order['sof_number'] ?? ''));
    $contractTerm   = trim($_POST['contract_term'] ?? ($order['contract_term'] ?? ''));
    $specialReqs    = trim($_POST['special_requirements'] ?? ($order['special_requirements'] ?? ''));

    // Pricing Fields (if submitted)
    $standardMrc    = isset($_POST['standard_mrc']) && $_POST['standard_mrc'] !== '' ? (float)$_POST['standard_mrc'] : ($order['standard_mrc'] !== null ? (float)$order['standard_mrc'] : null);
    $standardNrc    = isset($_POST['standard_nrc']) && $_POST['standard_nrc'] !== '' ? (float)$_POST['standard_nrc'] : ($order['standard_nrc'] !== null ? (float)$order['standard_nrc'] : null);
    $revisedMrc     = isset($_POST['revised_mrc']) && $_POST['revised_mrc'] !== '' ? (float)$_POST['revised_mrc'] : ($order['revised_mrc'] !== null ? (float)$order['revised_mrc'] : null);
    $revisedNrc     = isset($_POST['revised_nrc']) && $_POST['revised_nrc'] !== '' ? (float)$_POST['revised_nrc'] : ($order['revised_nrc'] !== null ? (float)$order['revised_nrc'] : null);
    $mrcCurrency    = trim($_POST['mrc_currency'] ?? ($order['mrc_currency'] ?? 'USD'));
    $discountPct    = isset($_POST['discount_pct']) && $_POST['discount_pct'] !== '' ? (float)$_POST['discount_pct'] : ($order['discount_pct'] !== null ? (float)$order['discount_pct'] : null);

    // Lifecycle Dates
    $parseDate = function($val, $current) {
        $val = trim($val ?? '');
        if ($val === '') return null;
        $dt = DateTime::createFromFormat('Y-m-d', $val);
        return ($dt && $dt->format('Y-m-d') === $val) ? $val : ($current ?: null);
    };

    $installationDate = isset($_POST['installation_date']) ? $parseDate($_POST['installation_date'], $order['installation_date'] ?? null) : ($order['installation_date'] ?? null);
    $activationDate   = isset($_POST['activation_date']) ? $parseDate($_POST['activation_date'], $order['activation_date'] ?? null) : ($order['activation_date'] ?? null);
    $billingStartDate = isset($_POST['billing_start_date']) ? $parseDate($_POST['billing_start_date'], $order['billing_start_date'] ?? null) : ($order['billing_start_date'] ?? null);
    $closedDate       = isset($_POST['closed_date']) ? $parseDate($_POST['closed_date'], $order['closed_date'] ?? null) : ($order['closed_date'] ?? null);

    // Active Status
    $isActive = isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : (isset($order['is_active']) ? (int)$order['is_active'] : 1);

    // Compare and build granular diff
    $changes = [];
    $trackStr = function($label, $old, $new) use (&$changes) {
        $oldClean = trim((string)($old ?? ''));
        $newClean = trim((string)($new ?? ''));
        if ($oldClean !== $newClean) {
            $changes[] = "• {$label} changed from \"" . ($oldClean ?: 'None') . "\" to \"" . ($newClean ?: 'None') . "\"";
        }
    };
    $trackNum = function($label, $old, $new, $isCurrency = false) use (&$changes) {
        $oldVal = $old !== null ? (float)$old : null;
        $newVal = $new !== null ? (float)$new : null;
        if ($oldVal !== $newVal) {
            $oldStr = $oldVal !== null ? ($isCurrency ? '$' . number_format($oldVal, 2) : (string)$oldVal) : 'None';
            $newStr = $newVal !== null ? ($isCurrency ? '$' . number_format($newVal, 2) : (string)$newVal) : 'None';
            $changes[] = "• {$label} changed from {$oldStr} to {$newStr}";
        }
    };
    $trackDate = function($label, $old, $new) use (&$changes) {
        $oldClean = trim((string)($old ?? ''));
        $newClean = trim((string)($new ?? ''));
        if ($oldClean !== $newClean) {
            $changes[] = "• {$label} changed from \"" . ($oldClean ?: 'None') . "\" to \"" . ($newClean ?: 'None') . "\"";
        }
    };

    // Customer & Contact
    $trackStr('Customer Name', $order['customer_name'] ?? '', $customerName);
    $trackStr('Contact Person', $order['customer_contact_name'] ?? '', $contactName);
    $trackStr('Contact Phone', $order['customer_contact_phone'] ?? '', $contactPhone);
    $trackStr('Contact Email', $order['customer_contact_email'] ?? '', $contactEmail);

    // Location & Site
    $trackStr('Customer Location', $order['customer_location'] ?? '', $location);
    $trackStr('Pickup Location', $order['pickup_location'] ?? '', $pickupLocation);
    $trackStr('Building Name', $order['building_name'] ?? '', $buildingName);
    $trackStr('Floor Number', $order['floor_number'] ?? '', $floorNumber);
    $trackStr('Apartment / Room', $order['apartment_number'] ?? '', $apartmentNumber);
    $trackStr('City / Region', $order['city_region'] ?? '', $cityRegion);
    $trackStr('GPS Coordinates', $order['gps_coordinates'] ?? '', $gpsCoordinates);
    $trackStr('Site Category', $order['site_category'] ?? '', $siteCategory);

    // Technical
    $trackStr('Service Type', $order['service_type'] ?? '', $serviceType);
    $trackStr('FTTX Package', $order['fttx_package'] ?? '', $fttxPackage);
    $trackStr('Bandwidth', $order['bandwidth'] ?? '', $bandwidth);
    $trackNum('Capacity', $order['capacity'] ?? null, $capacity);
    $trackStr('Capacity Unit', $order['capacity_unit'] ?? '', $capacityUnit);
    $trackStr('POP Location', $order['pop_location'] ?? '', $popLocation);
    $trackStr('NNI Location', $order['nni_location'] ?? '', $nniLocation);
    $trackStr('NNI Port Size', $order['nni_port_size'] ?? '', $nniPortSize);
    $trackStr('Aggregate Capacity', $order['aggregate_capacity'] ?? '', $aggregateCap);
    $trackStr('Interface Type', $order['interface_type'] ?? '', $interfaceType);
    $trackStr('Connector Type', $order['connector_type'] ?? '', $connectorType);
    $trackStr('Media Type', $order['media_type'] ?? '', $mediaType);
    $trackStr('Power Type', $order['power_type'] ?? '', $powerType);
    $trackStr('Rack Space', $order['rack_space'] ?? '', $rackSpace);
    $trackStr('Circuit ID', $order['circuit_id'] ?? '', $circuitId);
    $trackStr('Service ID', $order['service_id'] ?? '', $serviceId);
    $trackStr('SOF Number', $order['sof_number'] ?? '', $sofNumber);
    $trackStr('Contract Term', $order['contract_term'] ?? '', $contractTerm);
    $trackStr('Special Requirements', $order['special_requirements'] ?? '', $specialReqs);

    // Pricing
    $trackNum('Standard MRC', $order['standard_mrc'] ?? null, $standardMrc, true);
    $trackNum('Standard NRC', $order['standard_nrc'] ?? null, $standardNrc, true);
    $trackNum('Revised MRC', $order['revised_mrc'] ?? null, $revisedMrc, true);
    $trackNum('Revised NRC', $order['revised_nrc'] ?? null, $revisedNrc, true);
    $trackStr('Currency', $order['mrc_currency'] ?? '', $mrcCurrency);
    $trackNum('Discount %', $order['discount_pct'] ?? null, $discountPct);

    // Dates
    $trackDate('Installation Date', $order['installation_date'] ?? '', $installationDate);
    $trackDate('Activation Date', $order['activation_date'] ?? '', $activationDate);
    $trackDate('Billing Start Date', $order['billing_start_date'] ?? '', $billingStartDate);
    $trackDate('Closed Date', $order['closed_date'] ?? '', $closedDate);

    // Active Status
    $oldActive = isset($order['is_active']) ? (int)$order['is_active'] : 1;
    if ($isActive !== $oldActive) {
        $changes[] = "• Active Status changed from \"" . ($oldActive ? 'Active' : 'Inactive') . "\" to \"" . ($isActive ? 'Active' : 'Inactive') . "\"";
    }

    // Execute UPDATE on orders
    $db->prepare("UPDATE orders SET
        customer_name = ?, customer_contact_name = ?, customer_contact_phone = ?,
        customer_contact_email = ?, customer_location = ?, pickup_location = ?,
        building_name = ?, floor_number = ?, apartment_number = ?, city_region = ?,
        gps_coordinates = ?, site_category = ?, service_type = ?, fttx_package = ?,
        bandwidth = ?, capacity = ?, capacity_unit = ?, pop_location = ?,
        nni_location = ?, nni_port_size = ?, aggregate_capacity = ?, interface_type = ?, connector_type = ?,
        media_type = ?, power_type = ?, rack_space = ?, circuit_id = ?, service_id = ?,
        sof_number = ?, contract_term = ?, special_requirements = ?,
        standard_mrc = ?, standard_nrc = ?, revised_mrc = ?, revised_nrc = ?,
        mrc_currency = ?, discount_pct = ?,
        installation_date = ?, activation_date = ?, billing_start_date = ?, closed_date = ?,
        is_active = ?, updated_at = NOW()
        WHERE id = ?")->execute([
        $customerName, $contactName ?: null, $contactPhone ?: null,
        $contactEmail ?: null, $location ?: null, $pickupLocation ?: null,
        $buildingName ?: null, $floorNumber ?: null, $apartmentNumber ?: null, $cityRegion ?: null,
        $gpsCoordinates ?: null, $siteCategory ?: null, $serviceType ?: null, $fttxPackage ?: null,
        $bandwidth ?: null, $capacity, $capacityUnit ?: null, $popLocation ?: null,
        $nniLocation ?: null, $nniPortSize ?: '10G', $aggregateCap ?: null, $interfaceType ?: null, $connectorType ?: null,
        $mediaType ?: null, $powerType ?: null, $rackSpace ?: null, $circuitId ?: null, $serviceId ?: null,
        $sofNumber ?: null, $contractTerm ?: null, $specialReqs ?: null,
        $standardMrc, $standardNrc, $revisedMrc, $revisedNrc,
        $mrcCurrency ?: 'USD', $discountPct,
        $installationDate, $activationDate, $billingStartDate, $closedDate,
        $isActive, $orderId
    ]);

    // Update corresponding active_services record if it exists
    $serviceActiveStatus = $isActive ? 'Active' : 'Suspended';
    $db->prepare("UPDATE active_services SET
        customer_name = ?, location = ?, building_name = ?,
        circuit_id = ?, bandwidth_capacity = ?, service_type = ?,
        activation_date = ?, billing_start_date = ?, status = ?,
        updated_at = NOW()
        WHERE order_id = ?")->execute([
        $customerName, $location, $buildingName,
        $circuitId, $bandwidth ?: $fttxPackage, $serviceType,
        $activationDate, $billingStartDate, $serviceActiveStatus,
        $orderId
    ]);

    $changeSummary = !empty($changes) ? implode("\n", $changes) : "• No field values altered (metadata re-verified)";

    // Insert timeline record
    $timelineNote = "Order details modified by {$user['full_name']} (" . ($user['role'] ?? 'User') . ").\nAudit Reason: {$auditReason}\nModifications:\n{$changeSummary}";
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, $order['status'], $timelineNote, $user['id']]);

    // System audit log
    $inlineChanges = !empty($changes) ? implode('; ', $changes) : 'No field values altered';
    auditLog("Order #{$order['order_number']} edited by {$user['full_name']}. Reason: {$auditReason}. Changes: {$inlineChanges}", 'orders', $orderId);

    $countChanges = count($changes);
    setFlash('success', "Order <strong>" . e($order['order_number']) . "</strong> updated successfully. {$countChanges} modification(s) recorded in audit history.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Toggle Active / Inactive Status for Closed Order
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_active') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management', 'Project Manager', 'KAM') && !hasPermission('orders.edit')) {
        setFlash('danger', 'Access denied. You do not have permission to change client service active status.');
        header('Location: ' . (APP_URL . '/?page=closed_orders'));
        exit;
    }

    $orderId   = (int)($_POST['order_id'] ?? $_POST['id'] ?? 0);
    $targetVal = isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : 1;
    $redirectUrl = trim($_POST['redirect_url'] ?? '');

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('danger', 'Order not found.');
        header('Location: ' . ($redirectUrl ?: (APP_URL . '/?page=closed_orders')));
        exit;
    }

    $curActive = isset($order['is_active']) ? (int)$order['is_active'] : 1;
    $newActive = $targetVal;
    $statusText = $newActive === 1 ? 'Active' : 'Inactive';

    // Update orders table
    $db->prepare("UPDATE orders SET is_active = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$newActive, $orderId]);

    // Update active_services table
    $asStatus = $newActive === 1 ? 'Active' : 'Inactive';
    $db->prepare("UPDATE active_services SET status = ?, updated_at = NOW() WHERE order_id = ?")
       ->execute([$asStatus, $orderId]);

    // Audit log
    $user = currentUser();
    $userName = $user['full_name'] ?? 'System';
    auditLog("Order #{$order['order_number']} (Customer: {$order['customer_name']}) service active status changed from " . ($curActive ? 'Active' : 'Inactive') . " to {$statusText} by {$userName}.", 'orders', $orderId);

    setFlash('success', "Service for customer <strong>" . e($order['customer_name']) . "</strong> (Order #" . e($order['order_number']) . ") has been marked as <strong>{$statusText}</strong>.");

    if ($redirectUrl) {
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: ' . APP_URL . '/?page=closed_orders');
    }
    exit;
}

// ------------------------------------------------------------------
// POST: Bulk Toggle Active / Inactive Status for Closed Orders
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_toggle_active') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management', 'Project Manager', 'KAM') && !hasPermission('orders.edit')) {
        setFlash('danger', 'Access denied. You do not have permission to change client service active status.');
        header('Location: ' . (APP_URL . '/?page=closed_orders'));
        exit;
    }

    $rawIds = $_POST['order_ids'] ?? '';
    if (is_array($rawIds)) {
        $ids = array_map('intval', $rawIds);
    } else {
        $ids = array_filter(array_map('intval', explode(',', (string)$rawIds)));
    }

    $targetVal = isset($_POST['target_status']) ? (int)(bool)$_POST['target_status'] : 1;
    $redirectUrl = trim($_POST['redirect_url'] ?? '');
    $statusText = $targetVal === 1 ? 'Active' : 'Inactive';
    $asStatus   = $targetVal === 1 ? 'Active' : 'Inactive';

    if (empty($ids)) {
        setFlash('warning', 'No orders were selected for bulk update.');
        header('Location: ' . ($redirectUrl ?: (APP_URL . '/?page=closed_orders')));
        exit;
    }

    $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));

    // Update orders table
    $updateParams = array_merge([$targetVal], $ids);
    $db->prepare("UPDATE orders SET is_active = ?, updated_at = NOW() WHERE id IN ($inPlaceholders)")
       ->execute($updateParams);

    // Update active_services table
    $asParams = array_merge([$asStatus], $ids);
    $db->prepare("UPDATE active_services SET status = ?, updated_at = NOW() WHERE order_id IN ($inPlaceholders)")
       ->execute($asParams);

    $user = currentUser();
    $userName = $user['full_name'] ?? 'System';
    $countUpdated = count($ids);
    auditLog("Bulk service status update: {$countUpdated} order(s) marked as {$statusText} by {$userName}. (IDs: " . implode(',', $ids) . ")", 'orders');

    setFlash('success', "Successfully updated <strong>{$countUpdated}</strong> client service(s) to <strong>{$statusText}</strong>.");

    if ($redirectUrl) {
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: ' . APP_URL . '/?page=closed_orders');
    }
    exit;
}

// ------------------------------------------------------------------
// POST: Update order status (Admin / Management / Project Manager override)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management', 'Project Manager') && !hasPermission('orders.edit')) { 
        setFlash('danger','Access denied. Admin, Management, or Project Manager only.'); 
        header('Location: ' . APP_URL . '/?page=orders'); 
        exit; 
    }

    $orderId       = (int)($_POST['order_id'] ?? 0);
    $overrideType  = trim($_POST['override_type'] ?? 'direct'); // 'return', 'skip', 'direct'
    $overrideNote  = trim($_POST['note'] ?? '');
    $newServiceType = trim($_POST['new_service_type'] ?? '');

    $curStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $curStmt->execute([$orderId]);
    $order = $curStmt->fetch();
    if (!$order) {
        setFlash('danger', 'Order not found.');
        header('Location: ' . APP_URL . '/?page=orders');
        exit;
    }

    $currentStatus = $order['status'];
    $stageRank = [
        'Feasibility Review'        => 1,
        'Await Commercial Approval' => 2,
        'Management Approval'       => 3,
        'Pending SOF'               => 4,
        'SOF Review'                => 5,
        'Installation'              => 6,
        'Testing'                   => 7,
        'UAT'                       => 8,
        'Closed'                    => 9
    ];

    if ($overrideType === 'return') {
        $newStatus = trim($_POST['return_to_status'] ?? '');
        $returnReason = trim($_POST['return_reason_preset'] ?? '');
        $customRemarks = trim($_POST['return_remarks'] ?? '');
        if ($returnReason && $customRemarks) {
            $reasonSummary = "$returnReason: $customRemarks";
        } else {
            $reasonSummary = $returnReason ?: ($customRemarks ?: $overrideNote);
        }

        if (empty($newStatus)) {
            setFlash('danger', 'Please select a valid previous stage to return to.');
            header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }
        if (empty($reasonSummary)) {
            setFlash('danger', 'A return reason is mandatory when performing a Return Override.');
            header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }

        // Determine route target
        $route = match($newStatus) {
            'Feasibility Review'                          => 'BSA',
            'Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review' => 'KAM',
            'Installation', 'Testing'                     => 'Project Manager',
            'UAT'                                         => 'Partner',
            default                                       => 'Project Manager'
        };

        // Insert into order_returns table
        try {
            $retStmt = $db->prepare("
                INSERT INTO order_returns 
                (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to, old_nrc, new_nrc, old_mrc, new_mrc, returned_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $retStmt->execute([
                $orderId,
                $user['id'],
                $currentStatus,
                $newStatus,
                substr($reasonSummary, 0, 190),
                $customRemarks ?: $reasonSummary,
                $route,
                $order['standard_nrc'] ?? 0,
                $order['revised_nrc'] ?? $order['standard_nrc'] ?? 0,
                $order['standard_mrc'] ?? 0,
                $order['revised_mrc'] ?? $order['standard_mrc'] ?? 0
            ]);
        } catch (Throwable $e) {}

        // Rollback state & flags if needed
        $resetUpdates = ["status = ?", "updated_at = NOW()"];
        $resetParams  = [$newStatus];

        $targetRank = $stageRank[$newStatus] ?? 0;
        if ($targetRank < 9) {
            $resetUpdates[] = "is_active = 0";
            try {
                $db->prepare("UPDATE active_services SET status = 'Suspended', updated_at = NOW() WHERE order_id = ?")->execute([$orderId]);
            } catch (Throwable $e) {}
        }
        if ($targetRank < 8) {
            $resetUpdates[] = "uat_accepted_at = NULL";
            $resetUpdates[] = "uat_accepted_by = NULL";
            $resetUpdates[] = "uat_rejected_at = NULL";
        }
        if ($targetRank < 7) {
            $resetUpdates[] = "installation_date = NULL";
        }
        if ($targetRank < 5) {
            $resetUpdates[] = "countersigned_sof_at = NULL";
            $resetUpdates[] = "countersigned_sof_by = NULL";
        }
        if ($targetRank < 4) {
            $resetUpdates[] = "sof_uploaded_at = NULL";
            $resetUpdates[] = "sof_signed_file = NULL";
        }

        $resetParams[] = $orderId;
        $db->prepare("UPDATE orders SET " . implode(", ", $resetUpdates) . " WHERE id = ?")->execute($resetParams);

        $timelineNote = "⏪ Return Override: Order returned from '{$currentStatus}' to '{$newStatus}' (Routed to {$route}). Reason: {$reasonSummary}";
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?, ?, ?, ?)")
           ->execute([$orderId, $newStatus, $timelineNote, $user['id']]);

        auditLog("Return Status Override for Order #{$order['order_number']}: {$currentStatus} -> {$newStatus} (Reason: {$reasonSummary})", 'orders', $orderId);

        if (function_exists('evaluateAndSyncOrderStatus')) {
            evaluateAndSyncOrderStatus($orderId, 'status_override');
        }

        setFlash('success', "Order successfully returned to stage: {$newStatus}");
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;

    } elseif ($overrideType === 'skip') {
        $newStatus = trim($_POST['skip_to_status'] ?? '');
        $skipReason = trim($_POST['skip_reason'] ?? $overrideNote);

        if (empty($newStatus)) {
            setFlash('danger', 'Please select a valid upcoming stage to skip to.');
            header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }
        if (empty($skipReason)) {
            setFlash('danger', 'A reason is mandatory when performing a Fast-Forward / Skip Override.');
            header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }

        $targetRank = $stageRank[$newStatus] ?? 0;
        $today = date('Y-m-d');

        $autoUpdates = ["status = ?", "updated_at = NOW()"];
        $autoParams  = [$newStatus];

        // 1. Pass Feasibility if target >= 2
        if ($targetRank >= 2) {
            $autoUpdates[] = "bsa_technical_result = COALESCE(bsa_technical_result, 'Technically Feasible')";
            $autoUpdates[] = "bsa_feasi_approved = 1";
            $autoUpdates[] = "bsa_reviewed_at = COALESCE(bsa_reviewed_at, NOW())";
            $autoUpdates[] = "bsa_approved_at = COALESCE(bsa_approved_at, NOW())";
            $autoUpdates[] = "bsa_approved_by = COALESCE(bsa_approved_by, ?)";
            $autoParams[]  = $user['id'];
        }

        // 2. Pass Commercial & Management if target >= 4
        if ($targetRank >= 4) {
            $autoUpdates[] = "kam_approved_at = COALESCE(kam_approved_at, NOW())";
            $autoUpdates[] = "kam_approved_by = COALESCE(kam_approved_by, ?)";
            $autoParams[]  = $user['id'];
            $autoUpdates[] = "management_approved_at = COALESCE(management_approved_at, NOW())";
            $autoUpdates[] = "management_approved_by = COALESCE(management_approved_by, ?)";
            $autoParams[]  = $user['id'];
            $autoUpdates[] = "management_decision = COALESCE(management_decision, 'Approve as Requested')";
        }

        // 3. Pass SOF Generation & Countersigning if target >= 6
        if ($targetRank >= 6) {
            $autoUpdates[] = "sof_generated_at = COALESCE(sof_generated_at, NOW())";
            $autoUpdates[] = "sof_uploaded_at = COALESCE(sof_uploaded_at, NOW())";
            $autoUpdates[] = "countersigned_sof_at = COALESCE(countersigned_sof_at, NOW())";
            $autoUpdates[] = "countersigned_sof_by = COALESCE(countersigned_sof_by, ?)";
            $autoParams[]  = $user['id'];
        }

        // 4. Pass Installation if target >= 7
        if ($targetRank >= 7) {
            $autoUpdates[] = "installation_date = COALESCE(installation_date, ?)";
            $autoParams[]  = $today;
        }

        // 5. Pass Testing if target >= 8
        if ($targetRank >= 8) {
            $autoUpdates[] = "uat_submitted_at = COALESCE(uat_submitted_at, NOW())";
        }

        // 6. Pass UAT & Close if target >= 9
        if ($targetRank >= 9) {
            $autoUpdates[] = "uat_accepted_at = COALESCE(uat_accepted_at, NOW())";
            $autoUpdates[] = "uat_accepted_by = COALESCE(uat_accepted_by, ?)";
            $autoParams[]  = $user['id'];
            $autoUpdates[] = "closed_date = COALESCE(closed_date, ?)";
            $autoParams[]  = $today;
            $autoUpdates[] = "billing_start_date = COALESCE(billing_start_date, ?)";
            $autoParams[]  = $today;
            $autoUpdates[] = "activation_date = COALESCE(activation_date, ?)";
            $autoParams[]  = $today;
            $autoUpdates[] = "is_active = 1";

            $serviceId = !empty($order['service_id']) ? $order['service_id'] : generateServiceId($order['order_number'] ?? '');
            $circuitId = !empty($order['circuit_id']) ? $order['circuit_id'] : ('CKT-' . $order['order_number']);
            $bw = $order['bandwidth'] ?: ($order['fttx_package'] ?: ($order['aggregate_capacity'] ? ($order['aggregate_capacity'] . ' Mbps') : 'Standard 1000 Mbps'));

            $autoUpdates[] = "service_id = COALESCE(service_id, ?)";
            $autoParams[]  = $serviceId;
            $autoUpdates[] = "circuit_id = COALESCE(circuit_id, ?)";
            $autoParams[]  = $circuitId;

            // Activate in active_services
            try {
                $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                    ON DUPLICATE KEY UPDATE 
                        customer_name=VALUES(customer_name),
                        service_type=VALUES(service_type),
                        circuit_id=VALUES(circuit_id),
                        bandwidth_capacity=VALUES(bandwidth_capacity),
                        location=VALUES(location),
                        billing_start_date=VALUES(billing_start_date),
                        status='Active'")->execute([
                    $serviceId, $orderId, $order['partner_id'], $order['customer_name'], $order['service_type'],
                    $circuitId, $bw, $order['customer_location'], $order['building_name'],
                    $order['kam_id'], $today, $today, 'Active', 'Unknown'
                ]);
            } catch (Throwable $e) {}
        }

        $autoParams[] = $orderId;
        $db->prepare("UPDATE orders SET " . implode(", ", $autoUpdates) . " WHERE id = ?")->execute($autoParams);

        $timelineNote = "⏩ Fast-Forward Override: Order advanced from '{$currentStatus}' to '{$newStatus}' (Intermediate verification gates auto-approved). Reason: {$skipReason}";
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?, ?, ?, ?)")
           ->execute([$orderId, $newStatus, $timelineNote, $user['id']]);

        auditLog("Fast-Forward Status Override for Order #{$order['order_number']}: {$currentStatus} -> {$newStatus} (Reason: {$skipReason})", 'orders', $orderId);

        if (function_exists('evaluateAndSyncOrderStatus')) {
            evaluateAndSyncOrderStatus($orderId, 'status_override');
        }

        setFlash('success', "Order successfully skipped and advanced to stage: {$newStatus}");
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;

    } else {
        // Direct Override
        $newStatus = trim($_POST['new_status'] ?? '');
        $note = trim($_POST['note'] ?? 'Direct status override.');

        if ($order['status'] === 'Closed' && empty($note)) {
            setFlash('danger', 'Audit reason / note is strictly required when modifying a Closed order.');
            header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
            exit;
        }

        if (empty($note)) {
            $note = 'Direct status override.';
        }

        if (!empty($newServiceType)) {
            $db->prepare("UPDATE orders SET service_type = ? WHERE id = ?")->execute([$newServiceType, $orderId]);
        }
        if (!empty($newStatus)) {
            $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
            $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")->execute([$orderId, $newStatus, $note, $user['id']]);

            if ($newStatus === 'Closed') {
                $today = date('Y-m-d');
                $serviceId = !empty($order['service_id']) ? $order['service_id'] : generateServiceId($order['order_number'] ?? '');
                $circuitId = !empty($order['circuit_id']) ? $order['circuit_id'] : ('CKT-' . $order['order_number']);
                $bw = $order['bandwidth'] ?: ($order['fttx_package'] ?: ($order['aggregate_capacity'] ? ($order['aggregate_capacity'] . ' Mbps') : 'Standard'));
                $cDate = !empty($order['closed_date']) ? $order['closed_date'] : $today;
                $bDate = !empty($order['billing_start_date']) ? $order['billing_start_date'] : $today;

                $db->prepare("UPDATE orders SET 
                    closed_date = ?, 
                    billing_start_date = ?, 
                    activation_date = COALESCE(activation_date, ?), 
                    is_active = 1,
                    service_id = ?,
                    circuit_id = ?
                    WHERE id = ?")->execute([$cDate, $bDate, $today, $serviceId, $circuitId, $orderId]);

                $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                    ON DUPLICATE KEY UPDATE 
                        customer_name=VALUES(customer_name),
                        service_type=VALUES(service_type),
                        circuit_id=VALUES(circuit_id),
                        bandwidth_capacity=VALUES(bandwidth_capacity),
                        location=VALUES(location),
                        billing_start_date=VALUES(billing_start_date),
                        status='Active'")->execute([
                    $serviceId, $orderId, $order['partner_id'], $order['customer_name'], $newServiceType ?: $order['service_type'],
                    $circuitId, $bw, $order['customer_location'], $order['building_name'],
                    $order['kam_id'], $cDate, $bDate, 'Active', 'Unknown'
                ]);
            }
        }
        auditLog("Direct status override for order #$orderId (Reason: $note)", 'orders', $orderId);

        if (function_exists('evaluateAndSyncOrderStatus')) {
            evaluateAndSyncOrderStatus($orderId, 'status_override');
        }

        setFlash('success', 'Order status updated successfully.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }
}

// ------------------------------------------------------------------
// POST: Delete order (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=orders');
        exit;
    }

    $orderId = (int)($_POST['id'] ?? 0);
    if ($orderId > 0) {
        $stmt = $db->prepare("SELECT order_number FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $ordNum = $stmt->fetchColumn();

        $db->prepare("DELETE FROM order_timeline WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM order_documents WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM contractor_assignments WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM contractor_evidence WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM order_returns WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);

        auditLog("Deleted order #$ordNum (ID: $orderId)", 'orders', $orderId);
        setFlash('success', "Order <strong>" . e($ordNum) . "</strong> has been deleted.");
    }
    header('Location: ' . APP_URL . '/?page=orders');
    exit;
}

// ------------------------------------------------------------------
// Order Detail
// ------------------------------------------------------------------
if ($action === 'detail' || $_GET['page'] === 'order_detail') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');

    $stmt = $db->prepare("SELECT o.*, p.name as partner_name, p.address as partner_address, p.city_region as partner_city_region, p.partner_type, p.partner_category,
            u_uat.full_name as uat_uploaded_by_name,
            u_kam.full_name as kam_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        LEFT JOIN users u_uat ON o.uat_uploaded_by = u_uat.id
        LEFT JOIN users u_kam ON o.kam_id = u_kam.id
        WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    // Auto-sync Circuit ID from when order has proceeded to KAM
    $isProceededToKam = !in_array($order['status'] ?? '', ['Feasibility Review', 'Not Feasible', 'Cancelled']);
    if ($isProceededToKam && empty($order['circuit_id']) && !empty($order['order_number'])) {
        $order['circuit_id'] = 'CKT-' . $order['order_number'];
        try {
            $db->prepare("UPDATE orders SET circuit_id = ? WHERE id = ?")->execute([$order['circuit_id'], $orderId]);
        } catch (Throwable $e) {}
    }

    // Auto-sync Service ID & Circuit ID if Closed or Active Service exists
    if (empty($order['service_id'])) {
        $svcStmt = $db->prepare("SELECT service_id, circuit_id FROM active_services WHERE order_id = ? LIMIT 1");
        $svcStmt->execute([$orderId]);
        $activeSvc = $svcStmt->fetch();
        if ($activeSvc && !empty($activeSvc['service_id'])) {
            $order['service_id'] = $activeSvc['service_id'];
            $order['circuit_id'] = $activeSvc['circuit_id'] ?? $order['circuit_id'];
            try {
                $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ? WHERE id = ?")
                   ->execute([$order['service_id'], $order['circuit_id'], $orderId]);
            } catch (Throwable $e) {}
        } elseif ($order['status'] === 'Closed') {
            $genSvcId = generateServiceId($order['order_number'] ?? '');
            $genCktId = 'CKT-' . ($order['order_number'] ?? '');
            $today = $order['closed_date'] ?: date('Y-m-d');
            
            try {
                $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE order_id=order_id")->execute([
                    $genSvcId, $orderId, $order['partner_id'], $order['customer_name'], $order['service_type'],
                    $genCktId, $order['bandwidth'] ?: ($order['fttx_package'] ?? ''), $order['customer_location'] ?? '', $order['building_name'] ?? '',
                    $order['kam_id'] ?? null, $today, $today, 'Active', 'Online'
                ]);
            } catch (Throwable $e) {}

            try {
                $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ? WHERE id = ?")
                   ->execute([$genSvcId, $genCktId, $orderId]);
            } catch (Throwable $e) {}

            $order['service_id'] = $genSvcId;
            $order['circuit_id'] = $genCktId;
        }
    }

    // RBAC: Partner users receive a sanitized timeline — the `note` column is never
    // selected, so it cannot appear in the HTTP response, page source, or DevTools.
    // Authorized internal users receive the full query including event descriptions.
    if (isPartnerUser()) {
        $timelineStmt = $db->prepare("SELECT ot.id, ot.order_id, ot.status, ot.changed_at, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    } else {
        $timelineStmt = $db->prepare("SELECT ot.*, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    }
    $timelineStmt->execute([$orderId]);
    $timeline = $timelineStmt->fetchAll();



    $docs = $db->prepare("SELECT od.*, u.full_name FROM order_documents od LEFT JOIN users u ON od.uploaded_by = u.id WHERE od.order_id = ? ORDER BY od.uploaded_at DESC");
    $docs->execute([$orderId]);
    $docs = $docs->fetchAll();

    // Contractor assignment for this order
    $assignmentStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, u.full_name as assigned_by_name, u2.full_name as contractor_user_name
        FROM contractor_assignments ca
        JOIN partners p ON ca.contractor_partner_id = p.id
        LEFT JOIN users u ON ca.assigned_by = u.id
        LEFT JOIN users u2 ON ca.contractor_user_id = u2.id
        WHERE ca.order_id = ? ORDER BY ca.assigned_at DESC LIMIT 1");
    $assignmentStmt->execute([$orderId]);
    $assignment = $assignmentStmt->fetch();

    // Contractor evidence (linked directly to order or via contractor assignments)
    $evidenceStmt = $db->prepare("SELECT ce.*, u.full_name 
        FROM contractor_evidence ce 
        LEFT JOIN users u ON ce.uploaded_by = u.id 
        WHERE ce.order_id = ? 
           OR (ce.assignment_id IS NOT NULL AND ce.assignment_id > 0 AND ce.assignment_id IN (SELECT id FROM contractor_assignments WHERE order_id = ?))
        ORDER BY ce.uploaded_at DESC, ce.id DESC");
    $evidenceStmt->execute([$orderId, $orderId]);
    $evidence = $evidenceStmt->fetchAll();

    // Contractor progress updates
    $progressStmt = $db->prepare("SELECT cpu.*, u.full_name FROM contractor_progress_updates cpu LEFT JOIN users u ON cpu.updated_by = u.id WHERE cpu.order_id = ? ORDER BY cpu.created_at DESC");
    $progressStmt->execute([$orderId]);
    $progressUpdates = $progressStmt->fetchAll();

    // Contractor list for assignment (only contractor partners)
    $contractorList = $db->query("SELECT p.id, p.name FROM partners p WHERE p.kyc_type = 'Contractor' AND p.status = 'Active' ORDER BY p.name")->fetchAll();

    // Linked field survey tasks
    $orderSurveys = [];
    try {
        ensureSurveysSchema($db);
        $sStmt = $db->prepare("SELECT s.*, ct.team_name, ct.leader_name, ct.leader_phone FROM survey_tasks s JOIN contractor_teams ct ON s.contractor_team_id = ct.id WHERE s.order_id = ? ORDER BY s.id DESC");
        $sStmt->execute([$orderId]);
        $orderSurveys = $sStmt->fetchAll();
    } catch (Throwable $e) {}

    // Return audit
    $returnsStmt = $db->prepare("SELECT r.*, u.full_name FROM order_returns r LEFT JOIN users u ON r.returned_by = u.id WHERE r.order_id = ? ORDER BY r.returned_at DESC");
    $returnsStmt->execute([$orderId]);
    $orderReturns = $returnsStmt->fetchAll();

    // Price change audit trail
    $priceAudit = getOrderPriceAudit($orderId);

    $allStatuses = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];

    $pageTitle = 'Order ' . $order['order_number'];
    $extraJs   = 'orders';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/orders/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// New Order / Feasibility Request form
// ------------------------------------------------------------------
if ($_GET['page'] === 'new_order') {
    requirePermission('orders.create');
    $partners = [];
    $partnerKamName = null;
    $selectedPartnerId = (int)($_GET['partner_id'] ?? 0);
    if (!isPartnerUser()) {
        $pWhere = partnerWhere('p');
        try {
            $pStmt = $db->prepare("
                SELECT 
                    p.id, 
                    p.name, 
                    p.trading_name, 
                    p.partner_type, 
                    COALESCE(p.partner_category, IF(p.partner_type IN ('Enterprise', 'Government'), 'Enterprise Client', 'Partner')) as partner_category,
                    COALESCE(NULLIF(p.address, ''), kyc.office_address, '') as address,
                    COALESCE(NULLIF(p.city_region, ''), kyc.city_region, '') as city_region,
                    COALESCE(p.country, 'Tanzania') as country,
                    COALESCE(NULLIF(p.contact_person_name, ''), kyc.auth_signatory_name, kyc.main_contact_name, '') as contact_name,
                    COALESCE(NULLIF(p.contact_phone, ''), kyc.auth_signatory_mobile, kyc.main_contact_phone, '') as contact_phone,
                    COALESCE(NULLIF(p.contact_email, ''), kyc.auth_signatory_email, kyc.main_contact_email, '') as contact_email,
                    COALESCE(NULLIF(p.tin, ''), kyc.tin, '') as tin,
                    COALESCE(NULLIF(p.registration_number, ''), kyc.registration_number, '') as registration_number
                FROM partners p 
                LEFT JOIN (
                    SELECT partner_id, office_address, city_region, auth_signatory_name, auth_signatory_mobile, auth_signatory_email, main_contact_name, main_contact_phone, main_contact_email, tin, registration_number
                    FROM partner_kyc_applications
                    ORDER BY id DESC
                ) kyc ON kyc.partner_id = p.id
                WHERE (p.status = 'Active' OR p.status IS NULL) 
                  AND (p.kyc_type = 'Partner' OR p.kyc_type IS NULL) 
                  AND {$pWhere['condition']} 
                GROUP BY p.id
                ORDER BY p.name ASC
            ");
            $pStmt->execute($pWhere['params']);
            $partners = $pStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $pStmt = $db->prepare("SELECT p.id, p.name, p.partner_type, 'Partner' as partner_category, p.address, p.city_region, '' as contact_name, '' as contact_phone, '' as contact_email FROM partners p WHERE (p.status = 'Active' OR p.status IS NULL) AND {$pWhere['condition']} ORDER BY p.name");
            $pStmt->execute($pWhere['params']);
            $partners = $pStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        try {
            $pKamStmt = $db->prepare("SELECT assigned_kam_name FROM partners WHERE id = ?");
            $pKamStmt->execute([$user['partner_id'] ?? 0]);
            $partnerKamName = $pKamStmt->fetchColumn() ?: null;
        } catch (Throwable $e) {
            try { $db->exec("ALTER TABLE partners ADD COLUMN assigned_kam_name VARCHAR(100) NULL"); } catch (Throwable $t) {}
            $partnerKamName = null;
        }
    }

    $isCurrentPartnerEnterprise = false;
    if (isPartnerUser()) {
        try {
            $pCatStmt = $db->prepare("SELECT partner_category, partner_type FROM partners WHERE id = ?");
            $pCatStmt->execute([$user['partner_id'] ?? 0]);
            $pCatRow = $pCatStmt->fetch();
            if ($pCatRow && (($pCatRow['partner_category'] ?? '') === 'Enterprise Client' || ($pCatRow['partner_type'] ?? '') === 'Enterprise' || ($pCatRow['partner_type'] ?? '') === 'Government')) {
                $isCurrentPartnerEnterprise = true;
            }
        } catch (Throwable $e) {}
    }

    $kamList = $db->query("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = 'New Feasibility Request';
    $extraJs   = 'orders';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/orders/new_order.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Order Tracking List
// ------------------------------------------------------------------
$pw = partnerWhere('o');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterStatus  = $_GET['status'] ?? '';
$filterService = $_GET['service_type'] ?? '';
$filterSearch  = $_GET['q'] ?? '';
$filterSla     = $_GET['sla'] ?? '';
$filterPreset  = $_GET['preset'] ?? '';
$filterStart   = trim($_GET['start_date'] ?? '');
$filterEnd     = trim($_GET['end_date'] ?? '');
$filterActive  = (isAdmin() || isManagement()) ? (($_GET['active_filter'] ?? '') !== '' ? $_GET['active_filter'] : '') : '';

$currentPageKey = $_GET['page'] ?? 'orders';
$isClosedPage   = ($currentPageKey === 'closed_orders');

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

if ($filterStart && $filterEnd) {
    if ($isClosedPage) {
        $where .= " AND (COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) BETWEEN ? AND ?)";
        $params[] = $filterStart;
        $params[] = $filterEnd;
    } else {
        $where .= " AND o.created_at BETWEEN ? AND ?";
        $params[] = $filterStart . ' 00:00:00';
        $params[] = $filterEnd . ' 23:59:59';
    }
} elseif ($filterStart) {
    if ($isClosedPage) {
        $where .= " AND (COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) >= ?)";
        $params[] = $filterStart;
    } else {
        $where .= " AND o.created_at >= ?";
        $params[] = $filterStart . ' 00:00:00';
    }
} elseif ($filterEnd) {
    if ($isClosedPage) {
        $where .= " AND (COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) <= ?)";
        $params[] = $filterEnd;
    } else {
        $where .= " AND o.created_at <= ?";
        $params[] = $filterEnd . ' 23:59:59';
    }
}

$ongoingStatuses = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT'];
$closedStatuses  = ['Closed','Not Feasible','Cancelled'];

if (!$isClosedPage && in_array($filterStatus, $closedStatuses, true)) {
    $filterStatus = '';
} elseif ($isClosedPage && in_array($filterStatus, $ongoingStatuses, true)) {
    $filterStatus = '';
}
if (!$isClosedPage) {
    $filterActive = '';
}

if ($filterService) { $where .= " AND o.service_type = ?"; $params[] = $filterService; }
if ($filterSla === 'paused') {
    $where .= " AND o.sla_paused = 1";
} elseif ($filterSla === 'breached') {
    $candStmt = $db->prepare("SELECT o.* FROM orders o JOIN partners p ON o.partner_id = p.id $where");
    $candStmt->execute($params);
    $cands = $candStmt->fetchAll(PDO::FETCH_ASSOC);
    $breachedIds = [];
    if (!empty($cands)) {
        $analytics = computeComprehensiveSlaAnalytics($cands, $db);
        foreach ($analytics['order_evaluations'] as $ev) {
            if ($ev['order_sla_status'] === 'Breached') {
                $breachedIds[] = $ev['order_id'];
            }
        }
    }
    if (!empty($breachedIds)) {
        $inList = implode(',', $breachedIds);
        $where .= " AND o.id IN ($inList)";
    } else {
        $where .= " AND 1=0";
    }
}
if ($filterSearch)  { $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.circuit_id LIKE ?)"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; }

// Filter by partner_id if provided (e.g. from dashboard or admin)
$filterPartner = (int)($_GET['partner_id'] ?? 0);
if ($filterPartner > 0 && !isPartnerUser() && !isContractorUser()) {
    $where .= " AND o.partner_id = ?";
    $params[] = $filterPartner;
}

// Capture base conditions before applying isClosedPage, specific status, or active_filter
$baseWhere = $where;
$baseParams = $params;

// Calculate KPI counts
$ongoingCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND o.status != 'Closed' AND o.status NOT IN ('Not Feasible', 'Cancelled')");
$ongoingCountStmt->execute($baseParams);
$ongoingCount = (int)$ongoingCountStmt->fetchColumn();

$closedCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND (o.status = 'Closed' OR o.status IN ('Not Feasible', 'Cancelled'))");
$closedCountStmt->execute($baseParams);
$closedCount = (int)$closedCountStmt->fetchColumn();

$totalCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere");
$totalCountStmt->execute($baseParams);
$totalOrdersCount = (int)$totalCountStmt->fetchColumn();

$activeLiveStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND o.status = 'Closed' AND o.billing_start_date IS NOT NULL AND (o.is_active = 1 OR o.is_active IS NULL)");
$activeLiveStmt->execute($baseParams);
$activeLiveCount = (int)$activeLiveStmt->fetchColumn();

// Specific pipeline stage counts for ongoing orders KPI
$feasibilityCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND o.status = 'Feasibility Review'");
$feasibilityCountStmt->execute($baseParams);
$feasibilityCount = (int)$feasibilityCountStmt->fetchColumn();

$approvalCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND o.status IN ('Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review')");
$approvalCountStmt->execute($baseParams);
$approvalCount = (int)$approvalCountStmt->fetchColumn();

$installCountStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $baseWhere AND o.status IN ('Installation', 'Testing', 'UAT')");
$installCountStmt->execute($baseParams);
$installCount = (int)$installCountStmt->fetchColumn();

// Apply page condition
if ($isClosedPage) {
    $where .= " AND (o.status = 'Closed' OR o.status IN ('Not Feasible', 'Cancelled'))";
    // Filter by is_active (Admin/Management only) on Closed Orders page
    if ($filterActive === '1') {
        $where .= " AND o.billing_start_date IS NOT NULL AND (o.is_active = 1 OR o.is_active IS NULL)";
    } elseif ($filterActive === '0') {
        $where .= " AND (o.billing_start_date IS NULL OR o.is_active = 0)";
    }
} else {
    $where .= " AND o.status != 'Closed' AND o.status NOT IN ('Not Feasible', 'Cancelled')";
}

// Apply specific status filter if selected
if ($filterStatus) {
    $where .= " AND o.status = ?";
    $params[] = $filterStatus;
}

// ------------------------------------------------------------------
// CSV Export Action
// ------------------------------------------------------------------
if ($action === 'export' || (!empty($_GET['export']) && $_GET['export'] === 'csv')) {
    $exportOrderClause = $isClosedPage 
        ? "COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) DESC, o.id DESC" 
        : "o.created_at DESC, o.id DESC";
    $exportStmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id $where ORDER BY $exportOrderClause");
    $exportStmt->execute($params);
    $exportOrders = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    $filenamePrefix = $isClosedPage ? 'closed_orders_' : 'ongoing_orders_';
    header('Content-Disposition: attachment; filename="' . $filenamePrefix . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fputs($out, "\xEF\xBB\xBF");

    $exportHeaders = ['Order Number', 'Customer Name'];
    if (!isPartnerUser()) {
        $exportHeaders[] = 'Partner Name';
    }
    $exportHeaders[] = 'Service Type';
    $exportHeaders[] = 'Order Status';
    $exportHeaders[] = 'Customer Location';
    $exportHeaders[] = 'Circuit ID';
    $exportHeaders[] = 'Installation Date';
    $exportHeaders[] = 'Billing Start Date';
    $exportHeaders[] = 'Order Status (Active/Inactive)';
    if (!isPartnerUser()) {
        $exportHeaders[] = 'Total NRC (TZS)';
        $exportHeaders[] = 'Total MRC (TZS)';
        $exportHeaders[] = 'Total Revenue (TZS)';
    }
    $exportHeaders[] = 'Created Date';
    $exportHeaders[] = 'Updated Date';

    fputcsv($out, $exportHeaders);

    foreach ($exportOrders as $o) {
        $isActiveStr = isOrderActive($o) ? 'Active' : 'Inactive';
        $row = [
            $o['order_number'] ?? '',
            $o['customer_name'] ?? '',
        ];
        if (!isPartnerUser()) {
            $row[] = $o['partner_name'] ?? '';
        }
        $row[] = $o['service_type'] ?? '';
        $row[] = $o['status'] ?? '';
        $row[] = $o['customer_location'] ?? '';
        $row[] = $o['circuit_id'] ?? '';
        $row[] = !empty($o['installation_date']) ? date('Y-m-d', strtotime($o['installation_date'])) : (!empty($o['closed_date']) ? date('Y-m-d', strtotime($o['closed_date'])) : '');
        $row[] = !empty($o['billing_start_date']) ? date('Y-m-d', strtotime($o['billing_start_date'])) : '';
        $row[] = $isActiveStr;
        if (!isPartnerUser()) {
            $row[] = $o['total_nrc'] ?? 0;
            $row[] = $o['total_mrc'] ?? 0;
            $row[] = $o['total_revenue'] ?? 0;
        }
        $row[] = !empty($o['created_at']) ? date('Y-m-d H:i:s', strtotime($o['created_at'])) : '';
        $row[] = !empty($o['updated_at']) ? date('Y-m-d H:i:s', strtotime($o['updated_at'])) : '';

        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$sortBy  = $_GET['sort_by'] ?? '';
$sortDir = strtoupper($_GET['sort_dir'] ?? 'DESC');
if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
    $sortDir = 'DESC';
}

if ($isClosedPage) {
    $defaultOrder = "COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) DESC, o.id DESC";
} else {
    $defaultOrder = "o.created_at DESC, o.id DESC";
}

$orderByClause = match($sortBy) {
    'partner'           => "p.name $sortDir, o.created_at DESC",
    'customer'          => "o.customer_name $sortDir, o.created_at DESC",
    'created_at'        => "o.created_at $sortDir",
    'installation_date' => "COALESCE(o.installation_date, o.closed_date, o.billing_start_date, DATE(o.created_at)) $sortDir, o.id $sortDir",
    'billing_date'      => "o.billing_start_date $sortDir, o.created_at DESC",
    'order_number'      => "o.order_number $sortDir",
    'service_type'      => "o.service_type $sortDir, o.created_at DESC",
    'status'            => "o.status $sortDir, o.created_at DESC",
    'mrc'               => "o.total_mrc_incl_vat $sortDir, o.created_at DESC",
    'nrc'               => "o.total_nrc_incl_vat $sortDir, o.created_at DESC",
    default             => $defaultOrder
};

$totalStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 20;
$page  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id $where ORDER BY $orderByClause LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$allStatuses  = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];
$serviceTypes = getActiveServiceTypes($db);
$partnersList = [];
if (!isPartnerUser() && !isContractorUser()) {
    try {
        $partnersList = $db->query("SELECT id, name FROM partners WHERE (kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller')) AND status = 'Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $partnersList = $db->query("SELECT id, name FROM partners WHERE status = 'Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
}
// Export to view: pass filterActive and filterPartner so the view can mark the correct <option> selected
$filterActive  = $filterActive ?? '';
$filterPartner = $filterPartner ?? 0;

$pageTitle = $isClosedPage ? 'Closed Orders' : 'Order Tracking';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/orders/tracking.php';
include APP_DIR . '/views/layout/footer.php';
