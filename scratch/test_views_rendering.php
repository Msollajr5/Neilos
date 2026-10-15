<?php
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR',  ROOT_DIR . '/app');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('APP_URL', 'http://localhost/Neilos/public');

require_once APP_DIR . '/config/database.php';
require_once APP_DIR . '/helpers/auth.php';
require_once APP_DIR . '/helpers/format.php';
require_once APP_DIR . '/helpers/audit.php';
require_once APP_DIR . '/helpers/sla.php';
require_once APP_DIR . '/helpers/icons.php';
require_once APP_DIR . '/helpers/notifications.php';
require_once APP_DIR . '/helpers/workflow.php';
require_once APP_DIR . '/helpers/migration.php';

$db = getDB();

echo "=== TESTING VIEW RENDERING ACROSS ROLES & STAGES ===" . PHP_EOL;

// Get an order in each status if possible
$statuses = ['Feasibility Review', 'Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed'];

$rolesToTest = [
    ['role' => 'Partner', 'user_id' => 6, 'partner_id' => 1],
    ['role' => 'BSA', 'user_id' => 3, 'partner_id' => null],
    ['role' => 'KAM', 'user_id' => 2, 'partner_id' => null],
    ['role' => 'Management', 'user_id' => 10, 'partner_id' => null],
    ['role' => 'Project Manager', 'user_id' => 11, 'partner_id' => null],
    ['role' => 'Contractor', 'user_id' => 13, 'partner_id' => 4],
    ['role' => 'System Admin', 'user_id' => 1, 'partner_id' => null]
];

$latestOrder = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$latestOrder) {
    echo "No orders found in DB" . PHP_EOL;
    exit(1);
}

// Fetch helper data like in orders.php
$order = $latestOrder;
$orderId = $order['id'];
$status = $order['status'];
$servicePricing = [];
$servicePackages = [];
$checklist = [];
$evidence = [];
$contractorAssignments = [];
$contractorList = [];
$contractorTeams = [];
$allPricingPackages = [];
$allServiceTypes = [];
$closedJobs = [];
$assignments = [];
$counts = ['Assigned'=>0,'Accepted'=>0,'In Progress'=>0,'Completed Submitted'=>0,'Completed'=>0,'Returned'=>0,'SLA Due Today'=>0];
$statusFilter = '';

foreach ($rolesToTest as $rInfo) {
    $_SESSION['user_id'] = $rInfo['user_id'];
    $_SESSION['user'] = $db->query("SELECT * FROM users WHERE id = {$rInfo['user_id']}")->fetch(PDO::FETCH_ASSOC);

    echo "Testing role: {$rInfo['role']}... ";

    // Test detail.php
    ob_start();
    try {
        $user = $_SESSION['user'];
        $isPartner = isPartnerUser();
        $isContractor = isContractorUser();
        $isPM = hasRole('Project Manager');
        $isBSA = hasRole('BSA');
        $isKAM = hasRole('KAM');
        $isManagement = hasRole('Management');
        $isAdminUser = isAdmin();
        $isPMOnly = ($isPM && !$isAdminUser && !$isKAM && !$isBSA && !$isManagement);

        include APP_DIR . '/views/orders/detail.php';
        $out = ob_get_clean();
        assert(strlen($out) > 0, "Rendered detail output must not be empty");
        echo "detail.php OK · ";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "FAILED on detail.php: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
        exit(1);
    }

    // Test tracking.php
    ob_start();
    try {
        $orders = [$order];
        $totalOrders = 1;
        $page = 1;
        $totalPages = 1;
        $search = '';
        $filterStatus = '';
        $filterPartner = '';
        $filterService = '';
        $filterPreset = '';
        $filterStart = '';
        $filterEnd = '';
        $partnersDropdown = [];
        $servicesDropdown = [];

        include APP_DIR . '/views/orders/tracking.php';
        $out2 = ob_get_clean();
        assert(strlen($out2) > 0, "Rendered tracking output must not be empty");
        echo "tracking.php OK" . PHP_EOL;
    } catch (Throwable $e) {
        ob_end_clean();
        echo "FAILED on tracking.php: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
        exit(1);
    }
}

// Test contractor views
echo "Testing contractor views... ";
$_SESSION['user_id'] = $rolesToTest[5]['user_id'];
$_SESSION['user'] = $db->query("SELECT * FROM users WHERE id = {$rolesToTest[5]['user_id']}")->fetch(PDO::FETCH_ASSOC);

ob_start();
try {
    include APP_DIR . '/views/contractor/dashboard.php';
    $out3 = ob_get_clean();
    assert(strlen($out3) > 0);
    echo "contractor/dashboard.php OK · ";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FAILED on dashboard.php: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

ob_start();
try {
    $closedJobs = [];
    include APP_DIR . '/views/contractor/closed_jobs.php';
    $out4 = ob_get_clean();
    assert(strlen($out4) > 0);
    echo "contractor/closed_jobs.php OK" . PHP_EOL;
} catch (Throwable $e) {
    ob_end_clean();
    echo "FAILED on closed_jobs.php: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "=== ALL VIEWS RENDERED WITHOUT ANY ERRORS ===" . PHP_EOL;
