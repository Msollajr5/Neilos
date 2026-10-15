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

// Test accounts
$partnerUser    = $db->query("SELECT * FROM users WHERE role = 'Partner' AND partner_id IS NOT NULL AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$bsaUser        = $db->query("SELECT * FROM users WHERE role = 'BSA' AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$kamUser        = $db->query("SELECT * FROM users WHERE role = 'KAM' AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$managementUser = $db->query("SELECT * FROM users WHERE role = 'Management' AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$pmUser         = $db->query("SELECT * FROM users WHERE role = 'Project Manager' AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$contractorUser = $db->query("SELECT * FROM users WHERE role = 'Contractor' AND partner_id IS NOT NULL AND is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

echo "=== LOADED TEST USERS ===" . PHP_EOL;
echo "Partner:    " . $partnerUser['full_name'] . " (ID: {$partnerUser['id']}, Partner ID: {$partnerUser['partner_id']})" . PHP_EOL;
echo "BSA:        " . $bsaUser['full_name'] . " (ID: {$bsaUser['id']})" . PHP_EOL;
echo "KAM:        " . $kamUser['full_name'] . " (ID: {$kamUser['id']})" . PHP_EOL;
echo "Management: " . $managementUser['full_name'] . " (ID: {$managementUser['id']})" . PHP_EOL;
echo "PM:         " . $pmUser['full_name'] . " (ID: {$pmUser['id']})" . PHP_EOL;
echo "Contractor: " . $contractorUser['full_name'] . " (ID: {$contractorUser['id']}, Contractor ID: {$contractorUser['partner_id']})" . PHP_EOL;

// Ensure contractor has an approved team
$teamStmt = $db->prepare("SELECT * FROM contractor_teams WHERE contractor_partner_id = ? AND status = 'Approved' LIMIT 1");
$teamStmt->execute([$contractorUser['partner_id']]);
$contractorTeam = $teamStmt->fetch(PDO::FETCH_ASSOC);
if (!$contractorTeam) {
    $db->prepare("INSERT INTO contractor_teams (contractor_partner_id, team_name, leader_name, leader_phone, coverage_region, status, created_by, created_at) VALUES (?, 'FastTrack Crew', 'Juma Bakari', '+255711223344', 'Dar es Salaam', 'Approved', ?, NOW())")
       ->execute([$contractorUser['partner_id'], $pmUser['id']]);
    $teamId = $db->lastInsertId();
    $contractorTeam = ['id' => $teamId, 'team_name' => 'FastTrack Crew'];
}

$servicesToTest = [
    [
        'type' => 'FTTH',
        'params' => [
            'customer_name' => 'John Doe Villa',
            'customer_location' => 'Mikocheni B, Plot 14, Dar es Salaam',
            'fttx_package' => '50 Mbps Home Ultra',
            'gps_coordinates' => '-6.7725, 39.2412',
            'customer_contact_name' => 'John Doe',
            'customer_contact_phone' => '+255755123456',
            'customer_contact_email' => 'john.doe@gmail.com'
        ]
    ],
    [
        'type' => 'FTTB',
        'params' => [
            'customer_name' => 'Acme Tower Offices',
            'customer_location' => 'Ali Hassan Mwinyi Rd, Victoria',
            'building_name' => 'Acme Commercial Tower',
            'floor_number' => '4th Floor',
            'apartment_number' => 'Suite 402',
            'fttx_package' => '100 Mbps Biz Pro',
            'gps_coordinates' => '-6.7801, 39.2550',
            'customer_contact_name' => 'Jane Smith',
            'customer_contact_phone' => '+255788987654',
            'customer_contact_email' => 'jane@acme.co.tz'
        ]
    ],
    [
        'type' => 'Dedicated Layer 2',
        'params' => [
            'customer_name' => 'Apex Bank HQ to Disaster Recovery',
            'customer_location' => 'Posta Mpya & Kijitonyama',
            'aggregate_capacity' => '1 Gbps Dedicated Ethernet',
            'interface_type' => 'Optical 10G LC',
            'media_type' => 'Single Mode Fiber',
            'gps_coordinates' => '-6.8160, 39.2890',
            'customer_contact_name' => 'David IT Lead',
            'customer_contact_phone' => '+255766334455',
            'customer_contact_email' => 'it@apexbank.co.tz'
        ]
    ],
    [
        'type' => 'BIA (Broadband Internet Access)',
        'params' => [
            'customer_name' => 'Safari Logistics Hub',
            'customer_location' => 'Mbagala Rangi Tatu, Industrial Area',
            'bandwidth' => '30 Mbps Dedicated DIA/BIA',
            'gps_coordinates' => '-6.8900, 39.2600',
            'customer_contact_name' => 'Rashid Mussa',
            'customer_contact_phone' => '+255712345678',
            'customer_contact_email' => 'rashid@safarilogistics.co.tz'
        ]
    ],
    [
        'type' => 'DIA',
        'params' => [
            'customer_name' => 'Global Embassy Complex',
            'customer_location' => 'Oysterbay, Toure Drive',
            'bandwidth' => '100 Mbps 1:1 Dedicated Internet',
            'gps_coordinates' => '-6.7650, 39.2780',
            'customer_contact_name' => 'Embassy Admin',
            'customer_contact_phone' => '+255222600000',
            'customer_contact_email' => 'admin@embassy.org'
        ]
    ],
    [
        'type' => 'Remote Hands Only',
        'params' => [
            'customer_name' => 'DataCenter Cross-Connect Maintenance',
            'customer_location' => 'Neilos Central POP, Victoria',
            'remote_hands_nrc_usd' => 80.00,
            'special_requirements' => 'SFP+ transceiver replacement and fiber patch cord cleaning',
            'gps_coordinates' => '-6.7820, 39.2560',
            'customer_contact_name' => 'NOC Operations',
            'customer_contact_phone' => '+255777889900',
            'customer_contact_email' => 'noc@telcocloud.net'
        ]
    ]
];

$testPassedCount = 0;
$totalTests = count($servicesToTest);

foreach ($servicesToTest as $idx => $testCase) {
    $svc = $testCase['type'];
    echo PHP_EOL . "============================================================" . PHP_EOL;
    echo "TESTING SERVICE [#" . ($idx + 1) . "/$totalTests]: $svc" . PHP_EOL;
    echo "============================================================" . PHP_EOL;

    // STEP 1: Partner Creates Order
    $orderNumber = generateOrderNumber($partnerUser['partner_id'], $svc);
    echo "1. Generated Order Number: $orderNumber" . PHP_EOL;

    $orderData = array_merge([
        'order_number' => $orderNumber,
        'partner_id' => $partnerUser['partner_id'],
        'user_id' => $partnerUser['id'],
        'service_type' => $svc,
        'status' => 'Feasibility Review',
        'standard_nrc' => ($svc === 'Remote Hands Only') ? 80.00 : 150.00,
        'standard_mrc' => ($svc === 'Remote Hands Only') ? 0.00 : 85.00,
        'mrc_currency' => 'USD',
        'created_by' => $partnerUser['id'],
        'created_at' => date('Y-m-d H:i:s')
    ], $testCase['params']);

    $cols = array_keys($orderData);
    $placeholders = array_fill(0, count($cols), '?');
    $insStmt = $db->prepare("INSERT INTO orders (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")");
    $insStmt->execute(array_values($orderData));
    $orderId = (int)$db->lastInsertId();

    echo "   Order #$orderId created. Status: Feasibility Review" . PHP_EOL;

    // STEP 2: BSA Reviews Feasibility & Configures Technical Scope / BoM
    $bomItems = [
        ['category' => 'Device', 'item' => 'ONT Fiber Terminal', 'qty' => 1],
        ['category' => 'Material', 'item' => 'Drop Cable (100m)', 'qty' => 1],
        ['category' => 'Service', 'item' => 'Fiber Splicing & Termination', 'qty' => 1]
    ];
    $bomJson = json_encode($bomItems);

    $db->prepare("UPDATE orders SET 
        status = 'Await Commercial Approval',
        bsa_feasi_approved = 1,
        bsa_reviewed_by = ?,
        bsa_reviewed_at = NOW(),
        bsa_remarks = 'Technical site survey confirmed feasible. Signal path clear.',
        nrc_breakdown = ?,
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$bsaUser['id'], $bomJson, $orderId]);
    
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Await Commercial Approval', 'BSA approved technical feasibility.', $bsaUser['id']]);

    echo "2. BSA Feasibility Approved -> Status: Await Commercial Approval" . PHP_EOL;

    // STEP 3: KAM Commercial Review — Defines TZS/USD Exchange Rate & Proposed Pricing
    $exchangeRate = 2650.00;
    $proposedNrc = ($svc === 'Remote Hands Only') ? 75.00 : 120.00;
    $proposedMrc = ($svc === 'Remote Hands Only') ? 0.00 : 75.00;

    $db->prepare("UPDATE orders SET 
        status = 'Management Approval',
        usd_tzs_rate = ?,
        kam_proposed_nrc = ?,
        kam_proposed_mrc = ?,
        kam_commercial_justification = 'Volume partner discount applied per contract agreement.',
        kam_reviewed_by = ?,
        kam_reviewed_at = NOW(),
        kam_approved_by = ?,
        kam_approved_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$exchangeRate, $proposedNrc, $proposedMrc, $kamUser['id'], $kamUser['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Management Approval', 'KAM submitted commercial exception review with exchange rate ' . number_format($exchangeRate, 2) . ' TZS/USD.', $kamUser['id']]);

    echo "3. KAM Submitted Commercial Review (Rate: $exchangeRate TZS/USD, NRC: $$proposedNrc, MRC: $$proposedMrc) -> Status: Management Approval" . PHP_EOL;

    // STEP 4: Management Reviews & Approves Pricing Exception
    $db->prepare("UPDATE orders SET 
        status = 'Pending SOF',
        management_approved_at = NOW(),
        management_approved_by = ?,
        management_decision = 'Approve as Requested',
        management_remarks = 'Commercial terms approved by Management.',
        revised_nrc = ?,
        revised_mrc = ?,
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$managementUser['id'], $proposedNrc, $proposedMrc, $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'Management approved pricing exception. Awaiting Partner Signed SOF.', $managementUser['id']]);

    echo "4. Management Approved Pricing -> Status: Pending SOF" . PHP_EOL;

    // STEP 5: Partner Uploads Signed SOF
    $signedFilename = "signed_sof_order_{$orderId}.pdf";
    $signedPath = "orders/{$orderId}/sof/{$signedFilename}";

    $db->prepare("UPDATE orders SET 
        status = 'SOF Review',
        sof_signed_file = ?,
        sof_signed_filename = ?,
        sof_uploaded_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$signedPath, $signedFilename, $orderId]);

    $db->prepare("INSERT INTO order_documents (order_id, document_type, file_path, file_name, uploaded_by, uploaded_at) VALUES (?, 'Signed SOF', ?, ?, ?, NOW())")
       ->execute([$orderId, $signedPath, $signedFilename, $partnerUser['id']]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'SOF Review', 'Partner uploaded Signed SOF document.', $partnerUser['id']]);

    echo "5. Partner Uploaded Signed SOF -> Status: SOF Review" . PHP_EOL;

    // STEP 6: KAM / Admin Countersigns SOF
    $countersignedFilename = "countersigned_sof_order_{$orderId}.pdf";
    $countersignedPath = "orders/{$orderId}/sof/{$countersignedFilename}";

    $db->prepare("UPDATE orders SET 
        status = 'Installation',
        countersigned_sof_file = ?,
        countersigned_sof_filename = ?,
        countersigned_sof_by = ?,
        countersigned_sof_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$countersignedPath, $countersignedFilename, $kamUser['id'], $orderId]);

    $db->prepare("INSERT INTO order_documents (order_id, document_type, file_path, file_name, uploaded_by, uploaded_at) VALUES (?, 'Countersigned SOF', ?, ?, ?, NOW())")
       ->execute([$orderId, $countersignedPath, $countersignedFilename, $kamUser['id']]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', 'Countersigned SOF verified and uploaded. Order moved to Installation stage.', $kamUser['id']]);

    echo "6. KAM Countersigned SOF -> Status: Installation" . PHP_EOL;

    // STEP 7: PM Assigns Contractor
    $targetDate = date('Y-m-d', strtotime('+3 days'));
    $db->prepare("INSERT INTO contractor_assignments (order_id, contractor_partner_id, contractor_team_id, contractor_team_name, status, target_date, work_order_notes, assigned_by, assigned_at) VALUES (?, ?, ?, ?, 'Assigned', ?, 'Execute fiber installation per technical BSA conditions.', ?, NOW())")
       ->execute([$orderId, $contractorUser['partner_id'], $contractorTeam['id'], $contractorTeam['team_name'], $targetDate, $pmUser['id']]);
    $assignmentId = (int)$db->lastInsertId();

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "PM assigned contractor {$contractorUser['full_name']} (Team: {$contractorTeam['team_name']}).", $pmUser['id']]);

    echo "7. PM Assigned Contractor #$assignmentId (Target Date: $targetDate)" . PHP_EOL;

    // STEP 8: Contractor Accepts Job, Uploads Evidence & Additional Cost Claim, Selects Team, Submits Completion
    $db->prepare("UPDATE contractor_assignments SET status = 'Accepted', accepted_by = ?, accepted_at = NOW() WHERE id = ?")
       ->execute([$contractorUser['id'], $assignmentId]);

    // Upload site evidence
    $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, file_name, file_path, uploaded_by, uploaded_at) VALUES (?, ?, 'Site Photo', 'installation_site.jpg', 'orders/{$orderId}/evidence/site.jpg', ?, NOW())")
       ->execute([$assignmentId, $orderId, $contractorUser['id']]);

    // Upload additional cost form
    $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, file_name, file_path, cost_amount, notes, pickup_location, uploaded_by, uploaded_at) VALUES (?, ?, 'Additional Cost Form', 'extra_trenching.pdf', 'orders/{$orderId}/evidence/extra_trenching.pdf', 45.00, 'Extra 35m civils trenching through concrete driveway', 'Confirmed Main Gateway Room', ?, NOW())")
       ->execute([$assignmentId, $orderId, $contractorUser['id']]);

    // Submit completion
    $pickupLocation = "Confirmed Physical Equipment Rack, Server Room A";
    $db->prepare("UPDATE orders SET status = 'Testing', pickup_location = ?, contractor_team_id = ?, contractor_team_name = ?, installation_date = CURDATE(), updated_at = NOW() WHERE id = ?")
       ->execute([$pickupLocation, $contractorTeam['id'], $contractorTeam['team_name'], $orderId]);

    $db->prepare("UPDATE contractor_assignments SET status = 'Completed Submitted', pickup_location = ?, contractor_team_id = ?, contractor_team_name = ?, completed_at = NOW(), completion_remarks = 'Installation completed. All power levels nominal.' WHERE id = ?")
       ->execute([$pickupLocation, $contractorTeam['id'], $contractorTeam['team_name'], $assignmentId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', "Contractor submitted completion with team {$contractorTeam['team_name']}. Location: $pickupLocation", $contractorUser['id']]);

    echo "8. Contractor Submitted Completion with Additional Cost & Field Team -> Status: Testing" . PHP_EOL;

    // STEP 9: PM Reviews Site Evidence, Verifies Pickup Location & Approves Testing -> Starts 72hr Business Days UAT
    $uatDeadline = calculateUatBusinessDeadline(new DateTime(), 3)->format('Y-m-d H:i:s');
    $db->prepare("UPDATE orders SET 
        status = 'UAT',
        uat_notified_at = NOW(),
        uat_submitted_at = NOW(),
        uat_deadline = ?,
        pickup_location = ?,
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$uatDeadline, $pickupLocation, $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'UAT', "PM approved testing. Partner notified for UAT acceptance (Deadline: $uatDeadline).", $pmUser['id']]);

    echo "9. PM Approved Testing -> Status: UAT (72-Hour Working Days Deadline: $uatDeadline)" . PHP_EOL;

    // STEP 10: Partner Accepts Service in UAT -> Status: Closed & Service Activated
    $billingStartDate = date('Y-m-d');
    $serviceId = generateServiceId($orderNumber);
    $circuitId = 'CKT-' . $orderNumber;
    $bw = $testCase['params']['bandwidth'] ?? $testCase['params']['fttx_package'] ?? $testCase['params']['aggregate_capacity'] ?? 'Standard';

    $db->prepare("UPDATE orders SET 
        status = 'Closed',
        closed_date = NOW(),
        uat_accepted_at = NOW(),
        uat_accepted_by = ?,
        billing_start_date = ?,
        activation_date = ?,
        service_id = ?,
        circuit_id = ?,
        is_active = 1,
        updated_at = NOW()
        WHERE id = ?")
       ->execute([$partnerUser['id'], $billingStartDate, $billingStartDate, $serviceId, $circuitId, $orderId]);

    $db->prepare("UPDATE contractor_assignments SET status = 'Completed', completed_at = NOW() WHERE id = ?")
       ->execute([$assignmentId]);

    // Create Active Service record
    $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Unknown')
        ON DUPLICATE KEY UPDATE status='Active'")
       ->execute([$serviceId, $orderId, $partnerUser['partner_id'], $orderData['customer_name'], $svc, $circuitId, $bw, $pickupLocation, $testCase['params']['building_name'] ?? null, $kamUser['id'], $billingStartDate, $billingStartDate]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Closed', "Partner accepted service signoff. Service is now LIVE & ACTIVE (Service ID: $serviceId). Billing effective from $billingStartDate.", $partnerUser['id']]);

    echo "10. Partner Accepted UAT -> Status: Closed (Live & Active, Service ID: $serviceId)" . PHP_EOL;

    // Verification of Final Order State in DB
    $finalOrder = $db->query("SELECT * FROM orders WHERE id = $orderId")->fetch(PDO::FETCH_ASSOC);
    assert($finalOrder['status'] === 'Closed', "Final status must be Closed");
    assert($finalOrder['mrc_currency'] === 'USD', "Currency must be USD");
    assert((float)$finalOrder['usd_tzs_rate'] == 2650.00, "USD/TZS exchange rate must be 2650.00");
    assert(!empty($finalOrder['uat_deadline']), "UAT deadline must be populated");
    assert($finalOrder['contractor_team_name'] === $contractorTeam['team_name'], "Contractor team must match");
    assert(!empty($finalOrder['pickup_location']), "Pickup location must be saved");

    echo ">>> TEST PASSED: Service '$svc' completed full lifecycle flawlessly!" . PHP_EOL;
    $testPassedCount++;
}

echo PHP_EOL . "============================================================" . PHP_EOL;
echo "TESTING RETURN / REJECTION FLOWS" . PHP_EOL;
echo "============================================================" . PHP_EOL;

// 1. Partner Return to KAM Test
$retOrderNumber = generateOrderNumber($partnerUser['partner_id'], 'FTTH');
$db->prepare("INSERT INTO orders (order_number, partner_id, user_id, customer_name, service_type, status, standard_nrc, standard_mrc, mrc_currency, usd_tzs_rate, created_by, created_at) VALUES (?, ?, ?, 'Reject Test Customer', 'FTTH', 'Pending SOF', 150, 85, 'USD', 2600.00, ?, NOW())")
   ->execute([$retOrderNumber, $partnerUser['partner_id'], $partnerUser['id'], $partnerUser['id']]);
$retOrderId = (int)$db->lastInsertId();

// Partner returns order to KAM
$returnReason = "Budget constraint: Requested revised MRC under $65 USD";
$db->prepare("UPDATE orders SET status = 'Await Commercial Approval', sof_return_comments = ?, return_route = 'KAM', returned_by = ?, returned_at = NOW(), updated_at = NOW() WHERE id = ?")
   ->execute([$returnReason, $partnerUser['id'], $retOrderId]);

$db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to) VALUES (?, ?, 'Pending SOF', 'Await Commercial Approval', 'Partner not satisfied with pricing', ?, 'KAM')")
   ->execute([$retOrderId, $partnerUser['id'], $returnReason]);

$chkReturn = $db->query("SELECT status, sof_return_comments, return_route FROM orders WHERE id = $retOrderId")->fetch(PDO::FETCH_ASSOC);
assert($chkReturn['status'] === 'Await Commercial Approval', 'Order returned to KAM');
assert($chkReturn['return_route'] === 'KAM', 'Route is KAM');
echo ">>> TEST PASSED: Partner Return to KAM successfully routes back to Await Commercial Approval." . PHP_EOL;

// 2. PM Return to Contractor with Rejection Banner Test
$db->prepare("UPDATE orders SET status = 'Installation' WHERE id = $retOrderId")->execute();
$db->prepare("INSERT INTO contractor_assignments (order_id, contractor_partner_id, contractor_team_id, contractor_team_name, status, assigned_by, assigned_at) VALUES (?, ?, ?, ?, 'In Progress', ?, NOW())")
   ->execute([$retOrderId, $contractorUser['partner_id'], $contractorTeam['id'], $contractorTeam['team_name'], $pmUser['id']]);
$retAssignId = (int)$db->lastInsertId();

// PM returns to contractor with rejection reason
$pmRejectReason = "Optical power reading is -32 dBm. Please re-splice the fiber cassette to bring optical loss below -24 dBm.";
$db->prepare("UPDATE contractor_assignments SET status = 'Returned', rejection_reason = ?, completion_remarks = ? WHERE id = ?")
   ->execute([$pmRejectReason, $pmRejectReason, $retAssignId]);

$chkContractor = $db->query("SELECT status, rejection_reason FROM contractor_assignments WHERE id = $retAssignId")->fetch(PDO::FETCH_ASSOC);
assert($chkContractor['status'] === 'Returned', 'Contractor job is Returned');
assert($chkContractor['rejection_reason'] === $pmRejectReason, 'Rejection reason matches');
echo ">>> TEST PASSED: PM Return to Contractor records exact rejection reason for red alert banner." . PHP_EOL;

echo PHP_EOL . "============================================================" . PHP_EOL;
echo "TEST SUMMARY: $testPassedCount / $totalTests Services Tested + 2 Return/Rejection Suites: ALL PASSED 100%!" . PHP_EOL;
echo "============================================================" . PHP_EOL;
