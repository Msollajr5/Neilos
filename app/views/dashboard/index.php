<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard View
// ============================================================

$activatedOrders = $orderStats['Closed'] ?? 0;
$submittedOrders = $totalOrders;

// Chart color mapping — dark blue & vibrant palette
$chartColors = [
  'Feasibility Review'       => ['bg' => '#F59E0B', 'border' => '#FCD34D'],
  'Await Commercial Approval' => ['bg' => '#8B5CF6', 'border' => '#A78BFA'],
  'Management Approval'       => ['bg' => '#EC4899', 'border' => '#F472B6'],
  'Pending SOF'              => ['bg' => '#3B82F6', 'border' => '#60A5FA'],
  'SOF Review'               => ['bg' => '#06B6D4', 'border' => '#22D3EE'],
  'Installation'             => ['bg' => '#0F4C81', 'border' => '#1D70B8'],
  'Testing'                  => ['bg' => '#14B8A6', 'border' => '#2DD4BF'],
  'UAT'                      => ['bg' => '#6366F1', 'border' => '#818CF8'],
  'Closed'                   => ['bg' => '#10B981', 'border' => '#34D399'],
  'Not Feasible'             => ['bg' => '#EF4444', 'border' => '#F87171'],
];

$pipelineSteps = ['Feasibility Review', 'Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed'];
$totalInPipeline = 0;
foreach ($pipelineSteps as $s) { $totalInPipeline += $orderStats[$s] ?? 0; }
$pipelineMax = max(1, max(array_map(fn($s) => $orderStats[$s] ?? 0, $pipelineSteps)));
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
    <div class="page-subtitle"><?= date('l, d F Y') ?> · Telecom &amp; ISP Operations Support System</div>
  </div>
  <div class="page-header-actions">
    <?php if (isKamUser() || hasPermission('partners.view')): ?>
    <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary">
      <?= svgIcon('building') ?> <?= isKamUser() ? 'My Partners' : 'Partners' ?>
    </a>
    <a href="<?= APP_URL ?>/?page=partners&action=create" class="btn btn-secondary">
      <?= svgIcon('plus') ?> New Partner
    </a>
    <?php endif; ?>
    <?php if (hasPermission('orders.create')): ?>
    <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary">
      <?= svgIcon('plus') ?> New Service Order
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Date Filter Bar (Exact Contractors Appearance) -->
<div class="card mb-24" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <input type="hidden" name="page" value="dashboard">
      
      <!-- Period Presets -->
      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin-right:4px">Period:</span>
        <?php
          $dashPresets = [
            'all'        => 'All Time',
            'today'      => 'Today',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year'  => 'This Year',
          ];
          $curDashPreset = $preset ?: 'all';
        ?>
        <?php foreach ($dashPresets as $pKey => $pLabel): ?>
          <a href="<?= APP_URL ?>/?page=dashboard&preset=<?= $pKey ?>"
             class="btn btn-sm <?= ($curDashPreset === $pKey && !$startDate && !$endDate) ? 'btn-primary' : 'btn-secondary' ?>"
             style="font-size:0.78rem;padding:4px 10px">
            <?= $pLabel ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Custom Date Inputs & Action Buttons -->
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:0.82rem;color:var(--text-secondary)">Custom:</span>
        <input type="date" name="start_date" class="form-control" style="font-size:0.8rem;padding:4px 8px;width:auto;height:32px" value="<?= e($startDate) ?>" placeholder="From">
        <span style="color:var(--text-muted)">to</span>
        <input type="date" name="end_date" class="form-control" style="font-size:0.8rem;padding:4px 8px;width:auto;height:32px" value="<?= e($endDate) ?>" placeholder="To">
        <button type="submit" class="btn btn-primary btn-sm" style="height:32px;padding:0 14px">
          <?= svgIcon('filter') ?> Apply Filter
        </button>
        <?php if ($startDate || $endDate || ($preset !== 'all' && $preset !== '')): ?>
        <a href="<?= APP_URL ?>/?page=dashboard" class="btn btn-secondary btn-sm" style="height:32px;padding:0 12px;display:inline-flex;align-items:center">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($pendingKycCount) && $pendingKycCount > 0): ?>
