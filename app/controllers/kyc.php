<?php
// ============================================================
// Neilos Partner Portal — Partner & Contractor KYC Controller
// Complete One-Page KYC Application & Approval Workflow
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
// For POST requests the action comes from the hidden _action field in the form body;
// for GET requests it comes from the URL query string.
$action = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? (trim($_POST['_action'] ?? $_GET['action'] ?? 'index'))
    : ($_GET['action'] ?? 'index');

if (!function_exists('ensureKycSchema')) {
function ensureKycSchema(PDO $db): void {
    static $done = false;
    if ($done) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS kyc_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            kyc_id INT UNSIGNED NOT NULL,
            action_by INT UNSIGNED NULL,
            action_role VARCHAR(50) NOT NULL,
            from_status VARCHAR(50) NULL,
            to_status VARCHAR(50) NOT NULL,
            action_title VARCHAR(200) NOT NULL,
            details TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_kyc (kyc_id)
        ) ENGINE=InnoDB;");

        // Ensure status column supports Submitted for Approval
        try {
            $db->exec("ALTER TABLE partner_kyc_applications MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Draft'");
        } catch (Exception $e) {}

        $cols = [
            'office_address'              => "TEXT NULL",
            'address'                     => "TEXT NULL",
            'auth_signatory_title'        => "VARCHAR(150) NULL",
            'signatory2_title'            => "VARCHAR(150) NULL",
            'signatory2_name'             => "VARCHAR(200) NULL",
            'signatory2_email'            => "VARCHAR(200) NULL",
            'signatory2_phone'            => "VARCHAR(50) NULL",
            'tech_supervisor_title'       => "VARCHAR(150) NULL",
            'billing_contact_title'       => "VARCHAR(150) NULL",
            'billing_contact_name'        => "VARCHAR(200) NULL",
            'billing_contact_email'       => "VARCHAR(200) NULL",
            'billing_contact_phone'       => "VARCHAR(50) NULL",
            'contractor_agreement'        => "VARCHAR(500) NULL",
            'signed_sla_file'             => "VARCHAR(500) NULL",
            'safety_esg_file'             => "VARCHAR(500) NULL",
            'confidentiality_nda_file'    => "VARCHAR(500) NULL",
            'service_regions_file'        => "VARCHAR(500) NULL",
            'hse_certificate'             => "VARCHAR(500) NULL",
            'trca_certificate'            => "VARCHAR(500) NULL",
            'sla_accepted'                => "TINYINT(1) DEFAULT 0",
            'sla_accepted_at'             => "DATETIME NULL",
            'safety_esg_accepted'         => "TINYINT(1) DEFAULT 0",
            'safety_esg_accepted_at'      => "DATETIME NULL",
            'confidentiality_accepted'    => "TINYINT(1) DEFAULT 0",
            'confidentiality_accepted_at' => "DATETIME NULL",
            'accepted_by'                 => "INT UNSIGNED NULL",
            'custom_fields'               => "JSON NULL",
            // Partner-specific mandatory documents
            'partner_tin_certificate'     => "VARCHAR(500) NULL",
            'partner_vrn_certificate'     => "VARCHAR(500) NULL",
            'partner_incorporation_cert'  => "VARCHAR(500) NULL",
            'partner_tcra_certificate'    => "VARCHAR(500) NULL",
            'partner_tax_clearance'       => "VARCHAR(500) NULL",
            'partner_business_licence'    => "VARCHAR(500) NULL"
        ];

        foreach ($cols as $col => $def) {
            try {
                $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN $col $def");
            } catch (Exception $e) {}
        }
        $done = true;
    } catch (Exception $e) {}
}
}

// Ensure schema is updated
ensureKycSchema($db);

if (!function_exists('recordKycHistory')) {
function recordKycHistory(PDO $db, int $kycId, string $actionTitle, ?string $fromStatus, string $toStatus, ?string $details = null): void {
    $user = currentUser();
    $userId = $user ? (int)$user['id'] : null;
    $userRole = $user ? ($user['role'] ?: 'User') : 'System';

    try {
        $db->prepare("INSERT INTO kyc_history (kyc_id, action_by, action_role, from_status, to_status, action_title, details) VALUES (?,?,?,?,?,?,?)")
           ->execute([$kycId, $userId, $userRole, $fromStatus, $toStatus, $actionTitle, $details]);
    } catch (Exception $e) {}
}
}

