<?php
define('ROOT_DIR', 'c:/xampp/htdocs/Neilos');
define('APP_DIR',  ROOT_DIR . '/app');
define('APP_URL',  'http://localhost/Neilos');
require_once APP_DIR . '/config/database.php';
require_once APP_DIR . '/helpers/auth.php';
require_once APP_DIR . '/helpers/format.php';
require_once APP_DIR . '/helpers/audit.php';
require_once APP_DIR . '/helpers/sla.php';
require_once APP_DIR . '/helpers/icons.php';
require_once APP_DIR . '/helpers/notifications.php';
require_once APP_DIR . '/helpers/workflow.php';
require_once APP_DIR . '/helpers/migration.php';

// Mock session and user
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Project Manager';

$db = getDB();
$stmt = $db->prepare('SELECT o.*, p.name as partner_name, p.partner_type, p.partner_category,
        u_uat.full_name as uat_uploaded_by_name,
        u_kam.full_name as kam_name
    FROM orders o 
    JOIN partners p ON o.partner_id = p.id 
    LEFT JOIN users u_uat ON o.uat_uploaded_by = u_uat.id
    LEFT JOIN users u_kam ON o.kam_id = u_kam.id
    WHERE o.id = ?');
$stmt->execute([227]);
$baseOrder = $stmt->fetch();

// Test 1: Testing Status with PM role
$order = $baseOrder;
$order['status'] = 'Testing';
$status = 'Testing';
$docs = [];
$evidence = [];
$additionalCostEvidences = [];
$timeline = [];
$assignment = null;
$priceAudit = [];
$orderReturns = [];

ob_start();
try {
    include APP_DIR . '/views/orders/detail.php';
    $outTesting = ob_get_clean();
    echo "TESTING_RENDER: OK (" . strlen($outTesting) . " bytes)\n";
    echo "  Has Enterprise UAT Upload form: " . (str_contains($outTesting, 'action=upload_uat_form') ? 'YES' : 'NO') . "\n";
    echo "  Has Return to Installation button: " . (str_contains($outTesting, 'returnInstallationModal') ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in Testing render: " . $e->getMessage() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
}

// Test 2: UAT Status with KAM role
$_SESSION['user_role'] = 'KAM';
$order = $baseOrder;
$order['status'] = 'UAT';
$order['uat_signoff_file'] = 'uploads/orders/227/uat/sample_uat.pdf';
$order['uat_signoff_filename'] = 'Signed_UAT_ACM-DIA-261002-0001.pdf';
$order['uat_uploaded_at'] = '2026-10-06 14:00:00';
$status = 'UAT';

ob_start();
try {
    include APP_DIR . '/views/orders/detail.php';
    $outUat = ob_get_clean();
    echo "UAT_RENDER: OK (" . strlen($outUat) . " bytes)\n";
    echo "  Has Enterprise UAT title: " . (str_contains($outUat, 'Enterprise UAT') ? 'YES' : 'NO') . "\n";
    echo "  Has Accept Service action: " . (str_contains($outUat, 'action=uat_accept') ? 'YES' : 'NO') . "\n";
    echo "  Has Return to Installation action: " . (str_contains($outUat, 'action=return_to_installation') ? 'YES' : 'NO') . "\n";
    echo "  Has UAT form preview: " . (str_contains($outUat, 'Signed_UAT_ACM-DIA-261002-0001.pdf') ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in UAT render: " . $e->getMessage() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
}

// Save rendered HTMLs to scratch for browser viewing
file_put_contents(__DIR__ . '/test_order_testing.html', $outTesting ?? '');
file_put_contents(__DIR__ . '/test_order_uat.html', $outUat ?? '');
echo "FILES_WRITTEN: OK\n";