<!-- Finance & Admin Pending KYC Alert Banner -->
<div class="card mb-24" style="background: linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(217,119,6,0.04) 100%); border: 1px solid rgba(245,158,11,0.3); border-left: 5px solid #F59E0B; border-radius: var(--radius-md); padding: 16px 20px; box-shadow: var(--shadow-sm);">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
    <div style="display:flex; align-items:center; gap:14px;">
      <div style="width:42px; height:42px; border-radius:10px; background:rgba(245,158,11,0.18); color:#D97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
        <?= svgIcon('document', 22) ?>
      </div>
      <div>
        <div style="font-weight:700; font-size:1.05rem; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
          <span>Pending KYC Approvals</span>
          <span class="badge badge-warning" style="font-size:0.78rem; font-weight:700; padding:3px 8px;"><?= $pendingKycCount ?> Action Required</span>
        </div>
        <div style="font-size:0.84rem; color:var(--text-secondary); margin-top:2px;">
          There <?= $pendingKycCount === 1 ? 'is <strong>1 partner KYC application</strong>' : "are <strong>{$pendingKycCount} partner KYC applications</strong>" ?> submitted and awaiting compliance review and approval.
        </div>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
      <a href="<?= APP_URL ?>/?page=kyc" class="btn btn-warning btn-sm" style="color:#ffffff !important; font-weight:600; padding:8px 16px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(217,119,6,0.25);">
        <?= svgIcon('check', 14) ?> Review KYC Applications
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 6 Executive Summary Cards Grid (All Clickable Module Links) -->
<div class="stats-grid mb-24 dashboard-kpi-grid">
  <?php if (!isKamUser() && !isPartnerUser()): ?>
  <!-- Total Service Orders -->
  <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view all Service Orders in selected period">
    <div class="stat-icon blue"><?= svgIcon('list', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalOrders ?>"><?= $totalOrders ?></div>
      <div class="stat-label">Total Orders</div>
      <div class="stat-change up"><?= $totalOrders ?> recorded</div>
    </div>
  </a>

  <!-- Closed Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Closed<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Closed Orders in selected period">
    <div class="stat-icon green"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $completedOrders ?>"><?= $completedOrders ?></div>
      <div class="stat-label">Closed Orders</div>
      <div class="stat-change up">Billing Active</div>
    </div>
  </a>
  <?php endif; ?>

  <!-- Pending Actions -->
  <?php
    if (isKamUser()) {
        $pendingCommercial = $orderStats['Await Commercial Approval'] ?? 0;
        $pendingSOFReview  = $orderStats['SOF Review'] ?? 0;
        $pendingCount      = $pendingCommercial + $pendingSOFReview;
        $pendingChangeStr  = "$pendingCommercial Commercial · $pendingSOFReview SOF Countersign";
        $pendingLink       = APP_URL . "/?page=orders&status=" . urlencode('Await Commercial Approval') . $filterQueryStr;
    } else {
        $pendingCount      = $pendingBSA + $pendingUAT;
        $pendingChangeStr  = "$pendingBSA BSA · $pendingUAT UAT";
        $pendingLink       = APP_URL . "/?page=orders&status=" . urlencode('Feasibility Review') . $filterQueryStr;
    }
  ?>
  <a href="<?= $pendingLink ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Pending Actions">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></div>
      <div class="stat-label">Pending Actions</div>
      <div class="stat-change"><?= $pendingChangeStr ?></div>
    </div>
  </a>

  <!-- In-Progress Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Installation<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view In-Progress Orders">
    <div class="stat-icon blue"><?= svgIcon('project', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $inProgressOrders ?>"><?= $inProgressOrders ?></div>
      <div class="stat-label">In-Progress Orders</div>
      <div class="stat-change up">Fulfillment active</div>
    </div>
  </a>

  <!-- Not Feasible Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Not+Feasible<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Not Feasible Orders">
    <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $cancelledOrders ?>"><?= $cancelledOrders ?></div>
      <div class="stat-label">Not Feasible</div>
      <div class="stat-change text-muted"><?= $cancelledOrders ?> order(s)</div>
    </div>
  </a>

  <!-- Partners -->
  <?php if (canViewDashboardPartnersCard()): ?>
  <a href="<?= APP_URL ?>/?page=partners" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Partners">
    <div class="stat-icon navy"><?= svgIcon('building', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalPartners ?>"><?= $totalPartners ?></div>
      <div class="stat-label">Registered Partners</div>
      <div class="stat-change up">Active partners</div>
    </div>
  </a>
  <?php endif; ?>

  <?php if (canViewDashboardFinancials()): ?>
  <!-- USD Exchange Rate Card inside KPI Grid -->
  <div class="stat-card dashboard-usd-rate-card" style="grid-column: span 2; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid #0F4C81; background: var(--surface-1); padding: 16px 20px;">
    <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
      <div class="stat-icon blue" style="background: rgba(15,76,129,0.1); color: #0F4C81; font-weight: 700; font-size: 1.35rem; flex-shrink: 0; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
        $
      </div>
      <div style="min-width: 0;">
        <div class="stat-label" style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.76rem; color: var(--text-secondary);">
          USD Exchange Rate
        </div>
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-top: 1px; white-space: nowrap;">
          <?php if (!empty($usdRateData['has_rate']) && $usdRateData['rate'] > 0): ?>
            1 USD = <span style="color: #0F4C81;">TZS <?= money($usdRateData['rate']) ?></span>
          <?php else: ?>
            <span class="text-muted" style="font-size: 1rem;">USD Rate Not Set</span>
          <?php endif; ?>
        </div>
        <div class="stat-change text-muted" style="font-size: 0.72rem; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          <?= !empty($usdRateData['updated_at']) ? 'Updated: ' . fmtDate($usdRateData['updated_at'], 'd M Y, H:i') : 'System Default' ?>
          <?php if (!empty($usdRateData['updated_by_name'])): ?>
            · By <?= e($usdRateData['updated_by_name']) ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if (canManageUsdExchangeRate()): ?>
    <div style="flex-shrink: 0; margin-left: 10px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="openUsdRateModal()" style="font-size: 0.78rem; padding: 5px 12px; display: inline-flex; align-items: center; gap: 4px; border-radius: 6px;">
        <?= svgIcon('edit', 13) ?> Edit Rate
      </button>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (canManageUsdExchangeRate()): ?>
<!-- Edit USD Exchange Rate Modal -->
<div id="usdRateModal" class="modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:16px;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px)">
  <div class="card" style="width:100%;max-width:440px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);overflow:hidden;animation:fadeIn 0.2s ease">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--surface-2)">
      <div style="font-size:1.05rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <?= svgIcon('dollar', 18) ?> USD Exchange Rate
      </div>
      <button type="button" onclick="closeUsdRateModal()" style="background:none;border:none;font-size:1.3rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=dashboard&action=update_usd_rate<?= $filterQueryStr ?>" onsubmit="return validateUsdRateForm(this)">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="card-body" style="padding:20px">
        <div class="form-group mb-16">
          <label class="form-label" style="font-weight:600;margin-bottom:6px;display:block">USD Exchange Rate (1 USD = ? TZS) <span class="text-danger">*</span></label>
          <div class="input-group" style="display:flex;align-items:center">
            <span class="input-addon" style="background:var(--surface-2);border:1px solid var(--border);border-right:none;padding:8px 12px;font-size:0.88rem;color:var(--text-secondary);border-radius:var(--radius-sm) 0 0 var(--radius-sm);font-weight:600">TZS</span>
            <input type="number" step="0.01" min="0.01" max="1000000" name="usd_exchange_rate" id="usdExchangeRateInput" class="form-control" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0;font-size:1rem;font-weight:600" value="<?= !empty($usdRateData['rate']) ? $usdRateData['rate'] : '2650.00' ?>" placeholder="e.g. 2650.00" required>
          </div>
          <div class="form-hint" style="font-size:0.78rem;color:var(--text-muted);margin-top:6px">
            Enter the global exchange rate in TZS per 1 USD. Used to automatically calculate USD equivalents across all dashboard revenue metrics.
          </div>
        </div>
        <div id="usdRateErrorMsg" style="display:none;padding:8px 12px;background:#fee2e2;border:1px solid #ef4444;border-radius:6px;color:#b91c1c;font-size:0.82rem;margin-bottom:12px"></div>
      </div>
      <div class="card-footer" style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--border);background:var(--surface-1)">
        <button type="button" class="btn btn-secondary" onclick="closeUsdRateModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Save Rate</button>
      </div>
    </form>
  </div>
</div>

<script>
function openUsdRateModal() {
  var modal = document.getElementById('usdRateModal');
  if (modal) {
    modal.style.display = 'flex';
    var inp = document.getElementById('usdExchangeRateInput');
    if (inp) inp.focus();
  }
}
function closeUsdRateModal() {
  var modal = document.getElementById('usdRateModal');
  if (modal) modal.style.display = 'none';
}
function validateUsdRateForm(form) {
  var val = parseFloat(form.usd_exchange_rate.value);
  var err = document.getElementById('usdRateErrorMsg');
  if (isNaN(val) || val <= 0) {
    if (err) {
      err.textContent = 'Please enter a valid numeric exchange rate greater than 0.';
      err.style.display = 'block';
    }
    return false;
  }
  if (err) err.style.display = 'none';
  return true;
}
</script>
<?php endif; ?>

<?php if (canViewDashboardFinancials()): ?>
<!-- ============================================================ -->
<!-- New gross add revenue Section (Single Source of Truth)        -->
<!-- ============================================================ -->
<div class="card mb-24" id="revenue-analysis-section">
  <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px;">
    <div>
      <div class="card-title" style="font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
        <?= svgIcon('dollar-sign', 22) ?> New gross add revenue
      </div>
      <div class="card-subtitle">Calculated strictly from Closed + Billing Active Orders in the selected period (status = Closed and billing start date reached).</div>
    </div>
  </div>
  <div class="card-body" style="padding: 20px;">
    
    <!-- 1. Revenue Summary KPI Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
      <!-- NRC Revenue -->
      <div class="stat-card" style="border-left: 4px solid #3B82F6; background: var(--surface-1);">
        <div class="stat-icon blue"><?= svgIcon('dollar', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem;">USD <?= money($totalNrcRevenueUsd) ?></div>
          <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-top: 2px;">TZS <?= money($totalNrcRevenue) ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary); margin-top: 4px;">NRC Revenue</div>
          <div class="stat-change text-muted font-sm">One-time revenue (Incl. 18% VAT)</div>
        </div>
      </div>

      <!-- MRC Revenue -->
      <div class="stat-card" style="border-left: 4px solid #10B981; background: var(--surface-1);">
        <div class="stat-icon green"><?= svgIcon('dollar-sign', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem;">USD <?= money($totalMrcRevenueUsd) ?></div>
          <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-top: 2px;">TZS <?= money($totalMrcRevenue) ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary); margin-top: 4px;">MRC Revenue</div>
          <div class="stat-change text-muted font-sm">Recurring monthly (Incl. 18% VAT)</div>
        </div>
      </div>

      <!-- Total Revenue -->
      <div class="stat-card" style="border-left: 4px solid #0F4C81; background: var(--surface-1);">
        <div class="stat-icon navy"><?= svgIcon('chart', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem; color: #0F4C81;">USD <?= money($totalCombinedRevenueUsd) ?></div>
          <div style="font-size: 0.88rem; font-weight: 600; color: #0F4C81; margin-top: 2px;">TZS <?= money($totalCombinedRevenue) ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary); margin-top: 4px;">Total Revenue</div>
          <div class="stat-change up">Combined NRC + MRC (Incl. 18% VAT)</div>
        </div>
      </div>
    </div>

    <!-- 2. Revenue Breakdown by Service Type -->
    <div style="margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary);">Revenue by Service Type</span>
          <span class="badge badge-secondary" style="font-size: 0.75rem; padding: 4px 8px;"><?= count($serviceTypeRevenue) ?> Active Service <?= count($serviceTypeRevenue) === 1 ? 'Type' : 'Types' ?></span>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-secondary);">
          Total Active Billing Orders: <strong style="color: var(--text-primary);"><?= (int)$billingActiveCount ?></strong>
        </div>
      </div>

      <?php if (empty($serviceTypeRevenue)): ?>
        <div style="padding: 24px; text-align: center; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px solid var(--border); color: var(--text-muted); font-size: 0.88rem;">
          No billing active orders recorded for the selected period.
        </div>
      <?php else: ?>
        <div class="table-responsive" style="overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius-sm);">
          <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
            <thead>
              <tr style="background: var(--surface-2); border-bottom: 1px solid var(--border); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">
                <th style="padding: 10px 14px; text-align: left;">Service Type</th>
                <th style="padding: 10px 14px; text-align: center;">Active Orders</th>
                <th style="padding: 10px 14px; text-align: right;">NRC Revenue</th>
                <th style="padding: 10px 14px; text-align: right;">MRC Revenue</th>
                <th style="padding: 10px 14px; text-align: right;">Total Revenue</th>
                <th style="padding: 10px 14px; text-align: left; min-width: 140px;">Revenue Share</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($serviceTypeRevenue as $stName => $stData): ?>
                <?php
                  $sharePct = $totalCombinedRevenue > 0 ? round(($stData['total_revenue'] / $totalCombinedRevenue) * 100, 1) : 0;
                ?>
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;">
                  <td style="padding: 12px 14px; font-weight: 600; color: var(--text-primary);">
                    <span class="badge badge-primary" style="font-size: 0.8rem; padding: 4px 8px;"><?= e($stName) ?></span>
                  </td>
                  <td style="padding: 12px 14px; text-align: center; font-weight: 600; color: var(--text-primary);">
                    <?= (int)$stData['order_count'] ?>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; color: #2563EB; font-weight: 600; font-size: 0.9rem;">
                    <div>USD <?= money($stData['nrc_revenue_usd']) ?></div>
                    <div style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted); margin-top: 2px;">TZS <?= money($stData['nrc_revenue']) ?></div>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; color: #059669; font-weight: 600; font-size: 0.9rem;">
                    <div>USD <?= money($stData['mrc_revenue_usd']) ?></div>
                    <div style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted); margin-top: 2px;">TZS <?= money($stData['mrc_revenue']) ?></div>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--text-primary); font-size: 0.92rem;">
                    <div>USD <?= money($stData['total_revenue_usd']) ?></div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-top: 2px;">TZS <?= money($stData['total_revenue']) ?></div>
                  </td>
                  <td style="padding: 12px 14px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="flex: 1; background: var(--surface-3); border-radius: 4px; height: 6px; overflow: hidden; min-width: 60px;">
                        <div style="height: 100%; width: <?= $sharePct ?>%; background: var(--accent);"></div>
                      </div>
                      <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); min-width: 40px; text-align: right;"><?= $sharePct ?>%</span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ============================================================ -->
