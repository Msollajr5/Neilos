<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/format.php';
require_once __DIR__ . '/app/helpers/icons.php';
require_once __DIR__ . '/app/helpers/sla.php';

$cfg = getSlaStageConfig();
echo "Config Target Hours: " . $cfg['commercial']['target_hours'] . " hrs\n";
echo "Config Target Seconds: " . $cfg['commercial']['target_seconds'] . " s\n\n";

$testDurations = [
    '10 hours'            => 10 * 3600,
    '23 hours 59 minutes' => (23 * 3600) + (59 * 60),
    'Exactly 24 hours'    => 24 * 3600,
    '24 hours 1 minute'   => (24 * 3600) + 60,
    '30 hours'            => 30 * 3600,
];

$baseTs = strtotime('2026-08-01 08:00:00');

foreach ($testDurations as $label => $durationSec) {
    $order = [
        'id'            => 9999,
        'order_number'  => 'TEST-001',
        'status'        => 'Pending SOF',
        'created_at'    => date('Y-m-d H:i:s', $baseTs),
        'sla_paused'    => 0,
        'sla_paused_hours' => 0,
    ];

    $timeline = [
        ['id' => 1, 'order_id' => 9999, 'status' => 'Feasibility Review',        'changed_at' => date('Y-m-d H:i:s', $baseTs)],
        ['id' => 2, 'order_id' => 9999, 'status' => 'Await Commercial Approval', 'changed_at' => date('Y-m-d H:i:s', $baseTs + 3600)],
        ['id' => 3, 'order_id' => 9999, 'status' => 'Pending SOF',               'changed_at' => date('Y-m-d H:i:s', $baseTs + 3600 + $durationSec)],
    ];

    $res = computeOrderTimelineStages($order, $timeline);
    $comm = $res['stages']['commercial'];

    $expectedStatus = ($durationSec <= 24 * 3600) ? 'Within SLA' : 'Breached';
    $actualStatus   = $comm['status_label'];
    $isBreached     = $comm['is_breached'] ? 'YES' : 'NO';
    $delaySec       = $comm['delay_seconds'];
    $match          = ($actualStatus === $expectedStatus) ? 'PASS' : 'FAIL';

    echo "[$match] Duration: $label ($durationSec s)\n";
    echo "       Effective: " . $comm['effective_seconds'] . "s | Target: " . $comm['target_seconds'] . "s\n";
    echo "       Status: $actualStatus (Breached: $isBreached) | Delay: $delaySec s\n";
    echo "       Formatted: " . $comm['formatted_duration'] . " | Delay Formatted: " . $comm['formatted_delay'] . "\n\n";
}