// ------------------------------------------------------------------
// AJAX API: Get Entity Data for Auto-Fill
// ------------------------------------------------------------------
if ($action === 'api_entity_data') {
    header('Content-Type: application/json; charset=utf-8');
    $partnerId = (int)($_GET['partner_id'] ?? 0);
    if (!$partnerId) {
        echo json_encode(['success' => false, 'message' => 'Partner ID required']);
        exit;
    }

    $stmt = $db->prepare("SELECT p.*, pka.* 
        FROM partners p 
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id 
        WHERE p.id = ? 
        ORDER BY pka.updated_at DESC LIMIT 1");
    $stmt->execute([$partnerId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Entity not found']);
        exit;
    }

    if (empty($data['auth_signatory_name'])) {
        $uStmt = $db->prepare("SELECT full_name, email, mobile FROM users WHERE partner_id = ? AND is_active = 1 LIMIT 1");
        $uStmt->execute([$partnerId]);
        $u = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $data['auth_signatory_name']  = $u['full_name'];
            $data['auth_signatory_email'] = $u['email'];
            $data['auth_signatory_mobile']= $u['mobile'];
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Specific Document
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_doc') {
    verifyCsrf();
    $kycId  = (int)($_POST['kyc_id'] ?? 0);
    $docKey = preg_replace('/[^a-z0-9_]/', '', $_POST['doc_key'] ?? '');

    if (isPartnerUser() || isContractorUser()) {
        $userPartnerId = (int)($user['partner_id'] ?? 0);
        $checkStmt = $db->prepare("SELECT partner_id, status FROM partner_kyc_applications WHERE id = ?");
        $checkStmt->execute([$kycId]);
        $kRow = $checkStmt->fetch();
        if (!$kRow || (int)$kRow['partner_id'] !== $userPartnerId) {
            setFlash('danger', 'Permission denied.');
            header('Location: ' . APP_URL . '/?page=kyc');
            exit;
        }
    }

    $validCols = [
        // Contractor docs
        'business_license', 'contractor_agreement', 'signed_sla_file',
        'safety_esg_file', 'confidentiality_nda_file', 'service_regions_file',
        'hse_certificate', 'trca_certificate',
        // Partner docs
        'partner_tin_certificate', 'partner_vrn_certificate',
        'partner_incorporation_cert', 'partner_tcra_certificate',
        'partner_tax_clearance', 'partner_business_licence',
    ];

    if ($kycId > 0 && in_array($docKey, $validCols)) {
        $checkStmt = $db->prepare("SELECT status FROM partner_kyc_applications WHERE id = ?");
        $checkStmt->execute([$kycId]);
        $curApp = $checkStmt->fetch();
        if ($curApp && $curApp['status'] === 'Approved') {
            setFlash('danger', 'This KYC application is already approved and locked for modifications.');
            header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
            exit;
        }

        $db->prepare("UPDATE partner_kyc_applications SET {$docKey} = NULL WHERE id = ?")->execute([$kycId]);
        recordKycHistory($db, $kycId, "Deleted Document: $docKey", null, 'Draft');
        setFlash('success', "Document deleted successfully.");
    }

    header('Location: ' . APP_URL . '/?page=kyc&action=edit&id=' . $kycId);
    exit;
}

// ------------------------------------------------------------------
// POST: Save Draft / Submit / Resubmit
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['save_draft', 'submit', 'resubmit'])) {
    verifyCsrf();
    requirePermission('kyc.create');

    $kycId     = (int)($_POST['kyc_id'] ?? 0);
    $partnerId = (int)($_POST['partner_id'] ?? 0);
    $kycType   = $_POST['kyc_type'] ?? 'Partner';

    if (isPartnerUser() || isContractorUser()) {
        $userPartnerId = (int)($user['partner_id'] ?? 0);
        if (!$userPartnerId) {
            setFlash('danger', 'Your account is not linked to any partner entity.');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
        $partnerId = $userPartnerId;
        if ($kycId > 0) {
            $checkStmt = $db->prepare("SELECT partner_id FROM partner_kyc_applications WHERE id = ?");
            $checkStmt->execute([$kycId]);
            $ownerPartnerId = (int)$checkStmt->fetchColumn();
            if ($ownerPartnerId !== $userPartnerId) {
                setFlash('danger', 'Unauthorized access.');
                header('Location: ' . APP_URL . '/?page=kyc');
                exit;
            }
        }
    }

    if (!$partnerId) {
        setFlash('danger', 'Please select a valid Partner or Contractor.');
        header('Location: ' . APP_URL . '/?page=kyc&action=new');
        exit;
    }

    if ($kycId > 0) {
        $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE id = ?");
        $stmt->execute([$kycId]);
        $app = $stmt->fetch();
    } else {
        $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE partner_id = ?");
        $stmt->execute([$partnerId]);
        $app = $stmt->fetch();
    }

    if ($app && $app['status'] === 'Approved') {
        setFlash('danger', 'This KYC application is already approved and locked for modifications.');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $app['id']);
        exit;
    }

    if (!$app) {
        $db->prepare("INSERT INTO partner_kyc_applications (partner_id, kyc_type, status, registered_name, registration_number, tin, city_region) VALUES (?, ?, 'Draft', '', '', '', 'Dar es Salaam')")
           ->execute([$partnerId, $kycType]);
        $kycId = (int)$db->lastInsertId();
        $oldStatus = 'Draft';
        recordKycHistory($db, $kycId, 'KYC Created by ' . ($user['role'] ?: 'Internal User'), null, 'Draft');
    } else {
        $kycId = (int)$app['id'];
        $oldStatus = $app['status'];
    }

    // Process Custom Fields
    $customFields = [];
    if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
        foreach ($_POST['custom_fields'] as $cf) {
            if (!empty($cf['name'])) {
                $customFields[] = [
                    'name'     => trim($cf['name']),
                    'type'     => $cf['type'] ?? 'text',
                    'required' => !empty($cf['required']),
                    'value'    => $cf['value'] ?? ''
                ];
            }
        }
    }

    $serviceRegions = isset($_POST['service_regions']) ? implode(', ', (array)$_POST['service_regions']) : ($_POST['service_regions_text'] ?? null);

    $fields = [
        'kyc_type'                  => $kycType,
        'registered_name'           => $_POST['registered_name'] ?? '',
        'trading_name'              => $_POST['trading_name'] ?? null,
        'registration_number'       => $_POST['registration_number'] ?? '',
        'tin'                       => $_POST['tin'] ?? '',
        'vat_vrn'                   => $_POST['vat_vrn'] ?? null,
        'office_address'            => $_POST['office_address'] ?? '',
        'city_region'               => $_POST['city_region'] ?? 'Dar es Salaam',
        'country'                   => $_POST['country'] ?? 'Tanzania',
        
        // Contacts
        'auth_signatory_title'      => $_POST['auth_signatory_title'] ?? null,
        'auth_signatory_name'       => $_POST['auth_signatory_name'] ?? null,
        'auth_signatory_email'      => $_POST['auth_signatory_email'] ?? null,
        'auth_signatory_mobile'     => $_POST['auth_signatory_mobile'] ?? null,

        'signatory2_title'          => $_POST['signatory2_title'] ?? null,
        'signatory2_name'           => $_POST['signatory2_name'] ?? null,
        'signatory2_email'          => $_POST['signatory2_email'] ?? null,
        'signatory2_phone'          => $_POST['signatory2_phone'] ?? null,

        'tech_supervisor_title'     => $_POST['tech_supervisor_title'] ?? null,
        'tech_supervisor_name'      => $_POST['tech_supervisor_name'] ?? null,
        'tech_supervisor_email'     => $_POST['tech_supervisor_email'] ?? null,
        'tech_supervisor_phone'     => $_POST['tech_supervisor_phone'] ?? null,

        'billing_contact_title'     => $_POST['billing_contact_title'] ?? null,
        'billing_contact_name'      => $_POST['billing_contact_name'] ?? null,
        'billing_contact_email'     => $_POST['billing_contact_email'] ?? null,
        'billing_contact_phone'     => $_POST['billing_contact_phone'] ?? null,

        // Bank Details (Optional)
        'bank_name'                 => $_POST['bank_name'] ?? null,
        'bank_branch'               => $_POST['bank_branch'] ?? null,
        'bank_account_name'         => $_POST['bank_account_name'] ?? null,
        'bank_account_number'       => $_POST['bank_account_number'] ?? null,
        'bank_payment_terms'        => $_POST['bank_payment_terms'] ?? '30 Days',

        // Capabilities
        'cap_ftth_install'          => !empty($_POST['cap_ftth_install']) ? 1 : 0,
        'cap_sme_install'           => !empty($_POST['cap_sme_install']) ? 1 : 0,
        'cap_enterprise_install'    => !empty($_POST['cap_enterprise_install']) ? 1 : 0,
        'cap_site_survey'           => !empty($_POST['cap_site_survey']) ? 1 : 0,
        'cap_maintenance'           => !empty($_POST['cap_maintenance']) ? 1 : 0,
        'cap_remote_support'        => !empty($_POST['cap_remote_support']) ? 1 : 0,

        'service_regions'           => $serviceRegions,
        'custom_fields'             => json_encode($customFields),

        // Compliance Acceptances
        'sla_accepted'              => !empty($_POST['sla_accepted']) ? 1 : 0,
        'sla_accepted_at'           => !empty($_POST['sla_accepted']) ? date('Y-m-d H:i:s') : null,
        'safety_esg_accepted'       => !empty($_POST['safety_esg_accepted']) ? 1 : 0,
        'safety_esg_accepted_at'    => !empty($_POST['safety_esg_accepted']) ? date('Y-m-d H:i:s') : null,
        'confidentiality_accepted'  => !empty($_POST['confidentiality_accepted']) ? 1 : 0,
        'confidentiality_accepted_at'=> !empty($_POST['confidentiality_accepted']) ? date('Y-m-d H:i:s') : null,
    ];

    // Ensure title columns exist in partner_kyc_applications
    try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN auth_signatory_title VARCHAR(150) NULL AFTER auth_signatory_mobile"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN signatory2_title VARCHAR(150) NULL AFTER signatory2_phone"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN tech_supervisor_title VARCHAR(150) NULL AFTER tech_supervisor_phone"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE partner_kyc_applications ADD COLUMN billing_contact_title VARCHAR(150) NULL AFTER billing_contact_phone"); } catch (Throwable $e) {}

    // Dynamically query columns of partner_kyc_applications to prevent SQL 1054 Unknown Column errors
    $existingColsStmt = $db->query("SHOW COLUMNS FROM partner_kyc_applications");
    $existingCols = $existingColsStmt->fetchAll(PDO::FETCH_COLUMN);

    $sets = [];
    $params = [];
    foreach ($fields as $col => $val) {
        if (in_array($col, $existingCols)) {
            $sets[] = "$col = ?";
            $params[] = $val;
        }
    }
    $params[] = $kycId;

    if (!empty($sets)) {
        $db->prepare("UPDATE partner_kyc_applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    }

    $db->prepare("UPDATE partners SET kyc_type = ?, name = IF(name='', ?, name), tin = IF(tin='', ?, tin), registration_number = IF(registration_number='', ?, registration_number) WHERE id = ?")
       ->execute([$kycType, $fields['registered_name'], $fields['tin'], $fields['registration_number'], $partnerId]);

    // Handle all file uploads — Contractor docs + Partner-specific docs
    $fileMap = [
        // Contractor documents
        'business_license'           => 'Business License',
        'contractor_agreement'       => 'Contractor Agreement',
        'signed_sla_file'            => 'Signed SLA Acceptance Document',
        'safety_esg_file'            => 'Safety / ESG Policy Document',
        'confidentiality_nda_file'   => 'Confidentiality & Non-Disclosure Agreement (NDA)',
        'service_regions_file'       => 'Service Regions Supporting Document',
        'hse_certificate'            => 'HSE Certificate',
        'trca_certificate'           => 'TCRA / Regulatory License',

        // Partner-specific documents
        'partner_tin_certificate'    => 'TIN Certificate',
        'partner_vrn_certificate'    => 'VRN Certificate',
        'partner_incorporation_cert' => 'Certificate of Incorporation',
        'partner_tcra_certificate'   => 'TCRA Certificate',
        'partner_tax_clearance'      => 'Tax Clearance Certificate',
        'partner_business_licence'   => 'Business Licence Certificate',
    ];

    foreach ($fileMap as $inputKey => $docTitle) {
        if (!empty($_FILES[$inputKey]['name']) && $_FILES[$inputKey]['error'] === UPLOAD_ERR_OK) {
            try {
                $upload = uploadFile($_FILES[$inputKey], 'kyc/' . $kycId);
                $db->prepare("UPDATE partner_kyc_applications SET {$inputKey} = ? WHERE id = ?")
                   ->execute([$upload['path'], $kycId]);
            } catch (Exception $e) {}
        }
    }

    // Determine target status & validate mandatory requirements
    if ($action === 'submit' || $action === 'resubmit') {
        $missing = [];
        if (empty($_POST['registered_name']))        $missing[] = 'Partner/Contractor Name';
        if (empty($_POST['office_address']))        $missing[] = 'Physical Address';
        
        $curKyc = $db->query("SELECT * FROM partner_kyc_applications WHERE id = $kycId")->fetch();

        // Contacts — required for both Partner and Contractor
        if (empty($_POST['auth_signatory_name']))   $missing[] = 'Signatory 1 Name';
        if (empty($_POST['auth_signatory_email']))  $missing[] = 'Signatory 1 Email';
        if (empty($_POST['auth_signatory_mobile'])) $missing[] = 'Signatory 1 Phone';
        if (empty($_POST['signatory2_name']))       $missing[] = 'Signatory 2 Name';
        if (empty($_POST['signatory2_email']))      $missing[] = 'Signatory 2 Email';
        if (empty($_POST['signatory2_phone']))      $missing[] = 'Signatory 2 Phone';
        if (empty($_POST['tech_supervisor_name']))  $missing[] = 'Technical Contact Name';
        if (empty($_POST['tech_supervisor_email'])) $missing[] = 'Technical Contact Email';
        if (empty($_POST['tech_supervisor_phone'])) $missing[] = 'Technical Contact Phone';
        if (empty($_POST['billing_contact_name']))  $missing[] = 'Billing Contact Name';
        if (empty($_POST['billing_contact_email'])) $missing[] = 'Billing Contact Email';
        if (empty($_POST['billing_contact_phone'])) $missing[] = 'Billing Contact Phone';

        // Bank Details are optional as requested

        if ($kycType === 'Partner') {
            // ── PARTNER: 3 mandatory document uploads (TIN, VRN, and TCRA Certificate are optional) ──
            if (empty($curKyc['partner_incorporation_cert']) && empty($_FILES['partner_incorporation_cert']['name']))
                $missing[] = '3. Certificate of Incorporation';
            if (empty($curKyc['partner_tax_clearance']) && empty($_FILES['partner_tax_clearance']['name']))
                $missing[] = '5. Tax Clearance Certificate';
            if (empty($curKyc['partner_business_licence']) && empty($_FILES['partner_business_licence']['name']))
                $missing[] = '6. Business Licence Certificate';
        } else {
            // ── CONTRACTOR: original 7 compliance docs + extras ───────────
            if (empty($curKyc['business_license']) && empty($_FILES['business_license']['name']))
                $missing[] = 'Business License Document';
            if (empty($curKyc['contractor_agreement']) && empty($_FILES['contractor_agreement']['name']))
                $missing[] = '1. Contractor Agreement Document';
            if (empty($curKyc['signed_sla_file']) && empty($_FILES['signed_sla_file']['name']))
                $missing[] = '2. Signed SLA Acceptance Document';
            if (empty($curKyc['safety_esg_file']) && empty($_FILES['safety_esg_file']['name']))
                $missing[] = '3. Safety / ESG Policy Acceptance Document';
            if (empty($curKyc['confidentiality_nda_file']) && empty($_FILES['confidentiality_nda_file']['name']))
                $missing[] = '4. Confidentiality Clause / NDA Acceptance Document';
            if (empty($curKyc['service_regions_file']) && empty($_FILES['service_regions_file']['name']))
                $missing[] = '5. Service Regions Supporting Document';
            if (empty($curKyc['hse_certificate']) && empty($_FILES['hse_certificate']['name']))
                $missing[] = '6. HSE Certificate Document';
            if (empty($curKyc['trca_certificate']) && empty($_FILES['trca_certificate']['name']))
                $missing[] = '7. TRCA Installation License Document';
            if (empty($_POST['sla_accepted']))             $missing[] = 'SLA Policy Confirmation';
            if (empty($_POST['safety_esg_accepted']))      $missing[] = 'Safety / ESG Policy Confirmation';
            if (empty($_POST['confidentiality_accepted'])) $missing[] = 'Confidentiality Clause Confirmation';
            if (empty($_POST['service_regions']))           $missing[] = 'Service Regions (Select at least one region)';
            $hasCap = (!empty($_POST['cap_ftth_install']) || !empty($_POST['cap_sme_install']) || !empty($_POST['cap_enterprise_install']) || !empty($_POST['cap_site_survey']) || !empty($_POST['cap_maintenance']) || !empty($_POST['cap_remote_support']));
            if (!$hasCap) $missing[] = 'Capabilities (Select at least one capability)';
        }

        if (!empty($missing)) {
            setFlash('danger', '<strong>Validation Error:</strong> Please complete all mandatory fields and upload all required documents before submitting:<ul style="margin:6px 0 0 20px"><li>' . implode('</li><li>', $missing) . '</li></ul>');
            header('Location: ' . APP_URL . '/?page=kyc&action=edit&id=' . $kycId);
            exit;
        }

        $newStatus = 'Submitted for Approval';
        $db->prepare("UPDATE partner_kyc_applications SET status = 'Submitted for Approval', submitted_at = NOW() WHERE id = ?")->execute([$kycId]);

        $actTitle = ($action === 'resubmit') ? 'KYC Resubmitted for Approval' : 'KYC Submitted for Approval';
        recordKycHistory($db, $kycId, $actTitle, $oldStatus, $newStatus);

        queueKycNotification($kycId, $action === 'resubmit' ? 'Resubmit' : 'Submit');
        publishSystemEvent('kyc_status_updated', null, ['kyc_id' => $kycId, 'status' => 'Submitted for Approval']);

        setFlash('success', 'KYC application submitted for approval successfully.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    } else {
        $db->prepare("UPDATE partner_kyc_applications SET status = 'Draft' WHERE id = ?")->execute([$kycId]);
        recordKycHistory($db, $kycId, 'KYC Saved as Draft', $oldStatus, 'Draft');
        setFlash('success', 'KYC application draft saved.');
        header('Location: ' . APP_URL . '/?page=kyc&action=edit&id=' . $kycId);
        exit;
    }
}

// ------------------------------------------------------------------
// ------------------------------------------------------------------
// POST: Finance / Management Approve KYC
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve') {
    verifyCsrf();
    requirePermission('kyc.approve');
    $isAjax  = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    $kycId   = (int)($_POST['kyc_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? $_POST['review_notes'] ?? $_POST['notes'] ?? '');

    $stmt = $db->prepare("SELECT pka.*, p.name as partner_name FROM partner_kyc_applications pka LEFT JOIN partners p ON pka.partner_id = p.id WHERE pka.id = ?");
    $stmt->execute([$kycId]);
    $app = $stmt->fetch();

    if (!$app) {
        if ($isAjax) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'KYC application not found.']);
            exit;
        }
        setFlash('danger', 'KYC application not found.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    if ($app['status'] === 'Approved') {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'This KYC application is already approved.']);
            exit;
        }
        setFlash('info', 'This KYC application is already approved.');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
        exit;
    }

    if (!in_array($app['status'], ['Submitted for Approval', 'Pending Approval', 'Submitted'])) {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'This KYC application cannot be approved in its current status (' . $app['status'] . ').']);
            exit;
        }
        setFlash('danger', 'This KYC application cannot be approved in its current status (' . $app['status'] . ').');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
        exit;
    }

    $db->prepare("UPDATE partner_kyc_applications SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
       ->execute([$user['id'], $kycId]);

    // Activate the partner record if linked
    if (!empty($app['partner_id'])) {
        try {
            $db->prepare("UPDATE partners SET status = 'Active' WHERE id = ?")->execute([$app['partner_id']]);
        } catch (Throwable $t) {}
    }

    $actorRole = isFinance() ? 'Finance' : ($user['role'] ?: 'Management');
    $histDetails = "Approved by {$user['full_name']} ({$actorRole})" . ($remarks !== '' ? " · Remarks: {$remarks}" : "");
    recordKycHistory($db, $kycId, "KYC Approved by {$actorRole}", $app['status'], 'Approved', $histDetails);

    queueKycNotification($kycId, 'Approve');
    publishSystemEvent('kyc_status_updated', null, ['kyc_id' => $kycId, 'status' => 'Approved']);

    auditLog("KYC #$kycId Approved by {$user['full_name']}" . ($remarks ? " ($remarks)" : ""), 'kyc', $kycId);
    
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'KYC Application approved successfully. Partner is now marked as Active.']);
        exit;
    }

    setFlash('success', 'KYC Application approved successfully. Partner is now marked as Active.');
    $redir = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (APP_URL . '/?page=kyc&action=view&id=' . $kycId);
    header('Location: ' . $redir);
    exit;
}

