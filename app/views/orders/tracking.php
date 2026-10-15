<?php
// ============================================================
// Neilos Partner Portal — Order Tracking / Closed Orders View
// ============================================================
$isClosedPage = $isClosedPage ?? (($_GET['page'] ?? 'orders') === 'closed_orders');
$pageKey = $isClosedPage ? 'closed_orders' : 'orders';
$total = $total ?? count($orders ?? []);
$pages = $pages ?? 1;
$filterSearch = $filterSearch ?? ($_GET['q'] ?? '');
$filterPartner = $filterPartner ?? ($_GET['partner_id'] ?? 0);
$filterService = $filterService ?? ($_GET['service_type'] ?? '');
$filterActive = $filterActive ?? ($_GET['active_filter'] ?? '');
$filterStatus = $filterStatus ?? ($_GET['status'] ?? '');
$serviceTypes = $serviceTypes ?? [];
$partnersList = $partnersList ?? [];
$orders = $orders ?? [];
$ongoingCount = $ongoingCount ?? 0;
$closedCount = $closedCount ?? 0;
$totalOrdersCount = $totalOrdersCount ?? ($ongoingCount + $closedCount);
$activeLiveCount = $activeLiveCount ?? 0;
$feasibilityCount = $feasibilityCount ?? 0;
$approvalCount = $approvalCount ?? 0;
$installCount = $installCount ?? 0;
$ongoingStatuses = $ongoingStatuses ?? ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT'];
$closedStatuses  = ['Closed','Not Feasible','Cancelled'];
$canToggleActive = !isPartnerUser() && (isAdmin() || hasRole('Management', 'Project Manager', 'KAM') || hasPermission('orders.edit'));
?>
<div class="page-header" style="margin-bottom:20px">
  <div class="page-header-left">
    <h1 class="page-title"><?= $isClosedPage ? 'Closed Orders' : 'Order Tracking' ?></h1>
    <div class="page-subtitle">
      <?= $total ?> <?= $isClosedPage ? 'closed' : 'ongoing' ?> order(s) found &middot; <?= $isClosedPage ? 'Completed &amp; Live Services' : 'Delivery &amp; Fulfillment Pipeline' ?>
    </div>
  </div>
  <div class="page-header-actions">
    <?php if (!$isClosedPage && hasPermission('orders.create')): ?>
    <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary"><?= svgIcon('plus') ?> New Order</a>
    <?php endif; ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pageKey, 'export' => 'csv'])) ?>" class="btn btn-secondary"><?= svgIcon('download') ?> Export CSV</a>
  </div>
</div>

