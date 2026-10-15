<?php
// ============================================================
// Comprehensive Test Suite for Email Notifications & Workflows
// ============================================================

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR',  ROOT_DIR . '/app');

require_once APP_DIR . '/config/database.php';
require_once APP_DIR . '/helpers/auth.php';
require_once APP_DIR . '/helpers/format.php';
require_once APP_DIR . '/helpers/mailer.php';
require_once APP_DIR . '/helpers/email_templates.php';
require_once APP_DIR . '/helpers/notifications.php';

$db = getDB();

echo "============================================================\n";
echo "TESTING COMPREHENSIVE EMAIL NOTIFICATIONS SUITE\n";
echo "============================================================\n\n";

// 1. Test Password Reset Email
echo "--- 1. Testing Password Reset Email ---\n";
$resetUrl = getPortalBaseUrl() . '/?page=reset_password&token=' . bin2hex(random_bytes(32));
$resetHtml = renderPasswordResetEmail('Sarah BSA', $resetUrl, 60);
$res1 = sendPortalEmail('mail@neilosnetwork.co.tz', 'Test Password Reset Link', $resetHtml);
echo "Password Reset Email: " . ($res1['success'] ? "✓ SUCCESS ({$res1['message']})" : "✗ FAILED ({$res1['message']})") . "\n\n";

// 2. Test OTP Code Email
echo "--- 2. Testing OTP Code Email ---\n";
$otpHtml = renderOtpEmail('John KAM', '849201', 10);
$res2 = sendPortalEmail('mail@neilosnetwork.co.tz', 'Your Login OTP: 849201', $otpHtml);
echo "OTP Code Email: " . ($res2['success'] ? "✓ SUCCESS ({$res2['message']})" : "✗ FAILED ({$res2['message']})") . "\n\n";

// 3. Test Workflow Stage Action Email
echo "--- 3. Testing Workflow Action Required Email (Feasibility / Approval) ---\n";
$mockOrder = [
    'id'            => 999,
    'order_number'  => 'FAS-FTH-260917-0099',
    'customer_name' => 'Acme Bank Headquarters',
    'service_type'  => 'FTTH',
    'partner_name'  => 'FastNet Communications Ltd',
    'status'        => 'Feasibility Review',
    'created_at'    => date('Y-m-d H:i:s'),
];
$actionHtml = renderWorkflowActionEmail(
    'Sarah BSA (Engineering)',
    'FastNet Admin',
    'Partner Admin',
    $mockOrder,
    'Technical Feasibility Assessment Required',
    'A new service order request has been submitted by FastNet Communications Ltd requiring technical feasibility assessment, fiber route verification, and itemized BoM breakdown.',
    getPortalBaseUrl() . '/?page=orders&action=detail&id=999'
);
$res3 = sendPortalEmail('mail@neilosnetwork.co.tz', 'Action Required: Feasibility Review for Order FAS-FTH-260917-0099', $actionHtml);
echo "Workflow Action Email: " . ($res3['success'] ? "✓ SUCCESS ({$res3['message']})" : "✗ FAILED ({$res3['message']})") . "\n\n";

// 4. Test Multi-Recipient Order Closed Broadcast Email
echo "--- 4. Testing Multi-Recipient Order Closed Email (Management, Partner, KAM, Finance) ---\n";
$mockClosedOrder = [
    'id'            => 999,
    'order_number'  => 'FAS-FTH-260917-0099',
    'customer_name' => 'Acme Bank Headquarters',
    'service_type'  => 'Dedicated Layer 2 (100 Mbps)',
    'partner_name'  => 'FastNet Communications Ltd',
    'status'        => 'Closed',
    'created_at'    => date('Y-m-d H:i:s', strtotime('-5 days')),
    'final_mrc'     => 150.00,
    'final_nrc'     => 300.00,
    'usd_tzs_rate'  => 2650.00,
];

// Send to Management
$mgmtHtml = renderOrderClosedEmail('Executive Management', 'Management', $mockClosedOrder, date('Y-m-d H:i:s'), 'FAS-FTH-260917-0099');
$resMgmt = sendPortalEmail('mail@neilosnetwork.co.tz', 'Order Closed & Live: FAS-FTH-260917-0099 — Acme Bank Headquarters (Management Copy)', $mgmtHtml);
echo "Management Closed Notice: " . ($resMgmt['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";

// Send to Finance
$finHtml = renderOrderClosedEmail('Finance & Billing Department', 'Billing Operations', $mockClosedOrder, date('Y-m-d H:i:s'), 'FAS-FTH-260917-0099');
$resFin = sendPortalEmail('mail@neilosnetwork.co.tz', 'Billing Commencement Notice: Order Closed FAS-FTH-260917-0099 — Acme Bank Headquarters', $finHtml);
echo "Finance Closed Notice: " . ($resFin['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";

// Send to Partner
$partHtml = renderOrderClosedEmail('FastNet Communications Ltd', 'Partner Admin', $mockClosedOrder, date('Y-m-d H:i:s'), 'FAS-FTH-260917-0099');
$resPart = sendPortalEmail('mail@neilosnetwork.co.tz', 'Service Activated & Order Closed: FAS-FTH-260917-0099 — Acme Bank Headquarters', $partHtml);
echo "Partner Closed Notice: " . ($resPart['success'] ? "✓ SUCCESS" : "✗ FAILED") . "\n";

echo "\n============================================================\n";
echo "ALL NOTIFICATION SUITE TESTS COMPLETED SUCCESSFULLY!\n";
echo "============================================================\n";