// ------------------------------------------------------------------
// POST: Finance / Management Reject KYC
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reject') {
    verifyCsrf();
    requirePermission('kyc.reject');
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    $kycId  = (int)($_POST['kyc_id'] ?? 0);
    $reason = trim($_POST['rejection_reason'] ?? $_POST['review_notes'] ?? $_POST['notes'] ?? '');

    $stmt = $db->prepare("SELECT pka.*, p.name as partner_name FROM partner_kyc_applications pka LEFT JOIN partners p ON pka.partner_id = p.id WHERE pka.id = ?");
    $stmt->execute([$kycId]);
    $app = $stmt->fetch();

    if (!$app) {
        if ($isAjax) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'KYC application not found.']);
            exit;
        }
        setFlash('danger', 'KYC application not found.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    if ($app['status'] === 'Approved') {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'This KYC application is already approved and cannot be rejected.']);
            exit;
        }
        setFlash('danger', 'This KYC application is already approved and cannot be rejected.');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
        exit;
    }

    if (!in_array($app['status'], ['Submitted for Approval', 'Pending Approval', 'Submitted'])) {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'This KYC application cannot be rejected in its current status (' . $app['status'] . ').']);
            exit;
        }
        setFlash('danger', 'This KYC application cannot be rejected in its current status (' . $app['status'] . ').');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
        exit;
    }

    if (empty($reason)) {
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Rejection reason is mandatory.']);
            exit;
        }
        setFlash('danger', 'Rejection reason is mandatory.');
        header('Location: ' . APP_URL . '/?page=kyc&action=view&id=' . $kycId);
        exit;
    }

    $db->prepare("UPDATE partner_kyc_applications SET status = 'Rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
       ->execute([$reason, $user['id'], $kycId]);

    $actorRole = isFinance() ? 'Finance' : ($user['role'] ?: 'Management');
    recordKycHistory($db, $kycId, "KYC Rejected by {$actorRole}", $app['status'], 'Rejected', "Reason: $reason");

    queueKycNotification($kycId, 'Reject', $reason);
    publishSystemEvent('kyc_status_updated', null, ['kyc_id' => $kycId, 'status' => 'Rejected']);

    auditLog("KYC #$kycId Rejected. Reason: $reason", 'kyc', $kycId);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'KYC Application has been rejected and returned to the applicant with feedback.']);
        exit;
    }

    setFlash('warning', 'KYC Application has been rejected and returned to the applicant/KAM with feedback.');
    $redir = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (APP_URL . '/?page=kyc&action=view&id=' . $kycId);
    header('Location: ' . $redir);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete KYC Application (Admin & Management Only)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();

    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    // Strict Authorization Guard: Admin & Management users ONLY!
    // Partner and Contractor users are strictly forbidden.
    if (!isAdmin() && !isManagement() && !hasRole('System Admin', 'Admin', 'Management')) {
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admin and Management users can delete KYC applications.']);
            exit;
        }
        setFlash('danger', 'Unauthorized: Only Admin and Management users can delete KYC applications.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    $kycId = (int)($_POST['kyc_id'] ?? 0);
    if (!$kycId) {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid KYC application ID.']);
            exit;
        }
        setFlash('danger', 'Invalid KYC application ID.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    $stmt = $db->prepare("SELECT pka.*, p.name as partner_name FROM partner_kyc_applications pka JOIN partners p ON pka.partner_id = p.id WHERE pka.id = ?");
    $stmt->execute([$kycId]);
    $app = $stmt->fetch();

    if (!$app) {
        if ($isAjax) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'KYC application not found.']);
            exit;
        }
        setFlash('danger', 'KYC application not found.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    try {
        $db->beginTransaction();

        // 1. Audit trail preservation: Record audit log before deletion
        $appName = $app['registered_name'] ?: $app['partner_name'];
        auditLog("Deleted KYC Application #$kycId ($appName - {$app['kyc_type']})", 'kyc', $kycId);

        // 2. Safely clean up associated kyc_history records while preserving core audit_logs
        try {
            $db->prepare("DELETE FROM kyc_history WHERE kyc_id = ?")->execute([$kycId]);
        } catch (Exception $e) {}

        // 3. Delete the partner_kyc_applications record
        $db->prepare("DELETE FROM partner_kyc_applications WHERE id = ?")->execute([$kycId]);

        $db->commit();

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'KYC application deleted successfully.']);
            exit;
        }

        setFlash('success', 'KYC application deleted successfully.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Failed to delete KYC application: ' . $e->getMessage()]);
            exit;
        }

        setFlash('danger', 'Failed to delete KYC application: ' . e($e->getMessage()));
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }
}