<!-- 1. KPI Summary Cards Grid -->
<div class="stats-grid mb-24" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px">
  <?php if (!$isClosedPage): ?>
    <!-- Ongoing Orders View KPI Cards -->
    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon yellow" style="width:44px;height:44px;border-radius:10px;background:rgba(217,119,6,0.1);color:var(--warning);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('clock', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2;color:#d97706"><?= $ongoingCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Active Pipeline</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon blue" style="width:44px;height:44px;border-radius:10px;background:rgba(15,76,129,0.1);color:var(--accent);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('search', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $feasibilityCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Feasibility Review</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon purple" style="width:44px;height:44px;border-radius:10px;background:rgba(147,51,234,0.1);color:#9333ea;display:flex;align-items:center;justify-content:center">
        <?= svgIcon('document', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $approvalCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Pending Approvals / SOF</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon teal" style="width:44px;height:44px;border-radius:10px;background:rgba(13,148,136,0.1);color:#0d9488;display:flex;align-items:center;justify-content:center">
        <?= svgIcon('server', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $installCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Installation &amp; Testing</div>
      </div>
    </div>
  <?php else: ?>
    <!-- Closed Orders View KPI Cards -->
    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon green" style="width:44px;height:44px;border-radius:10px;background:rgba(5,150,105,0.1);color:var(--success);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('check-circle', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2;color:#059669"><?= $closedCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Closed Orders</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon teal" style="width:44px;height:44px;border-radius:10px;background:rgba(13,148,136,0.1);color:#0d9488;display:flex;align-items:center;justify-content:center">
        <?= svgIcon('shield-check', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2;color:#0d9488"><?= $activeLiveCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Active Live Services</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon" style="width:44px;height:44px;border-radius:10px;background:rgba(107,114,128,0.1);color:var(--text-muted);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('refresh', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= max(0, $closedCount - $activeLiveCount) ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Inactive / Decommissioned</div>
      </div>
    </div>

    <div class="stat-card" style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px">
      <div class="stat-icon blue" style="width:44px;height:44px;border-radius:10px;background:rgba(15,76,129,0.1);color:var(--accent);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('document', 22) ?>
      </div>
      <div class="stat-info">
        <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $totalOrdersCount ?></div>
        <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Total Registered Orders</div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
$curSortBy  = $_GET['sort_by'] ?? '';
$curSortDir = strtoupper($_GET['sort_dir'] ?? 'DESC');
$curSortCombined = $curSortBy ? "{$curSortBy}_{$curSortDir}" : '';

$sortLink = function(string $col) use ($pageKey, $curSortBy, $curSortDir) {
    $p = $_GET;
    $p['page'] = $pageKey;
    $p['sort_by'] = $col;
    $p['sort_dir'] = ($curSortBy === $col && $curSortDir === 'ASC') ? 'DESC' : 'ASC';
    unset($p['p']);
    return '?' . http_build_query($p);
};

$sortIcon = function(string $col) use ($curSortBy, $curSortDir) {
    if ($curSortBy === $col) {
        return $curSortDir === 'ASC' ? ' <span style="color:var(--accent)">▲</span>' : ' <span style="color:var(--accent)">▼</span>';
    }
    return ' <span style="color:var(--text-muted);opacity:0.4;font-size:0.75rem">⇅</span>';
};
?>

<!-- 2. Filter Card -->
<div class="card mb-20" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="page" value="<?= e($pageKey) ?>">

      <!-- Row 1: Period Presets + Custom Date Range -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
          <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin-right:4px">Period:</span>
          <?php
            $presets = [
              'all'        => 'All Time',
              'today'      => 'Today',
              'this_month' => 'This Month',
              'last_month' => 'Last Month',
              'this_year'  => 'This Year',
            ];
            $curPreset = $filterPreset ?: 'all';
          ?>
          <?php foreach ($presets as $pKey => $pLabel): ?>
            <?php
              $pParams = $_GET;
              $pParams['page'] = $pageKey;
              $pParams['preset'] = $pKey;
              unset($pParams['start_date'], $pParams['end_date'], $pParams['p']);
            ?>
            <a href="?<?= http_build_query($pParams) ?>"
               class="btn btn-sm <?= ($curPreset === $pKey && !$filterStart && !$filterEnd) ? 'btn-primary' : 'btn-secondary' ?>"
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

      <!-- Row 2: Search, Column Filters & Sort Option -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <!-- Search Box -->
        <div style="flex:2;min-width:220px">
          <div class="search-box" style="width:100%">
            <?= svgIcon('search') ?>
            <input type="text" name="q" placeholder="Search order #, customer, circuit..." value="<?= e($filterSearch) ?>">
          </div>
        </div>

        <!-- Sort Option Dropdown -->
        <div style="flex:1;min-width:160px">
          <select name="sort_by_combined" class="form-control" style="font-size:0.85rem" onchange="const val = this.value.split(':'); document.getElementById('hiddenSortBy').value = val[0]||''; document.getElementById('hiddenSortDir').value = val[1]||'DESC';">
            <option value=":DESC" <?= empty($curSortBy) ? 'selected' : '' ?>>Sort: Default (<?= $isClosedPage ? 'Newest Installation' : 'Newest Created' ?>)</option>
            <option value="partner:ASC" <?= ($curSortBy === 'partner' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Partner Name (A → Z)</option>
            <option value="partner:DESC" <?= ($curSortBy === 'partner' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: Partner Name (Z → A)</option>
            <option value="customer:ASC" <?= ($curSortBy === 'customer' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Customer Name (A → Z)</option>
            <option value="customer:DESC" <?= ($curSortBy === 'customer' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: Customer Name (Z → A)</option>
            <?php if ($isClosedPage): ?>
            <option value="installation_date:DESC" <?= ($curSortBy === 'installation_date' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: Installation Date (Newest)</option>
            <option value="installation_date:ASC" <?= ($curSortBy === 'installation_date' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Installation Date (Oldest)</option>
            <option value="billing_date:DESC" <?= ($curSortBy === 'billing_date' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: Billing Date (Newest)</option>
            <option value="billing_date:ASC" <?= ($curSortBy === 'billing_date' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Billing Date (Oldest)</option>
            <?php endif; ?>
            <option value="created_at:DESC" <?= ($curSortBy === 'created_at' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: Created Date (Newest)</option>
            <option value="created_at:ASC" <?= ($curSortBy === 'created_at' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Created Date (Oldest)</option>
            <option value="mrc:DESC" <?= ($curSortBy === 'mrc' && $curSortDir === 'DESC') ? 'selected' : '' ?>>Sort: MRC (High → Low)</option>
            <option value="mrc:ASC" <?= ($curSortBy === 'mrc' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: MRC (Low → High)</option>
            <option value="order_number:ASC" <?= ($curSortBy === 'order_number' && $curSortDir === 'ASC') ? 'selected' : '' ?>>Sort: Order # (A → Z)</option>
          </select>
          <input type="hidden" name="sort_by" id="hiddenSortBy" value="<?= e($curSortBy) ?>">
          <input type="hidden" name="sort_dir" id="hiddenSortDir" value="<?= e($curSortDir) ?>">
        </div>

        <!-- Partner Filter (Internal Users Only) -->
        <?php if (!isPartnerUser() && !empty($partnersList)): ?>
        <div style="flex:1;min-width:160px">
          <select name="partner_id" class="form-control" style="font-size:0.85rem">
            <option value="">All Partners</option>
            <?php foreach($partnersList as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)$filterPartner === (int)$p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Status Filter -->
        <div style="flex:1;min-width:140px">
          <select name="status" class="form-control" style="font-size:0.85rem">
            <?php if (!$isClosedPage): ?>
              <option value="">All Ongoing Statuses</option>
              <?php foreach($ongoingStatuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="">All Closed Statuses</option>
              <?php foreach($closedStatuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Service Type Filter -->
        <div style="flex:1;min-width:140px">
          <select name="service_type" class="form-control" style="font-size:0.85rem">
            <option value="">All Service Types</option>
            <?php foreach($serviceTypes as $s): ?>
            <option value="<?= e($s) ?>" <?= $filterService === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($isClosedPage && (isAdmin() || isManagement())): ?>
        <!-- Active / Inactive Filter (Closed Orders Only) -->
        <div style="flex:1;min-width:140px">
          <select name="active_filter" class="form-control" style="font-size:0.85rem">
            <option value="">All (Active + Inactive)</option>
            <option value="1" <?= ($filterActive === '1') ? 'selected' : '' ?>>Active Only (Live)</option>
            <option value="0" <?= ($filterActive === '0') ? 'selected' : '' ?>>Inactive Only</option>
          </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px">
          <?= svgIcon('filter') ?> Filter
        </button>
        <?php if ($filterStart !== '' || $filterEnd !== '' || ($filterPreset !== '' && $filterPreset !== 'all') || $filterSearch !== '' || $filterStatus !== '' || $filterService !== '' || ($filterPartner > 0) || ($isClosedPage && $filterActive !== '') || $curSortBy !== ''): ?>
        <a href="<?= APP_URL ?>/?page=<?= e($pageKey) ?>" class="btn btn-secondary btn-sm" style="height:38px;padding:0 14px;display:inline-flex;align-items:center">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Action Toolbar (Closed Orders Only) -->
<?php if ($isClosedPage && $canToggleActive): ?>
<div id="closedBulkActionBar" style="display:none;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:10px 16px;margin-bottom:14px;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;box-shadow:var(--shadow-sm)">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-weight:700;font-size:0.9rem;color:var(--text-primary)"><span id="selectedClosedCount">0</span> client order(s) selected</span>
    <span class="text-muted font-sm" style="font-size:0.82rem">· Choose bulk service active action:</span>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <button type="button" class="btn btn-success btn-sm" onclick="executeClosedBulkAction(1)" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;background:#10b981;border-color:#10b981;padding:5px 14px">
      <?= svgIcon('check-circle', 14) ?> Bulk Activate Clients
    </button>
    <button type="button" class="btn btn-danger btn-sm" onclick="executeClosedBulkAction(0)" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;padding:5px 14px">
      <?= svgIcon('x-circle', 14) ?> Bulk Deactivate Clients
    </button>
  </div>
</div>

<form id="closedBulkActionForm" method="POST" action="<?= APP_URL ?>/?page=orders&action=bulk_toggle_active" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="target_status" id="bulkTargetStatus" value="1">
  <input type="hidden" name="redirect_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
  <input type="hidden" name="order_ids" id="bulkOrderIds" value="">
</form>
<?php endif; ?>

<!-- 3. Orders Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <?php if ($isClosedPage && $canToggleActive): ?>
          <th style="width:36px;text-align:center;padding:10px 8px">
            <input type="checkbox" id="selectAllClosed" title="Select all orders" onchange="toggleSelectAllClosed(this)" style="cursor:pointer;width:15px;height:15px">
          </th>
          <?php endif; ?>
          <th><a href="<?= $sortLink('order_number') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Order #<?= $sortIcon('order_number') ?></a></th>
          <?php if (!isPartnerUser()): ?>
          <th><a href="<?= $sortLink('partner') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Partner<?= $sortIcon('partner') ?></a></th>
          <?php endif; ?>
          <th><a href="<?= $sortLink('customer') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Customer<?= $sortIcon('customer') ?></a></th>
          <th><a href="<?= $sortLink('service_type') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Service Type<?= $sortIcon('service_type') ?></a></th>
          <?php if (!$isClosedPage): ?>
          <th><a href="<?= $sortLink('status') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Status<?= $sortIcon('status') ?></a></th>
          <th><a href="<?= $sortLink('created_at') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Created<?= $sortIcon('created_at') ?></a></th>
          <?php else: ?>
          <th><a href="<?= $sortLink('installation_date') ?>" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center">Installation Date<?= $sortIcon('installation_date') ?></a></th>
          <th>Service Active Status</th>
          <?php endif; ?>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr>
          <td colspan="<?= isPartnerUser() ? ($isClosedPage ? 6 : 6) : ($isClosedPage ? ($canToggleActive ? 8 : 7) : 7) ?>">
            <?php if (!$isClosedPage): ?>
            <div class="empty-state" style="padding:40px 20px;text-align:center">
              <div style="color:var(--warning);margin-bottom:12px"><?= svgIcon('clock', 40) ?></div>
              <div class="empty-state-title" style="font-size:1.1rem;font-weight:600;margin-bottom:6px">No Ongoing Orders</div>
              <div class="empty-state-text" style="color:var(--text-secondary);font-size:0.88rem;max-width:420px;margin:0 auto">
                There are currently no active orders in the delivery or fulfillment pipeline matching your filter criteria.
              </div>
              <?php if (hasPermission('orders.create')): ?>
              <div style="margin-top:16px">
                <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary btn-sm">
                  <?= svgIcon('plus') ?> Create New Order
                </a>
              </div>
              <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:40px 20px;text-align:center">
              <div style="color:var(--success);margin-bottom:12px"><?= svgIcon('check-circle', 40) ?></div>
              <div class="empty-state-title" style="font-size:1.1rem;font-weight:600;margin-bottom:6px">No Closed Orders Found</div>
              <div class="empty-state-text" style="color:var(--text-secondary);font-size:0.88rem;max-width:420px;margin:0 auto">
                No completed or closed orders match your current filters or date range.
              </div>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach($orders as $o): ?>
        <?php
          $stVal = !empty($o['service_type']) ? $o['service_type'] : '';
          if (!$stVal) {
              if (!empty($o['fttx_package'])) {
                  $stVal = 'FTTH';
              } elseif (!empty($o['aggregate_capacity'])) {
                  $stVal = 'Layer 2 ( last mile)';
              } elseif (!empty($o['bandwidth'])) {
                  $stVal = 'BIA (Broadband Internet Access)';
              } elseif ((float)($o['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($o['base_nrc_usd'] ?? 0) == 80000) {
                  $stVal = 'Remote Hands Only';
              } else {
                  $stVal = 'Not specified';
              }
          }
          // An order is active ONLY when Closed and has a billing date and is_active is 1
          $isActive = isOrderActive($o);
          $billingDate = $o['billing_start_date'] ?? null;
        ?>
        <tr>
          <?php if ($isClosedPage && $canToggleActive): ?>
          <td style="text-align:center;padding:10px 8px">
            <input type="checkbox" class="closed-order-checkbox" value="<?= $o['id'] ?>" onchange="updateClosedBulkBar()" style="cursor:pointer;width:15px;height:15px">
          </td>
          <?php endif; ?>
          <td>
            <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="font-600" style="color:var(--primary)">
              <?= e($o['order_number']) ?>
            </a>
          </td>
          <?php if (!isPartnerUser()): ?>
          <td class="font-sm"><?= e($o['partner_name'] ?? '—') ?></td>
          <?php endif; ?>
          <td>
            <div style="font-weight:500"><?= e($o['customer_name']) ?></div>
            <?php if (!empty($o['circuit_id'])): ?>
            <div class="font-sm text-muted" style="font-size:0.75rem">CID: <?= e($o['circuit_id']) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-primary"><?= e($stVal) ?></span></td>
          <?php if (!$isClosedPage): ?>
          <td>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
              <span class="badge <?= orderStatusClass($o['status']) ?>"><?= e($o['status']) ?></span>
              <?php if ($o['status'] === 'UAT'): ?>
                <?php $uInfo = getUatCountdownInfo($o); ?>
                <?= $uInfo['badge_html'] ?>
              <?php endif; ?>
            </div>
          </td>
          <td class="text-muted font-sm"><?= fmtDate($o['created_at']) ?></td>
          <?php else: ?>
          <!-- Installation Date column -->
          <td class="font-sm">
            <?php 
              $instDate = $o['installation_date'] ?? ($o['closed_date'] ?? ($o['billing_start_date'] ?? null));
            ?>
            <?php if ($instDate): ?>
              <span style="color:var(--text-primary);font-weight:500"><?= fmtDate($instDate) ?></span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <!-- Service Active Status column -->
          <td>
            <?php if ($isActive): ?>
              <span class="badge badge-success" style="display:inline-flex;align-items:center;gap:4px">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                Active
              </span>
            <?php else: ?>
              <span class="badge badge-secondary" style="display:inline-flex;align-items:center;gap:4px">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                Inactive
              </span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td>
            <div class="actions" style="display:flex;align-items:center;gap:5px;flex-wrap:nowrap">
              <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm" title="View Order" style="display:inline-flex;align-items:center;gap:4px;font-size:0.78rem;padding:4px 9px">
                <?= svgIcon('eye', 13) ?> View
              </a>

              <?php if ($isClosedPage && $canToggleActive): ?>
                <?php if ($isActive): ?>
                  <!-- If currently Active -> Show ONLY small red Deactivate button (❌) -->
                  <form method="POST" action="<?= APP_URL ?>/?page=orders&action=toggle_active" style="display:inline" data-confirm="Deactivate client service for <?= e($o['customer_name']) ?> (Order #<?= e($o['order_number']) ?>)?" data-confirm-title="Deactivate Client" data-confirm-btn="Deactivate" data-confirm-class="btn-danger">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <input type="hidden" name="is_active" value="0">
                    <input type="hidden" name="redirect_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Deactivate Client Service (Set Inactive)" style="width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                  </form>
                <?php else: ?>
                  <!-- If currently Inactive -> Show ONLY small green Activate button (✔️) -->
                  <form method="POST" action="<?= APP_URL ?>/?page=orders&action=toggle_active" style="display:inline" data-confirm="Activate client service for <?= e($o['customer_name']) ?> (Order #<?= e($o['order_number']) ?>)?" data-confirm-title="Activate Client" data-confirm-btn="Activate" data-confirm-class="btn-success">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <input type="hidden" name="is_active" value="1">
                    <input type="hidden" name="redirect_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <button type="submit" class="btn btn-success btn-sm btn-icon" title="Activate Client Service (Set Active)" style="width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:#10b981;border-color:#10b981">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>

              <?php if (isAdmin() || hasRole('Management')): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=delete" style="display:inline" data-confirm="Permanently delete order <?= e($o['order_number']) ?>?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Order" style="width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center"><?= svgIcon('trash', 13) ?></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;gap:8px;align-items:center;justify-content:center">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$i, 'page'=>$pageKey])) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleSelectAllClosed(master) {
  const boxes = document.querySelectorAll('.closed-order-checkbox');
  boxes.forEach(b => b.checked = master.checked);
  updateClosedBulkBar();
}

function updateClosedBulkBar() {
  const checked = document.querySelectorAll('.closed-order-checkbox:checked');
  const count = checked.length;
  const bar = document.getElementById('closedBulkActionBar');
  const countSpan = document.getElementById('selectedClosedCount');
  const master = document.getElementById('selectAllClosed');
  const allBoxes = document.querySelectorAll('.closed-order-checkbox');
  
  if (countSpan) countSpan.textContent = count;
  if (bar) bar.style.display = count > 0 ? 'flex' : 'none';
  if (master && allBoxes.length > 0) {
    master.checked = count === allBoxes.length;
    master.indeterminate = count > 0 && count < allBoxes.length;
  }
}

function executeClosedBulkAction(targetStatus) {
  const checked = document.querySelectorAll('.closed-order-checkbox:checked');
  if (checked.length === 0) {
    alert('Please select at least one client order.');
    return;
  }
  const actionText = targetStatus === 1 ? 'ACTIVATE' : 'DEACTIVATE';
  const confirmMsg = `Are you sure you want to ${actionText} ${checked.length} selected client service(s)?`;
  if (!confirm(confirmMsg)) {
    return;
  }
  const ids = Array.from(checked).map(c => c.value);
  document.getElementById('bulkOrderIds').value = ids.join(',');
  document.getElementById('bulkTargetStatus').value = targetStatus;
  document.getElementById('closedBulkActionForm').submit();
}
</script>

<style>
.stat-card:hover {
  transform: translateY(-2px);
}
</style>
