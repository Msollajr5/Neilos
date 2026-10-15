<?php
// ============================================================
// Neilos Partner Portal — System-wide Secure File Download Controller
// ============================================================

requireLogin();

$db = getDB();
$user = currentUser();
$table = trim($_GET['table'] ?? '');
$column = trim($_GET['column'] ?? 'file_path');
$id = (int)($_GET['id'] ?? 0);
$rawFile = trim($_GET['file'] ?? '');

$filePath = '';
$fileName = '';
$mimeType = '';
$isInline = (isset($_GET['inline']) && ($_GET['inline'] === '1' || $_GET['inline'] === 'true')) || (isset($_GET['view']) && $_GET['view'] === '1');

function handleDownloadError(int $statusCode, string $title, string $message, string $targetFile = ''): void {
    global $isInline;
    http_response_code($statusCode);

    if ($isInline) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title><?= htmlspecialchars($title) ?></title>
          <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
            body { background: #0b1120; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
            .error-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px 28px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4); }
            .icon-wrap { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.15); color: #ef4444; font-size: 28px; }
            h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; color: #f1f5f9; }
            p { font-size: 0.9rem; color: #94a3b8; line-height: 1.5; margin-bottom: 20px; }
            .meta { background: #0b1120; border-radius: 6px; padding: 10px 14px; font-size: 0.8rem; font-family: monospace; color: #cbd5e1; word-break: break-all; margin-bottom: 24px; border: 1px solid #334155; text-align: left; }
            .btn-close { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #3b82f6; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: background 0.2s; }
            .btn-close:hover { background: #2563eb; }
          </style>
        </head>
        <body>
          <div class="error-card">
            <div class="icon-wrap">⚠️</div>
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($message) ?></p>
            <?php if (!empty($targetFile)): ?>
              <div class="meta"><strong>Target File:</strong> <?= htmlspecialchars($targetFile) ?></div>
            <?php endif; ?>
            <button type="button" class="btn-close" onclick="if(window.parent && window.parent.closeGlobalFileViewModal){ window.parent.closeGlobalFileViewModal(); } else if(window.parent && window.parent.closeDocumentModal){ window.parent.closeDocumentModal(); } else { window.close(); }">
              Close Preview
            </button>
          </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Standard download request - redirect with flash message
    setFlash('danger', $message);
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    header('Location: ' . (!empty($referrer) ? $referrer : (APP_URL . '/?page=dashboard')));
    exit;
}

function denyDownloadAccess(): void {
    handleDownloadError(403, 'Access Denied', 'You do not have authorization to view or download this document.');
}

if ($table && $id > 0) {
    // 1. Contractor Evidence
    if ($table === 'contractor_evidence') {
        $stmt = $db->prepare("SELECT ce.*, ca.contractor_partner_id, o.partner_id as order_partner_id 
            FROM contractor_evidence ce 
            LEFT JOIN contractor_assignments ca ON ce.assignment_id = ca.id 
            LEFT JOIN orders o ON ce.order_id = o.id 
            WHERE ce.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if (isPartnerUser()) {
                if ((int)$row['order_partner_id'] !== (int)$user['partner_id']) {
                    denyDownloadAccess();
                }
            } elseif (isContractorUser()) {
                $myPartnerId = (int)$user['partner_id'];
                $isAssigned = $row['contractor_partner_id'] && (int)$row['contractor_partner_id'] === $myPartnerId;
                $isUploader = (int)$row['uploaded_by'] === (int)$user['id'];
                if (!$isAssigned && !$isUploader) {
                    denyDownloadAccess();
                }
            }
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: 'evidence_' . $id;
        }
    }
    // 2. Order Documents
    elseif ($table === 'order_documents') {
        $stmt = $db->prepare("SELECT od.*, o.partner_id as order_partner_id 
            FROM order_documents od 
            LEFT JOIN orders o ON od.order_id = o.id 
            WHERE od.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if (isPartnerUser()) {
                if ((int)$row['order_partner_id'] !== (int)$user['partner_id']) {
                    denyDownloadAccess();
                }
            } elseif (isContractorUser()) {
                // Contractor must be assigned to this order
                $assignCheck = $db->prepare("SELECT 1 FROM contractor_assignments WHERE order_id = ? AND contractor_partner_id = ? LIMIT 1");
                $assignCheck->execute([$row['order_id'], (int)$user['partner_id']]);
                if (!$assignCheck->fetch()) {
                    denyDownloadAccess();
                }
                // Contractors cannot view signed SOFs or confidential commercial invoices of the partner
                if (in_array(strtolower($row['doc_type'] ?? ''), ['sof', 'countersigned_sof', 'invoice'])) {
                    denyDownloadAccess();
                }
            }
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: 'order_document_' . $id;
        }
    }
    // 3. KYC Documents
    elseif ($table === 'partner_kyc_application_documents' || $table === 'kyc_documents') {
        try {
            $stmt = $db->prepare("SELECT kd.*, pka.partner_id 
                FROM kyc_documents kd 
                LEFT JOIN partner_kyc_applications pka ON kd.application_id = pka.id 
                WHERE kd.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                if (isExternalUser() && (int)$row['partner_id'] !== (int)$user['partner_id']) {
                    denyDownloadAccess();
                }
                $filePath = $row['file_path'];
                $fileName = $row['file_name'] ?? ($row['document_name'] ?? ('kyc_document_' . $id));
            }
        } catch (Throwable $t) {}
    }
    // 4. Orders Table (SOF signed / countersigned / UAT)
    elseif ($table === 'orders') {
        $validColumns = ['sof_signed_file', 'countersigned_sof_file', 'uat_signoff_file'];
        if (in_array($column, $validColumns)) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && !empty($row[$column])) {
                if (isPartnerUser()) {
                    if ((int)$row['partner_id'] !== (int)$user['partner_id']) {
                        denyDownloadAccess();
                    }
                } elseif (isContractorUser()) {
                    // Contractor can only access UAT signoff if assigned to this order
                    if ($column !== 'uat_signoff_file') {
                        denyDownloadAccess();
                    }
                    $assignCheck = $db->prepare("SELECT 1 FROM contractor_assignments WHERE order_id = ? AND contractor_partner_id = ? LIMIT 1");
                    $assignCheck->execute([$row['id'], (int)$user['partner_id']]);
                    if (!$assignCheck->fetch()) {
                        denyDownloadAccess();
                    }
                }
                $filePath = $row[$column];
                $nameCol = ($column === 'sof_signed_file') ? 'sof_signed_filename' : (($column === 'countersigned_sof_file') ? 'countersigned_sof_filename' : '');
                $fileName = (!empty($nameCol) && !empty($row[$nameCol])) ? $row[$nameCol] : basename($filePath);
            }
        }
    }
    // 5. KYC Applications Table (All KYC compliance & legal documents)
    elseif ($table === 'partner_kyc_applications') {
        $validColumns = [
            'business_license', 'hse_certificate', 'trca_certificate', 'contractor_agreement', 
            'signed_sla_file', 'safety_esg_file', 'confidentiality_nda_file', 'service_regions_file', 
            'partner_tin_certificate', 'partner_vrn_certificate', 'partner_incorporation_cert', 
            'partner_tcra_certificate', 'partner_tax_clearance', 'partner_business_licence'
        ];
        if (in_array($column, $validColumns)) {
            $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && !empty($row[$column])) {
                if (isExternalUser() && (int)$row['partner_id'] !== (int)$user['partner_id']) {
                    denyDownloadAccess();
                }
                $filePath = $row[$column];
                $fileName = basename($filePath);
            }
        }
    }
    // 6. Survey Evidence (Photos, Diagrams, Route Findings)
    elseif ($table === 'survey_evidence') {
        $stmt = $db->prepare("SELECT se.*, st.contractor_partner_id 
            FROM survey_evidence se 
            JOIN survey_tasks st ON se.survey_id = st.id 
            WHERE se.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if (isContractorUser()) {
                if ((int)$row['contractor_partner_id'] !== (int)$user['partner_id']) {
                    denyDownloadAccess();
                }
            }
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: ('survey_evidence_' . $id);
        }
    }
} elseif (!empty($rawFile)) {
    // Direct sanitized relative path fallback
    $sanitized = ltrim(str_replace(['..', "\0"], '', $rawFile), '/\\');
    
    // Prevent path traversal and enforce allowed base folders
    $allowedPrefixes = ['uploads/', 'assets/'];
    $isAllowedPrefix = false;
    foreach ($allowedPrefixes as $prefix) {
        if (strpos($sanitized, $prefix) === 0) {
            $isAllowedPrefix = true;
            break;
        }
    }
    
    if ($isAllowedPrefix) {
        // Public assets and avatars
        if (strpos($sanitized, 'assets/') === 0 || strpos($sanitized, 'uploads/avatars/') === 0 || strpos($sanitized, 'uploads/company/') === 0) {
            $filePath = $sanitized;
            $fileName = basename($filePath);
        } elseif (isExternalUser()) {
            // For external users accessing documents via direct file path, verify tenant ownership
            $myPartnerId = (int)($user['partner_id'] ?? 0);
            $authorized = false;
            
            // 1. Check orders
            $check = $db->prepare("SELECT 1 FROM orders WHERE partner_id = ? AND (sof_signed_file = ? OR countersigned_sof_file = ? OR uat_signoff_file = ?) LIMIT 1");
            $check->execute([$myPartnerId, $sanitized, $sanitized, $sanitized]);
            if ($check->fetch()) {
                $authorized = true;
            }
            
            // 2. Check order_documents
            if (!$authorized) {
                $check = $db->prepare("SELECT 1 FROM order_documents od JOIN orders o ON od.order_id = o.id WHERE o.partner_id = ? AND od.file_path = ? LIMIT 1");
                $check->execute([$myPartnerId, $sanitized]);
                if ($check->fetch()) {
                    $authorized = true;
                }
            }
            
            // 3. Check contractor_evidence
            if (!$authorized) {
                if (isPartnerUser()) {
                    $check = $db->prepare("SELECT 1 FROM contractor_evidence ce JOIN orders o ON ce.order_id = o.id WHERE o.partner_id = ? AND ce.file_path = ? LIMIT 1");
                    $check->execute([$myPartnerId, $sanitized]);
                    if ($check->fetch()) {
                        $authorized = true;
                    }
                } elseif (isContractorUser()) {
                    $check = $db->prepare("SELECT 1 FROM contractor_evidence ce JOIN contractor_assignments ca ON ce.assignment_id = ca.id WHERE ca.contractor_partner_id = ? AND ce.file_path = ? LIMIT 1");
                    $check->execute([$myPartnerId, $sanitized]);
                    if ($check->fetch()) {
                        $authorized = true;
                    }
                }
            }
            
            // 4. Check partner_kyc_applications
            if (!$authorized) {
                $check = $db->prepare("SELECT 1 FROM partner_kyc_applications WHERE partner_id = ? AND (
                    business_license = ? OR hse_certificate = ? OR trca_certificate = ? OR contractor_agreement = ? OR
                    signed_sla_file = ? OR safety_esg_file = ? OR confidentiality_nda_file = ? OR service_regions_file = ? OR
                    partner_tin_certificate = ? OR partner_vrn_certificate = ? OR partner_incorporation_cert = ? OR
                    partner_tcra_certificate = ? OR partner_tax_clearance = ? OR partner_business_licence = ?
                ) LIMIT 1");
                $check->execute(array_merge([$myPartnerId], array_fill(0, 14, $sanitized)));
                if ($check->fetch()) {
                    $authorized = true;
                }
            }
            
            // 5. Check kyc_documents
            if (!$authorized) {
                $check = $db->prepare("SELECT 1 FROM kyc_documents kd JOIN partner_kyc_applications pka ON kd.application_id = pka.id WHERE pka.partner_id = ? AND kd.file_path = ? LIMIT 1");
                $check->execute([$myPartnerId, $sanitized]);
                if ($check->fetch()) {
                    $authorized = true;
                }
            }
            
            if ($authorized) {
                $filePath = $sanitized;
                $fileName = basename($filePath);
            } else {
                denyDownloadAccess();
            }
        } else {
            // Internal staff
            $filePath = $sanitized;
            $fileName = basename($filePath);
        }
    }
}

