<?php // SLA Tracking & Analytics View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">SLA Tracking &amp; Delay Analytics</div>
    <div class="page-subtitle"><?= $total ?> order(s) evaluated &middot; Lifecycle stage duration and compliance monitoring</div>
  </div>
  <div class="page-header-actions">
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 'sla_tracking', 'action' => 'export'])) ?>" class="btn btn-secondary">
      <?= svgIcon('download') ?> Export CSV
    </a>
  </div>
</div>

<!-- SLA Executive Summary Cards -->
<div class="stats-grid mb-24" style="grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px">
  <!-- SLA Compliance Rate -->
  <div class="stat-card" style="border-left:4px solid <?= $slaOverallCompliance >= 90 ? '#10B981' : '#EF4444' ?>">
    <div class="stat-icon <?= $slaOverallCompliance >= 90 ? 'green' : 'red' ?>"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $slaOverallCompliance ?>%</div>
      <div class="stat-label">Overall SLA Compliance</div>
      <div class="stat-change <?= $totalBreached > 0 ? 'text-danger' : 'up' ?>">
        <?= $nonBreachedCount ?>/<?= $totalEvaluated ?> compliant orders
      </div>
    </div>
  </div>

  <!-- Breached Orders -->
  <div class="stat-card" style="border-left:4px solid #EF4444">
    <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalBreached ?></div>
      <div class="stat-label">Breached Orders</div>
      <div class="stat-change text-danger font-sm">
        <?= $totalStageBreaches ?> stage breach(es) across <?= $ordersWithStageBreaches ?> order(s)
      </div>
    </div>
  </div>

  <!-- Paused Orders -->
  <div class="stat-card" style="border-left:4px solid #F59E0B">
    <div class="stat-icon yellow"><?= svgIcon('pause-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalPaused ?></div>
      <div class="stat-label">Currently Paused</div>
      <div class="stat-change text-muted font-sm">
        <?= $totalPaused > 0 ? 'Active customer/access blockers' : 'No paused installations' ?>
      </div>
    </div>
  </div>

  <!-- Average Fulfillment Duration -->
  <div class="stat-card" style="border-left:4px solid #0F4C81">
    <div class="stat-icon blue"><?= svgIcon('calendar', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.15rem"><?= $avgDurationFormatted ?></div>
      <div class="stat-label">Avg. Duration</div>
      <div class="stat-change text-muted font-sm">
        <?= $completedCount > 0 ? 'Closed: ' . $slaAnalytics['avg_completed_formatted'] : 'Active: ' . $slaAnalytics['avg_active_formatted'] ?>
      </div>
    </div>
  </div>
</div>

<!-- Filter Card (Exact Contractors Appearance) -->
<div class="card mb-20" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="page" value="sla_tracking">

      <!-- Row 1: Period Presets + Custom Date Range -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
          <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin-right:4px">Period:</span>
          <?php
            $slaPresets = [
              'all'        => 'All Time',
              'today'      => 'Today',
              'this_month' => 'This Month',
              'last_month' => 'Last Month',
              'this_year'  => 'This Year',
            ];
            $curSlaPreset = $filterPreset ?: 'all';
          ?>
          <?php foreach ($slaPresets as $pKey => $pLabel): ?>
            <?php
              $pParams = $_GET;
              $pParams['page'] = 'sla_tracking';
              $pParams['preset'] = $pKey;
              unset($pParams['start_date'], $pParams['end_date'], $pParams['p']);
            ?>
            <a href="?<?= http_build_query($pParams) ?>"
               class="btn btn-sm <?= ($curSlaPreset === $pKey && !$filterStart && !$filterEnd) ? 'btn-primary' : 'btn-secondary' ?>"
               style="font-size:0.78rem;padding:4px 10px">
              <?= $pLabel ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Custom Date Range -->
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span style="font-size:0.82rem;color:var(--text-secondary)">Custom:</span>
          <input type="date" name="start_date" class="form-control" style="font-size:0.8rem;padding:4px 8px;width:auto;height:32px" value="<?= e($filterStart) ?>" placeholder="From">
          <span style="color:var(--text-muted)">to</span>
          <input type="date" name="end_date" class="form-control" style="font-size:0.8rem;padding:4px 8px;width:auto;height:32px" value="<?= e($filterEnd) ?>" placeholder="To">
        </div>
      </div>

      <!-- Row 2: Search & Status Filters -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <!-- Search Box -->
        <div style="flex:2;min-width:240px">
          <div class="search-box" style="width:100%">
            <?= svgIcon('search') ?>
            <input type="text" name="q" placeholder="Search order #, customer..." value="<?= e($filterSearch) ?>">
          </div>
        </div>

        <!-- Status Filter -->
        <div style="flex:1;min-width:140px">
          <select name="status" class="form-control" style="font-size:0.85rem">
            <option value="">All Statuses</option>
            <?php foreach ($allStatuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px">
          <?= svgIcon('filter') ?> Filter
        </button>
        <?php if ($filterStart || $filterEnd || ($filterPreset !== '' && $filterPreset !== 'all') || $filterSearch || $filterStatus): ?>
        <a href="<?= APP_URL ?>/?page=sla_tracking" class="btn btn-secondary btn-sm" style="height:38px;padding:0 14px;display:inline-flex;align-items:center">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card" style="width:100%;overflow:hidden">
  <div class="table-responsive" style="width:100%;max-height:650px;overflow-x:auto;overflow-y:auto;-webkit-overflow-scrolling:touch">
    <table class="data-table sla-table" style="width:100%;min-width:1470px;border-collapse:separate;border-spacing:0;table-layout:auto">
      <thead>
        <tr style="position:sticky;top:0;z-index:10;background:var(--surface-2);box-shadow:inset 0 -2px 0 var(--border)">
          <th style="min-width:140px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">ORDER #</th>
          <th style="min-width:160px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">CUSTOMER</th>
          <th style="min-width:180px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">SERVICE TYPE</th>
          <th style="min-width:130px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">STATUS</th>
          <th style="min-width:120px;text-align:center;padding:14px 16px;white-space:nowrap;font-weight:700">ORDER SLA</th>
          <th style="min-width:160px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">FEASIBILITY (24h)</th>
          <th style="min-width:160px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">COMMERCIAL (24h)</th>
          <th style="min-width:160px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">SOF (72h)</th>
          <th style="min-width:160px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">INSTALLATION (120h)</th>
          <th style="min-width:180px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700;color:var(--primary)">TOTAL DURATION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($slaData)): ?>
        <tr><td colspan="10" style="text-align:center;padding:24px"><div class="empty-state"><div class="empty-state-title">No orders found</div><div class="empty-state-text">No orders match the selected filters.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($slaData as $o): 
          $stgs = $o['stages'];
        ?>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap">
            <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['order_id'] ?>" class="font-600" style="color:var(--primary)">
              <?= e($o['order_number']) ?>
            </a>
          </td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($o['customer_name']) ?>">
            <?= e($o['customer_name']) ?>
          </td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap">
            <?php if (!empty($o['service_type_display'])): ?>
            <span class="badge badge-primary" style="font-size:.8rem;padding:6px 10px;white-space:nowrap;display:inline-block"><?= e($o['service_type_display']) ?></span>
            <?php else: ?>
            —
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap">
            <span class="badge <?= orderStatusClass($o['current_status']) ?>"><?= e($o['current_status']) ?></span>
          </td>
          <td style="padding:14px 16px;text-align:center;vertical-align:middle;white-space:nowrap">
            <?php if ($o['order_sla_status'] === 'Paused'): ?>
              <span class="badge badge-warning">⏸ Paused (<?= (float)$o['paused_hours'] ?>h)</span>
            <?php elseif ($o['order_sla_status'] === 'Breached'): ?>
              <span class="badge badge-danger">✕ Breached (<?= $o['stage_breach_count'] ?> delay)</span>
            <?php elseif ($o['order_sla_status'] === 'At Risk'): ?>
              <span class="badge badge-info">⚡ At Risk</span>
            <?php else: ?>
              <span class="badge badge-success">✓ Within SLA</span>
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm">
            <div style="font-weight:<?= $stgs['feasibility']['is_breached'] ? '700' : '500' ?>;color:<?= $stgs['feasibility']['is_breached'] ? '#EF4444' : 'inherit' ?>">
              <?= $stgs['feasibility']['formatted_duration'] ?>
            </div>
            <?php if ($stgs['feasibility']['is_breached']): ?>
            <div style="font-size:0.7rem;color:#EF4444">+<?= $stgs['feasibility']['formatted_delay'] ?> overdue</div>
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm">
            <div style="font-weight:<?= $stgs['commercial']['is_breached'] ? '700' : '500' ?>;color:<?= $stgs['commercial']['is_breached'] ? '#EF4444' : 'inherit' ?>">
              <?= $stgs['commercial']['formatted_duration'] ?>
            </div>
            <?php if ($stgs['commercial']['is_breached']): ?>
            <div style="font-size:0.7rem;color:#EF4444">+<?= $stgs['commercial']['formatted_delay'] ?> overdue</div>
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm">
            <div style="font-weight:<?= $stgs['sof']['is_breached'] ? '700' : '500' ?>;color:<?= $stgs['sof']['is_breached'] ? '#EF4444' : 'inherit' ?>">
              <?= $stgs['sof']['formatted_duration'] ?>
            </div>
            <?php if ($stgs['sof']['is_breached']): ?>
            <div style="font-size:0.7rem;color:#EF4444">+<?= $stgs['sof']['formatted_delay'] ?> overdue</div>
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm">
            <div style="font-weight:<?= $stgs['installation']['is_breached'] ? '700' : '500' ?>;color:<?= $stgs['installation']['is_breached'] ? '#EF4444' : 'inherit' ?>">
              <?= $stgs['installation']['formatted_duration'] ?>
            </div>
            <?php if ($stgs['installation']['is_breached']): ?>
            <div style="font-size:0.7rem;color:#EF4444">+<?= $stgs['installation']['formatted_delay'] ?> overdue</div>
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap;background:rgba(99,102,241,.04)">
            <span style="color:var(--primary);font-weight:700"><?= $o['formatted_total_duration'] ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;gap:8px;align-items:center;justify-content:center">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?page=sla_tracking&p=<?= $i ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?><?= $sortKey ? '&sort='.e($sortKey) : '' ?><?= $filterPreset ? '&preset='.e($filterPreset) : '' ?><?= $filterStart ? '&start_date='.e($filterStart) : '' ?><?= $filterEnd ? '&end_date='.e($filterEnd) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>