<!-- Projected Total Revenue Section (Single Source of Truth)     -->
<!-- ============================================================ -->
<div class="card mb-24" id="projected-revenue-section">
  <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px;">
    <div>
      <div class="card-title" style="font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
        <?= svgIcon('chart', 22) ?> Projected Total Revenue
      </div>
      <div class="card-subtitle">Recurring revenue projected from active closed orders based on billing date and Service Term.</div>
    </div>
  </div>
  <div class="card-body" style="padding: 20px;">
    
    <!-- 1. Projected Revenue Summary KPI Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
      <!-- Projected Total Revenue -->
      <div class="stat-card" style="border-left: 4px solid #0F4C81; background: var(--surface-1);">
        <div class="stat-icon navy"><?= svgIcon('chart', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem; color: #0F4C81;">USD <?= money($projectedTotalRevenueUsd) ?></div>
          <div style="font-size: 0.88rem; font-weight: 600; color: #0F4C81; margin-top: 2px;">TZS <?= money($projectedRevenueData['projected_total_revenue']) ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary); margin-top: 4px;">Projected Total Revenue</div>
          <div class="stat-change up font-sm">Accumulated recurring to date (<?= e($projectedRevenueData['current_month_label']) ?>)</div>
        </div>
      </div>

      <!-- Current Monthly MRC -->
      <div class="stat-card" style="border-left: 4px solid #10B981; background: var(--surface-1);">
        <div class="stat-icon green"><?= svgIcon('dollar-sign', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem; color: #059669;">USD <?= money($currentMonthlyMrcUsd) ?></div>
          <div style="font-size: 0.88rem; font-weight: 600; color: #059669; margin-top: 2px;">TZS <?= money($projectedRevenueData['current_monthly_mrc']) ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary); margin-top: 4px;">Current Monthly MRC</div>
          <div class="stat-change text-muted font-sm">Active monthly run-rate (Incl. 18% VAT)</div>
        </div>
      </div>

      <!-- Active Billed Orders -->
      <div class="stat-card" style="border-left: 4px solid #8B5CF6; background: var(--surface-1);">
        <div class="stat-icon purple"><?= svgIcon('list', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem; color: #7C3AED;"><?= (int)$projectedRevenueData['eligible_order_count'] ?></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary);">Active Billed Orders</div>
          <div class="stat-change text-muted font-sm">Closed + active with billing date</div>
        </div>
      </div>

      <!-- Total Billable Months -->
      <div class="stat-card" style="border-left: 4px solid #F59E0B; background: var(--surface-1);">
        <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size: 1.45rem; color: #D97706;"><?= (int)$projectedRevenueData['total_billable_months'] ?> <span style="font-size: 0.95rem; font-weight: 500;">Months</span></div>
          <div class="stat-label" style="font-weight: 600; color: var(--text-primary);">Total Billable Months</div>
          <div class="stat-change text-muted font-sm">Capped by committed Service Term</div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php endif; // canViewDashboardFinancials ?>

<!-- ============================================================ -->
<!-- SLA Tracking & Delay Analytics Section (Single Filtered State) -->
<!-- ============================================================ -->
<div class="card mb-24" id="sla-analytics-section" style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
  <div class="card-header" style="border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--surface-1)">
    <div>
      <div class="card-title" style="font-size:1.25rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <?= svgIcon('clock', 22) ?> SLA Tracking &amp; Delay Analytics
      </div>
      <div class="card-subtitle" style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">
        <?= $slaTotalEvaluated ?> order(s) evaluated &middot; Lifecycle stage duration, compliance monitoring, and bottleneck analysis for the active filter period
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <a href="<?= APP_URL ?>/?page=sla_tracking<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
        <?= svgIcon('download', 14) ?> Full SLA Tracking &amp; Export
      </a>
    </div>
  </div>

  <div class="card-body" style="padding:20px">
    
    <!-- 1. Executive SLA KPI Cards -->
    <div class="stats-grid mb-24" style="grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px">
      <!-- SLA Compliance Rate -->
      <div class="stat-card" style="border-left:4px solid <?= $slaCompliancePct >= 90 ? '#10B981' : '#EF4444' ?>;background:var(--surface-1)">
        <div class="stat-icon <?= $slaCompliancePct >= 90 ? 'green' : 'red' ?>"><?= svgIcon('clock', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaCompliancePct ?>%</div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Overall SLA Compliance</div>
          <div class="stat-change <?= $slaBreachedCount > 0 ? 'text-danger' : 'up' ?>">
            <?= $slaNonBreachedCount ?>/<?= $slaTotalEvaluated ?> compliant orders
          </div>
        </div>
      </div>

      <!-- Breached Orders (Actionable Drilldown) -->
      <a href="<?= APP_URL ?>/?page=orders&sla=breached<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit;border-left:4px solid #EF4444;background:var(--surface-1);cursor:pointer" title="Click to view breached orders">
        <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaBreachedCount ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Breached Orders</div>
          <div class="stat-change text-danger font-sm">
            <?= $slaTotalStageBreaches ?> stage breach(es) across <?= $slaOrdersWithBreaches ?> order(s)
          </div>
        </div>
      </a>

      <!-- Paused Orders (Actionable Drilldown) -->
      <a href="<?= APP_URL ?>/?page=orders&sla=paused<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit;border-left:4px solid #F59E0B;background:var(--surface-1);cursor:pointer" title="Click to view paused orders">
        <div class="stat-icon yellow"><?= svgIcon('pause-circle', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaPausedCount ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Currently Paused</div>
          <div class="stat-change text-muted font-sm">
            <?= $slaPausedCount > 0 ? 'Active customer/access blockers →' : 'No paused installations' ?>
          </div>
        </div>
      </a>

      <!-- Average Lifecycle Duration -->
      <div class="stat-card" style="border-left:4px solid #0F4C81;background:var(--surface-1)">
        <div class="stat-icon blue"><?= svgIcon('calendar', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size:1.15rem"><?= $slaAvgDuration ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Avg. Duration</div>
          <div class="stat-change text-muted font-sm">
            <?= $slaCompletedCount > 0 ? 'Closed: ' . $slaAvgCompletedDuration : 'Active: ' . $slaAvgActiveDuration ?>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Two-Column SLA Detailed Breakdown: Stages & Bottlenecks -->
    <div class="dashboard-two-col-grid">
      
      <!-- Stage-by-Stage Performance Table -->
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
            <?= svgIcon('activity', 16) ?> Performance by Lifecycle Stage
          </div>
          <span class="badge badge-info" style="font-size:0.75rem">Target Matrix</span>
        </div>

        <?php if ($slaTotalEvaluated === 0): ?>
        <div class="empty-state p-16" style="padding:24px 12px;text-align:center">
          <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary);font-size:0.9rem">No SLA orders in selected period</div>
          <div class="empty-state-text" style="font-size:0.8rem;color:var(--text-muted)">Stage metrics will appear once orders are placed within this date range.</div>
        </div>
        <?php else: ?>
        <div class="table-responsive" style="overflow-x:auto">
          <table class="table" style="width:100%;border-collapse:collapse;font-size:0.82rem">
            <thead>
              <tr style="border-bottom:1px solid var(--border);color:var(--text-secondary);text-align:left">
                <th style="padding:8px 6px;font-weight:600">STAGE (TARGET)</th>
                <th style="padding:8px 6px;text-align:center;font-weight:600">EVAL</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">AVG DURATION</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">AVG DELAY</th>
                <th style="padding:8px 6px;text-align:center;font-weight:600">SLA STATUS</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($slaStages as $stg): ?>
              <tr style="border-bottom:1px solid rgba(0,0,0,0.04)">
                <td style="padding:10px 6px;vertical-align:middle">
                  <div style="font-weight:600;color:var(--text-primary)"><?= e($stg['name']) ?></div>
                  <div style="font-size:0.72rem;color:var(--text-muted)">Target: <?= $stg['target_hours'] ?> hrs</div>
                </td>
                <td style="padding:10px 6px;text-align:center;vertical-align:middle">
                  <span style="font-weight:600"><?= $stg['evaluated'] ?></span>
                  <?php if ($stg['active'] > 0): ?>
                  <span class="badge badge-warning" style="font-size:0.68rem;padding:2px 4px" title="Active in stage"><?= $stg['active'] ?> act</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle;font-weight:500">
                  <?= $stg['avg_formatted'] ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle;font-weight:500;color:<?= $stg['breached'] > 0 ? '#EF4444' : 'var(--text-muted)' ?>">
                  <?= $stg['breached'] > 0 ? '+' . $stg['avg_delay_formatted'] : '—' ?>
                </td>
                <td style="padding:10px 6px;text-align:center;vertical-align:middle">
                  <?php if ($stg['breached'] > 0): ?>
                  <span class="badge badge-danger" style="font-size:0.72rem"><?= $stg['breached'] ?> breached</span>
                  <?php elseif ($stg['evaluated'] > 0): ?>
                  <span class="badge badge-success" style="font-size:0.72rem">100% OK</span>
                  <?php else: ?>
                  <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle">
                  <?php 
                    $stageFilterStatus = match($stg['key']) {
                      'feasibility'  => 'Feasibility Review',
                      'commercial'   => 'Await Commercial Approval',
                      'sof'          => 'Pending SOF',
                      'installation' => 'Installation',
                      'testing_uat'  => 'UAT',
                      default        => ''
                    };
                  ?>
                  <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($stageFilterStatus) ?><?= $filterQueryStr ?>" class="btn btn-outline btn-xs" style="padding:2px 8px;font-size:0.75rem" title="View orders in <?= e($stg['name']) ?>">
                    View →
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- SLA Bottlenecks & Distribution Highlight -->
      <div style="display:flex;flex-direction:column;gap:16px">
        
        <!-- Bottlenecks Card -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:16px;flex:1">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
              <?= svgIcon('alert-triangle', 16) ?> SLA Bottlenecks &amp; Delay Risks
            </div>
            <span class="badge <?= $slaTotalStageBreaches > 0 ? 'badge-danger' : 'badge-success' ?>" style="font-size:0.75rem">
              <?= $slaTotalStageBreaches > 0 ? $slaTotalStageBreaches . ' Stage Delay(s)' : 'On Track' ?>
            </span>
          </div>

          <?php 
          $hasBottlenecks = false;
          foreach ($slaBottlenecks as $bn) {
              if ($bn['breached'] > 0 || ($bn['active'] > 0 && $bn['avg_sec'] > ($bn['target_hours'] * 3600))) {
                  $hasBottlenecks = true;
                  break;
              }
          }
          ?>

          <?php if (!$hasBottlenecks): ?>
          <div style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:16px;display:flex;align-items:flex-start;gap:12px">
            <div style="color:#10B981;font-size:1.3rem;line-height:1">✓</div>
            <div>
              <div style="font-weight:600;color:#065F46;font-size:0.88rem">No SLA breaches in selected period</div>
              <div style="font-size:0.8rem;color:#047857;margin-top:2px">All fulfillment stages are operating within targeted response and delivery times.</div>
            </div>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($slaBottlenecks as $bn): 
              if ($bn['breached'] === 0 && ($bn['active'] === 0 || $bn['avg_sec'] <= ($bn['target_hours'] * 3600))) continue;
              $stageFilterStatus = match($bn['key']) {
                'feasibility'  => 'Feasibility Review',
                'commercial'   => 'Await Commercial Approval',
                'sof'          => 'Pending SOF',
                'installation' => 'Installation',
                'testing_uat'  => 'UAT',
                default        => ''
              };
            ?>
            <div style="background:var(--surface-1);border:1px solid <?= $bn['breached'] > 0 ? '#EF4444' : '#F59E0B' ?>;border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between">
              <div>
                <div style="font-weight:600;font-size:0.85rem;color:var(--text-primary)"><?= e($bn['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)">
                  Avg Delay: <strong style="color:<?= $bn['breached'] > 0 ? '#EF4444' : '#D97706' ?>"><?= $bn['breached'] > 0 ? '+' . $bn['avg_delay_formatted'] : 'On Track' ?></strong> &middot; Target: <?= $bn['target_hours'] ?>h
                </div>
              </div>
              <div style="text-align:right">
                <?php if ($bn['breached'] > 0): ?>
                <span class="badge badge-danger" style="font-size:0.75rem"><?= $bn['breached'] ?>/<?= $bn['evaluated'] ?> Breached (<?= $bn['breach_pct'] ?>%)</span>
                <?php else: ?>
                <span class="badge badge-warning" style="font-size:0.75rem">Attention</span>
                <?php endif; ?>
                <div style="margin-top:4px">
                  <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($stageFilterStatus) ?><?= $filterQueryStr ?>" style="font-size:0.72rem;color:var(--primary);font-weight:600;text-decoration:none">
                    Investigate →
                  </a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- SLA Status Mutually Exclusive Distribution Breakdown -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:14px 16px">
          <div style="font-weight:600;font-size:0.85rem;color:var(--text-secondary);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
            <span>SLA Status Distribution (Mutually Exclusive)</span>
            <span>Total: <?= $slaTotalEvaluated ?></span>
          </div>
          
          <div style="display:flex;height:10px;border-radius:5px;overflow:hidden;background:#E5E7EB;margin-bottom:10px">
            <?php if ($slaTotalEvaluated > 0): 
              $pWithin = round(($slaWithinCount / $slaTotalEvaluated) * 100);
              $pAtRisk = round(($slaAtRiskCount / $slaTotalEvaluated) * 100);
              $pBreach = round(($slaBreachedCount / $slaTotalEvaluated) * 100);
              $pPaused = round(($slaPausedCount / $slaTotalEvaluated) * 100);
            ?>
              <div style="width:<?= $pWithin ?>%;background:#10B981" title="<?= $slaWithinCount ?> Within SLA (<?= $pWithin ?>%)"></div>
              <div style="width:<?= $pAtRisk ?>%;background:#3B82F6" title="<?= $slaAtRiskCount ?> At Risk (<?= $pAtRisk ?>%)"></div>
              <div style="width:<?= $pBreach ?>%;background:#EF4444" title="<?= $slaBreachedCount ?> Breached (<?= $pBreach ?>%)"></div>
              <div style="width:<?= $pPaused ?>%;background:#F59E0B" title="<?= $slaPausedCount ?> Paused (<?= $pPaused ?>%)"></div>
            <?php else: ?>
              <div style="width:100%;background:#D1D5DB"></div>
            <?php endif; ?>
          </div>

          <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-secondary);flex-wrap:wrap;gap:8px">
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block"></span>
              Within SLA: <strong style="color:var(--text-primary)"><?= $slaWithinCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#3B82F6;display:inline-block"></span>
              At Risk: <strong style="color:var(--text-primary)"><?= $slaAtRiskCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#EF4444;display:inline-block"></span>
              Breached: <strong style="color:var(--text-primary)"><?= $slaBreachedCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
              Paused: <strong style="color:var(--text-primary)"><?= $slaPausedCount ?></strong>
            </span>
          </div>

          <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border);display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-muted)">
            <span>Active In-Flight: <strong style="color:var(--text-primary)"><?= $slaActiveCount ?></strong></span>
            <span>Completed / Closed: <strong style="color:var(--text-primary)"><?= $slaCompletedCount ?></strong></span>
            <span>Total Stage Breaches: <strong style="color:<?= $slaTotalStageBreaches > 0 ? '#EF4444' : 'var(--text-primary)' ?>"><?= $slaTotalStageBreaches ?></strong></span>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>

<!-- Full-Width Order Fulfillment Pipeline (Left-to-Right Horizontal Flow) -->
<div class="card mb-24" id="order-fulfillment-pipeline-section" style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
  <div class="card-header" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--surface-1)">
    <div>
      <div class="card-title" style="font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <?= svgIcon('activity', 20) ?> Order Fulfillment Pipeline
      </div>
      <div class="card-subtitle" style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">
        <?= $totalInPipeline ?> total order(s) progressing across lifecycle stages from initial survey to billing activation
      </div>
    </div>
    <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px;font-weight:600">
      View Orders <?= svgIcon('chevron-right', 13) ?>
    </a>
  </div>
  <div class="card-body" style="padding:20px">
    <div class="horizontal-pipeline-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(135px, 1fr));gap:12px">
      <?php 
      $stepIndex = 0;
      foreach ($pipelineSteps as $st):
        $stepIndex++;
        $cnt = $orderStats[$st] ?? 0;
        $pct = $pipelineMax > 0 ? round(($cnt / $pipelineMax) * 100) : 0;
        $c = $chartColors[$st] ?? ['bg' => '#0F4C81', 'border' => '#0A365C'];
        $hasOrders = $cnt > 0;
        
        $stageShortName = match($st) {
          'Feasibility Review'        => 'Feasibility',
          'Await Commercial Approval' => 'Commercial',
          'Management Approval'       => 'Management',
          'Pending SOF'               => 'Pending SOF',
          'SOF Review'                => 'SOF Review',
          'Installation'              => 'Installation',
          'Testing'                   => 'Testing',
          'UAT'                       => 'UAT Sign-off',
          'Closed'                    => 'Closed / Active',
          default                     => $st
        };
      ?>
      <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($st) ?><?= $filterQueryStr ?>" 
         class="horizontal-pipeline-card" 
         style="text-decoration:none;color:inherit;background:var(--surface-1);border:1px solid <?= $hasOrders ? $c['bg'] : 'var(--border)' ?>;border-top:4px solid <?= $c['bg'] ?>;border-radius:10px;padding:14px 12px;display:flex;flex-direction:column;justify-content:space-between;min-height:130px;transition:all 0.2s ease;box-shadow:<?= $hasOrders ? '0 2px 8px rgba(0,0,0,0.04)' : 'none' ?>"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
         onmouseout="this.style.transform='none';this.style.boxShadow='<?= $hasOrders ? '0 2px 8px rgba(0,0,0,0.04)' : 'none' ?>'"
         title="View <?= e($st) ?> orders">
        
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="width:28px;height:28px;border-radius:8px;background:<?= $c['bg'] ?>18;color:<?= $c['bg'] ?>;display:flex;align-items:center;justify-content:center">
            <?= svgIcon(
              $st === 'Feasibility Review' ? 'search' :
              ($st === 'Await Commercial Approval' || $st === 'Awaiting Commercial Approval' ? 'dollar' :
              ($st === 'Management Approval' ? 'users' :
              ($st === 'Pending SOF' ? 'document' :
              ($st === 'SOF Review' ? 'edit' :
              ($st === 'Installation' ? 'project' :
              ($st === 'Testing' ? 'check' :
              ($st === 'UAT' ? 'check' : 'server'))))))), 14
            ) ?>
          </div>
          <span style="font-size:0.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px">
            #<?= $stepIndex ?>
          </span>
        </div>

        <div>
          <div style="font-size:1.65rem;font-weight:800;color:<?= $hasOrders ? 'var(--text-primary)' : 'var(--text-muted)' ?>;line-height:1.1;margin-bottom:4px">
            <?= $cnt ?>
          </div>
          <div style="font-size:0.8rem;font-weight:700;color:var(--text-primary);line-height:1.25;margin-bottom:4px" title="<?= e($st) ?>">
            <?= e($stageShortName) ?>
          </div>
          <div style="font-size:0.7rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= e($st) ?>
          </div>
        </div>

        <div style="margin-top:10px;background:var(--surface-3);border-radius:4px;height:4px;overflow:hidden">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $c['bg'] ?>"></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