// ------------------------------------------------------------------
// VIEW: Create / Edit / View / List KYC
// ------------------------------------------------------------------
$isPartnerOrContractor = (isPartnerUser() || isContractorUser());

if ($action === 'new' || $action === 'edit' || $action === 'view') {
    $kycId = (int)($_GET['id'] ?? 0);
    $app = null;

    if ($kycId > 0) {
        $stmt = $db->prepare("SELECT pka.*, p.name as partner_name, u.full_name as reviewer_name
            FROM partner_kyc_applications pka
            JOIN partners p ON pka.partner_id = p.id
            LEFT JOIN users u ON pka.reviewed_by = u.id
            WHERE pka.id = ?");
        $stmt->execute([$kycId]);
        $app = $stmt->fetch();
    } elseif ($isPartnerOrContractor) {
        $partnerId = (int)($user['partner_id'] ?? 0);
        $stmt = $db->prepare("SELECT pka.*, p.name as partner_name, u.full_name as reviewer_name
            FROM partner_kyc_applications pka
            JOIN partners p ON pka.partner_id = p.id
            LEFT JOIN users u ON pka.reviewed_by = u.id
            WHERE pka.partner_id = ? ORDER BY pka.updated_at DESC LIMIT 1");
        $stmt->execute([$partnerId]);
        $app = $stmt->fetch();
        if (!$app && $partnerId > 0) {
            $pStmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
            $pStmt->execute([$partnerId]);
            $pRow = $pStmt->fetch();
            if ($pRow) {
                $isContractorEnt = ($pRow['kyc_type'] === 'Contractor' || $pRow['partner_type'] === 'Contractor');
                $app = [
                    'id'                  => 0,
                    'partner_id'          => $pRow['id'],
                    'partner_name'        => $pRow['name'],
                    'registered_name'     => $pRow['name'],
                    'registration_number' => $pRow['registration_number'] ?? '',
                    'tin'                 => $pRow['tin'] ?? '',
                    'vat_vrn'             => $pRow['vat_vrn'] ?? '',
                    'office_address'      => $pRow['address'] ?? '',
                    'city_region'         => $pRow['city_region'] ?? 'Dar es Salaam',
                    'country'             => $pRow['country'] ?? 'Tanzania',
                    'kyc_type'            => $isContractorEnt ? 'Contractor' : 'Partner',
                    'status'              => 'Draft',
                    'auth_signatory_name' => $user['full_name'] ?? '',
                    'auth_signatory_email'=> $user['email'] ?? '',
                    'auth_signatory_mobile'=> $user['mobile'] ?? '',
                ];
            }
        }
    }

    if (!$app && !empty($_GET['partner_id'])) {
        $reqPartnerId = (int)$_GET['partner_id'];
        $checkStmt = $db->prepare("SELECT pka.*, p.name as partner_name, u.full_name as reviewer_name
            FROM partner_kyc_applications pka
            JOIN partners p ON pka.partner_id = p.id
            LEFT JOIN users u ON pka.reviewed_by = u.id
            WHERE pka.partner_id = ? ORDER BY pka.updated_at DESC LIMIT 1");
        $checkStmt->execute([$reqPartnerId]);
        $existingKyc = $checkStmt->fetch();
        if ($existingKyc) {
            header('Location: ' . APP_URL . '/?page=kyc&action=' . ($existingKyc['status'] === 'Approved' ? 'view' : 'edit') . '&id=' . $existingKyc['id']);
            exit;
        }

        $pStmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $pStmt->execute([$reqPartnerId]);
        $pRow = $pStmt->fetch();
        if ($pRow) {
            $isContractorEnt = ($pRow['kyc_type'] === 'Contractor' || $pRow['partner_type'] === 'Contractor');
            $app = [
                'id' => 0,
                'partner_id' => $pRow['id'],
                'partner_name' => $pRow['name'],
                'registered_name' => $pRow['name'],
                'registration_number' => $pRow['registration_number'] ?? '',
                'tin' => $pRow['tin'] ?? '',
                'vat_vrn' => $pRow['vat_vrn'] ?? '',
                'office_address' => $pRow['address'] ?? '',
                'city_region' => $pRow['city_region'] ?? 'Dar es Salaam',
                'country' => $pRow['country'] ?? 'Tanzania',
                'kyc_type' => $isContractorEnt ? 'Contractor' : 'Partner',
                'status' => 'Draft',
            ];
            $uStmt = $db->prepare("SELECT full_name, email, mobile FROM users WHERE partner_id = ? AND is_active = 1 LIMIT 1");
            $uStmt->execute([$reqPartnerId]);
            $uRow = $uStmt->fetch();
            if ($uRow) {
                $app['auth_signatory_name'] = $uRow['full_name'];
                $app['auth_signatory_email'] = $uRow['email'];
                $app['auth_signatory_mobile'] = $uRow['mobile'];
            }
        }
    }

    if ($isPartnerOrContractor && $app && (int)$user['partner_id'] !== (int)$app['partner_id']) {
        setFlash('danger', 'Unauthorized access.');
        header('Location: ' . APP_URL . '/?page=dashboard');
        exit;
    }

    $pWherePartner = partnerWhere('p');
    $partnersStmt = $db->prepare("SELECT p.id, p.name, p.partner_type, p.kyc_type,
            pka.status as kyc_status, pka.id as kyc_app_id
        FROM partners p
        LEFT JOIN partner_kyc_applications pka
            ON pka.partner_id = p.id AND pka.kyc_type = 'Partner'
        WHERE (p.kyc_type = 'Partner' OR (p.kyc_type IS NULL AND p.partner_type NOT IN ('Other')))
          AND {$pWherePartner['condition']}
        ORDER BY p.name ASC");
    $partnersStmt->execute($pWherePartner['params']);
    $partnersList = $partnersStmt->fetchAll();

    $contractorsList = $db->query("SELECT p.id, p.name, p.partner_type, p.kyc_type,
            pka.status as kyc_status, pka.id as kyc_app_id
        FROM partners p
        LEFT JOIN partner_kyc_applications pka
            ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
        WHERE p.kyc_type = 'Contractor'
        ORDER BY p.name ASC")->fetchAll();

    $history = [];
    if ($app && !empty($app['id'])) {
        $hStmt = $db->prepare("SELECT kh.*, u.full_name as actor_name FROM kyc_history kh LEFT JOIN users u ON kh.action_by = u.id WHERE kh.kyc_id = ? ORDER BY kh.created_at DESC");
        $hStmt->execute([$app['id']]);
        $history = $hStmt->fetchAll();
    }

    $pageTitle = ($action === 'view' ? 'Review KYC' : 'KYC Application');
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/kyc/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// Default: List view
if ($isPartnerOrContractor) {
    $partnerId = (int)($user['partner_id'] ?? 0);
    $stmt = $db->prepare("SELECT id, status FROM partner_kyc_applications WHERE partner_id = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$partnerId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $targetAction = in_array($existing['status'], ['Draft', 'Needs Modification']) ? 'edit' : 'view';
        header('Location: ' . APP_URL . '/?page=kyc&action=' . $targetAction . '&id=' . $existing['id']);
    } else {
        header('Location: ' . APP_URL . '/?page=kyc&action=new');
    }
    exit;
}

// Admin / Management List View
$pw = partnerWhere('pka');
$stmt = $db->prepare("SELECT pka.*, 
       p.name as partner_name, 
       p.partner_type,
       COALESCE(NULLIF(pka.tin, ''), p.tin) as effective_tin,
       COALESCE(NULLIF(pka.registration_number, ''), p.registration_number) as effective_reg_no,
       u.full_name as reviewer_name
    FROM partner_kyc_applications pka
    LEFT JOIN partners p ON pka.partner_id = p.id
    LEFT JOIN users u ON pka.reviewed_by = u.id
    WHERE {$pw['condition']}
    ORDER BY pka.updated_at DESC");
$stmt->execute($pw['params']);
$applications = $stmt->fetchAll();

// Calculate operational KPI metrics & group into ongoing vs approved
$totalCount = count($applications);
$submittedCount = 0;
$approvedCount = 0;
$draftCount = 0;
$rejectedCount = 0;

$ongoingApps = [];
$approvedApps = [];

foreach ($applications as $appItem) {
    $st = $appItem['status'] ?? 'Draft';
    if (in_array($st, ['Submitted for Approval', 'Pending Approval', 'Submitted'])) {
        $submittedCount++;
    } elseif ($st === 'Approved') {
        $approvedCount++;
    } elseif ($st === 'Rejected') {
        $rejectedCount++;
    } else {
        $draftCount++;
    }

    if ($st === 'Approved') {
        $approvedApps[] = $appItem;
    } else {
        $ongoingApps[] = $appItem;
    }
}

$activeTab = trim($_GET['tab'] ?? '');
if ($activeTab !== 'ongoing' && $activeTab !== 'approved') {
    $activeTab = (count($ongoingApps) === 0 && count($approvedApps) > 0) ? 'approved' : 'ongoing';
}

$pageTitle = 'KYC Applications';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/kyc/admin_list.php';
include APP_DIR . '/views/layout/footer.php';
