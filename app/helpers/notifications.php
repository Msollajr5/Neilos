<?php
// ============================================================
// Neilos Partner Portal — Comprehensive Notification Engine
// Supports Workflow Alerts, Order Closed Multi-Recipient Broadcasts,
// Realtime SMTP Delivery and Background Queue Processing.
// ============================================================

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';

/**
 * Queue a notification in database
 */
function queueNotification(string $channel, string $recipient, string $subject, string $message, string $contextType = null, int $contextId = null, string $htmlBody = ''): int {
    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO notification_queue (channel, recipient, subject, message, context_type, context_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Queued', NOW())");
        $stmt->execute([$channel, $recipient, $subject, $message, $contextType, $contextId]);
        return (int)$db->lastInsertId();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Direct real-time notification with queue recording
 */
function dispatchDirectEmail(string|array $recipients, string $subject, string $htmlBody, string $plainText = '', string $contextType = null, int $contextId = null): array {
    $toArr = is_array($recipients) ? $recipients : array_map('trim', explode(',', $recipients));
    $toArr = array_filter($toArr, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
    
    if (empty($toArr)) {
        return ['success' => false, 'message' => 'No valid email recipients'];
    }

    // Attempt direct SMTP send
    $result = sendPortalEmail($toArr, $subject, $htmlBody, $plainText);

    // Record into notification_queue for audit trail
    $db = getDB();
    foreach ($toArr as $rcpt) {
        try {
            $stmt = $db->prepare("INSERT INTO notification_queue (channel, recipient, subject, message, context_type, context_id, status, attempts, error_message, sent_at, created_at) VALUES ('Email', ?, ?, ?, ?, ?, ?, 1, ?, IF(? = 'Sent', NOW(), NULL), NOW())");
            $stmt->execute([
                $rcpt,
                $subject,
                !empty($plainText) ? $plainText : strip_tags($htmlBody),
                $contextType,
                $contextId,
                $result['success'] ? 'Sent' : 'Failed',
                $result['success'] ? null : ($result['message'] ?? 'SMTP Error'),
                $result['success'] ? 'Sent' : 'Failed'
            ]);
        } catch (Throwable $t) {}
    }

    return $result;
}

/**
 * KYC Application Event Notifications
 */
function queueKycNotification(int $kycId, string $event, string $extraReason = ''): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pka.*, p.name as partner_name, p.contact_email as partner_contact_email,
               u.email as partner_user_email, u.full_name as partner_user_name
        FROM partner_kyc_applications pka
        JOIN partners p ON pka.partner_id = p.id
        LEFT JOIN users u ON u.partner_id = p.id AND u.is_active = 1
        WHERE pka.id = ?
        LIMIT 1
    ");
    $stmt->execute([$kycId]);
    $kyc = $stmt->fetch();
    if (!$kyc) return;

    $partnerEmail = $kyc['partner_user_email'] ?: ($kyc['partner_contact_email'] ?: 'partner@neilosnetwork.co.tz');
    $partnerName  = $kyc['partner_name'] ?: 'Partner';
    $baseUrl      = getPortalBaseUrl();
    $kycUrl       = $baseUrl . '/?page=kyc&action=view&id=' . $kycId;

    $adminEmails = ['admin@neilosnetwork.co.tz', 'manager@neilosnetwork.co.tz', 'john.kam@neilosnetwork.co.tz'];

    if ($event === 'Submit' || $event === 'Resubmit') {
        $subject = "KYC Application Submitted for Review — {$partnerName}";
        $html = renderKycStatusEmail($partnerName, $partnerName, 'Submitted for Approval', 'Your application is undergoing commercial and legal compliance verification.', $kycUrl);
        dispatchDirectEmail($partnerEmail, $subject, $html, '', 'kyc', $kycId);

        $adminHtml = renderKycStatusEmail('Compliance Team', $partnerName, 'Submitted for Approval', "KYC application #{$kycId} for {$partnerName} requires compliance review and approval.", $kycUrl);
        dispatchDirectEmail($adminEmails, "New KYC Application for Approval: {$partnerName}", $adminHtml, '', 'kyc', $kycId);
    } elseif ($event === 'Approve') {
        $subject = "Congratulations! KYC Application Approved — {$partnerName}";
        $html = renderKycStatusEmail($partnerName, $partnerName, 'Approved', 'Your account has been fully verified and activated. You may now create and track service orders.', $kycUrl);
        dispatchDirectEmail($partnerEmail, $subject, $html, '', 'kyc', $kycId);

        $adminHtml = renderKycStatusEmail('Management Team', $partnerName, 'Approved', "KYC application #{$kycId} for {$partnerName} has been approved and activated.", $kycUrl);
        dispatchDirectEmail($adminEmails, "KYC Approved: {$partnerName}", $adminHtml, '', 'kyc', $kycId);
    } elseif ($event === 'Reject') {
        $subject = "KYC Application Requires Corrections — {$partnerName}";
        $html = renderKycStatusEmail($partnerName, $partnerName, 'Rejected', "Rejection Reason: {$extraReason}. Please log in to update and resubmit your details.", $kycUrl);
        dispatchDirectEmail($partnerEmail, $subject, $html, '', 'kyc', $kycId);
    }
}

/**
 * Main Order Lifecycle Notification Dispatcher
 */
function queueOrderNotification(int $orderId, string $event, ?int $actorUserId = null): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, p.name as partner_name, p.contact_email as partner_contact_email,
               p.contact_person_name as partner_contact_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) return;

    // Resolve Actor info
    $actorName = 'Neilos System';
    $actorRole = 'System';
    if ($actorUserId) {
        $uStmt = $db->prepare("SELECT full_name, role FROM users WHERE id = ?");
        $uStmt->execute([$actorUserId]);
        if ($u = $uStmt->fetch()) {
            $actorName = $u['full_name'];
            $actorRole = $u['role'];
        }
    } elseif (!empty($_SESSION['full_name'])) {
        $actorName = $_SESSION['full_name'];
        $actorRole = $_SESSION['user_role'] ?? 'Staff';
    }

    // Resolve Role emails
    $getRoleEmails = function(string $role, string $fallback) use ($db) {
        $emails = $db->query("SELECT email FROM users WHERE role = '{$role}' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        return !empty($emails) ? $emails : [$fallback];
    };

    $bsaEmails  = $getRoleEmails('BSA', 'comfortmnyinga@gmail.com');
    $kamEmails  = $getRoleEmails('KAM', 'john.kam@neilosnetwork.co.tz');
    $mgmtEmails = $getRoleEmails('Management', 'manager@neilosnetwork.co.tz');
    $pmEmails   = $getRoleEmails('Project Manager', 'pm@neilosnetwork.co.tz');
    $finEmails  = $getRoleEmails('Billing', 'billing@neilosnetwork.co.tz');

    // Partner emails
    $partnerUserEmails = $db->query("SELECT email FROM users WHERE partner_id = {$o['partner_id']} AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    $partnerEmails = array_unique(array_filter(array_merge($partnerUserEmails, [$o['partner_contact_email'] ?? ''])));
    if (empty($partnerEmails)) $partnerEmails = ['partner@neilosnetwork.co.tz'];

    $baseUrl   = getPortalBaseUrl();
    $orderUrl  = $baseUrl . '/?page=orders&action=detail&id=' . $orderId;
    $orderNum  = $o['order_number'];
    $custName  = $o['customer_name'];
    $svcType   = $o['service_type'];

    switch ($event) {
        // 1. Feasibility Submitted -> BSA Action Required
        case 'Feasibility Submitted':
        case 'Order Created':
            $subject = "Action Required: New Feasibility Review for Order {$orderNum} — {$custName}";
            $desc = "A new service order request has been submitted by <strong>{$o['partner_name']}</strong> and is awaiting technical feasibility assessment and BoM pricing.";
            $html = renderWorkflowActionEmail('BSA Engineering Team', $actorName, $actorRole, $o, "Technical Feasibility Review Required", $desc, $orderUrl);
            dispatchDirectEmail($bsaEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 2. BSA Feasibility Approved -> KAM Commercial Review Action Required
        case 'Technically Feasible':
        case 'Feasibility Approved':
        case 'BSA Feasibility Approved':
            $subject = "Action Required: Commercial Review for Order {$orderNum} — {$custName}";
            $desc = "BSA Engineering has confirmed technical feasibility and itemized BoM costs. Please define commercial pricing, exchange rate (TZS/USD), and submit for approval.";
            $html = renderWorkflowActionEmail('Key Account Management (KAM)', $actorName, $actorRole, $o, "Commercial Review & Pricing Required", $desc, $orderUrl);
            dispatchDirectEmail($kamEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 3. KAM Escalated to Management -> Management Action Required
        case 'KAM Requires Further Approval':
        case 'Management Approval Required':
            $subject = "Action Required: Pricing Exception Review for Order {$orderNum} — {$custName}";
            $desc = "KAM <strong>{$actorName}</strong> has submitted non-standard commercial pricing terms (MRC/NRC/Exchange Rate) for your executive review and approval.";
            $html = renderWorkflowActionEmail('Management Approver', $actorName, $actorRole, $o, "Management Pricing Exception Approval Required", $desc, $orderUrl);
            dispatchDirectEmail($mgmtEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 4. Management Approved Pricing -> Partner Sign SOF Action Required
        case 'Management Approved':
        case 'Pricing Approved':
        case 'Management Pricing Approved':
            $subject = "Service Order Form (SOF) Ready to Sign: Order {$orderNum} — {$custName}";
            $desc = "Great news! Commercial terms for your order have been approved. Please download your Service Order Form (SOF), sign it, and upload the signed document to proceed.";
            $html = renderWorkflowActionEmail($o['partner_name'], $actorName, $actorRole, $o, "Download & Sign Service Order Form (SOF)", $desc, $orderUrl);
            dispatchDirectEmail($partnerEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 5. Partner Uploads Signed SOF -> KAM Countersign Action Required
        case 'Signed SOF Uploaded':
            $subject = "Action Required: Countersign SOF for Order {$orderNum} — {$custName}";
            $desc = "Partner <strong>{$o['partner_name']}</strong> has uploaded their signed Service Order Form (SOF). Please review, countersign, and upload the executed Neilos copy to release this project.";
            $html = renderWorkflowActionEmail('Key Account Manager', $actorName, $actorRole, $o, "Countersign Service Order Form (SOF)", $desc, $orderUrl);
            dispatchDirectEmail($kamEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 6. KAM Countersigns SOF -> PM Contractor Assignment Action Required + Partner Notification
        case 'Countersigned SOF Uploaded':
        case 'Proceed to Project':
            // Notify PM
            $pmSubject = "Action Required: Contractor Assignment for Order {$orderNum} — {$custName}";
            $pmDesc = "The Service Order Form (SOF) has been fully executed by both parties. Please assign a field contractor and schedule site installation.";
            $pmHtml = renderWorkflowActionEmail('Project Manager', $actorName, $actorRole, $o, "Assign Contractor for Installation", $pmDesc, $orderUrl);
            dispatchDirectEmail($pmEmails, $pmSubject, $pmHtml, '', 'order', $orderId);

            // Notify Partner
            $pSubject = "SOF Executed — Installation is Commencing: Order {$orderNum}";
            $pDesc = "The Service Order Form has been fully countersigned and executed. Our project deployment team will now schedule installation access.";
            $pHtml = renderWorkflowActionEmail($o['partner_name'], $actorName, $actorRole, $o, "Installation Scheduled", $pDesc, $orderUrl);
            dispatchDirectEmail($partnerEmails, $pSubject, $pHtml, '', 'order', $orderId);
            break;

        // 7. Contractor Completed -> PM & BSA Testing Review Action Required
        case 'Contractor Completed':
            $subject = "Action Required: Installation Testing & Evidence Review for Order {$orderNum}";
            $desc = "The field installation crew has submitted their completion report, serial numbers, and test evidence. Please review and verify technical parameters for UAT release.";
            $html = renderWorkflowActionEmail('Operations & Engineering Team', $actorName, $actorRole, $o, "Review Test Results & Approve for UAT", $desc, $orderUrl);
            dispatchDirectEmail(array_merge($pmEmails, $bsaEmails), $subject, $html, '', 'order', $orderId);
            break;

        // 8. Testing Approved -> Partner UAT Action Required
        case 'Testing Approved':
            $subject = "Action Required: Service Ready for User Acceptance Testing (UAT): Order {$orderNum}";
            $desc = "Physical installation and internal tests are complete. Your 72-hour business day UAT window is now active. Please log in to test and accept the service or report any issues.";
            $html = renderWorkflowActionEmail($o['partner_name'], $actorName, $actorRole, $o, "Perform User Acceptance Testing (UAT)", $desc, $orderUrl);
            dispatchDirectEmail($partnerEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 9. ORDER CLOSED -> Simultaneous Multi-Recipient Broadcast to Management, Partner, KAM, Finance
        case 'Partner Accepted Service':
        case 'Order Closed':
        case 'Closed':
        case 'Service Activated':
            $closedDate = date('Y-m-d H:i:s');
            $activeServiceId = $o['order_number'];

            // 1. Management
            $mgmtHtml = renderOrderClosedEmail('Executive Management', 'Management', $o, $closedDate, $activeServiceId);
            dispatchDirectEmail($mgmtEmails, "Order Closed & Live: {$orderNum} — {$custName} ({$svcType})", $mgmtHtml, '', 'order', $orderId);

            // 2. Partner
            $partnerHtml = renderOrderClosedEmail($o['partner_name'], 'Partner Admin', $o, $closedDate, $activeServiceId);
            dispatchDirectEmail($partnerEmails, "Service Activated & Order Closed: {$orderNum} — {$custName}", $partnerHtml, '', 'order', $orderId);

            // 3. KAM
            $kamHtml = renderOrderClosedEmail('Key Account Management', 'Commercials', $o, $closedDate, $activeServiceId);
            dispatchDirectEmail($kamEmails, "Order Closed & Live: {$orderNum} — {$custName} ({$svcType})", $kamHtml, '', 'order', $orderId);

            // 4. Finance & Billing
            $finHtml = renderOrderClosedEmail('Finance & Billing Department', 'Billing Operations', $o, $closedDate, $activeServiceId);
            dispatchDirectEmail($finEmails, "Billing Commencement Notice: Order Closed {$orderNum} — {$custName}", $finHtml, '', 'order', $orderId);
            break;

        // 10. Partner / Management Returns
        case 'Partner Returned Feasibility':
        case 'Returned to KAM':
        case 'Management Returned to KAM':
            $subject = "Order Returned by Partner/Management: {$orderNum} — {$custName}";
            $desc = "Order has been returned with remarks for commercial/feasibility review.";
            $html = renderWorkflowActionEmail('KAM & Commercial Team', $actorName, $actorRole, $o, "Order Returned for Review", $desc, $orderUrl);
            dispatchDirectEmail($kamEmails, $subject, $html, '', 'order', $orderId);
            break;

        // 11. PM Returns to Contractor
        case 'PM Returned to Contractor':
            $contractorEmail = $db->query("SELECT p.contact_email FROM contractor_assignments ca JOIN partners p ON ca.contractor_partner_id = p.id WHERE ca.order_id = {$orderId} ORDER BY ca.id DESC LIMIT 1")->fetchColumn();
            if ($contractorEmail) {
                $subject = "Action Required: Installation Corrections for Order {$orderNum}";
                $desc = "Project Manager has returned the job for corrections. Please review the rejection notes and resubmit upon fixing.";
                $html = renderWorkflowActionEmail('Contractor Team', $actorName, $actorRole, $o, "Installation Corrections Required", $desc, $baseUrl . '/?page=contractor');
                dispatchDirectEmail($contractorEmail, $subject, $html, '', 'order', $orderId);
            }
            break;

        default:
            break;
    }
}

/**
 * Process any pending items in notification_queue
 */
function processNotificationQueue(int $limit = 10): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notification_queue WHERE status = 'Queued' ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$limit]);
    $items = $stmt->fetchAll();
    $processed = 0;

    foreach ($items as $item) {
        $res = sendPortalEmail($item['recipient'], $item['subject'], nl2br(htmlspecialchars($item['message'])), $item['message']);
        $success = $res['success'];
        $db->prepare("UPDATE notification_queue SET status = ?, attempts = attempts + 1, error_message = ?, sent_at = IF(? = 'Sent', NOW(), NULL) WHERE id = ?")
           ->execute([
               $success ? 'Sent' : 'Failed',
               $success ? null : ($res['message'] ?? 'Delivery failed'),
               $success ? 'Sent' : 'Failed',
               $item['id']
           ]);
        $processed++;
    }
    return $processed;
}

/**
 * Legacy compatibility wrapper
 */
function sendNotification(string $channel, string $recipient, string $subject, string $message): bool {
    if ($channel === 'Email') {
        $res = sendPortalEmail($recipient, $subject, nl2br(htmlspecialchars($message)), $message);
        return $res['success'];
    }
    return false;
}