if (empty($filePath)) {
    handleDownloadError(404, 'Document Record Not Found', 'The requested document record does not exist in the database or has not been uploaded yet.', $fileName ?: 'Unknown');
}

$relativePath = ltrim(str_replace('\\', '/', $filePath), '/');
$fullPath = PUBLIC_DIR . '/' . $relativePath;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    $fullPath = dirname(APP_DIR) . '/public/' . $relativePath;
}

if (!file_exists($fullPath) || !is_file($fullPath)) {
    $fullPath = dirname(APP_DIR) . '/' . $relativePath;
}

// Canonical path traversal barrier
$realFullPath = realpath($fullPath);
$realRoot = realpath(dirname(APP_DIR));
if (!$realFullPath || strpos($realFullPath, $realRoot) !== 0 || !is_file($realFullPath)) {
    handleDownloadError(404, 'File Missing on Server Storage', 'The physical document file could not be located on the server filesystem. Please re-upload this document in the KYC application form.', basename($filePath));
}
$fullPath = $realFullPath;

if (!$fileName) {
    $fileName = basename($fullPath);
}

// Determine MIME type with reliable extension mapping for inline viewing
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
    'txt'  => 'text/plain',
    'log'  => 'text/plain',
    'csv'  => 'text/csv',
    'json' => 'application/json',
    'xml'  => 'application/xml',
    'html' => 'text/html',
];

$mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
$disposition = $isInline ? 'inline' : 'attachment';

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace(['"', "'", "\r", "\n"], '', $fileName) . '"');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;
