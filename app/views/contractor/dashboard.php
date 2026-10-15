<?php
$assignments        = $assignments ?? [];
$counts             = $counts ?? ['Assigned'=>0,'Accepted'=>0,'In Progress'=>0,'Completed Submitted'=>0,'Completed'=>0,'Returned'=>0,'SLA Due Today'=>0];
$statusFilter       = $statusFilter ?? '';
$serviceFilter      = $serviceFilter ?? '';
$search             = $search ?? '';
$contractorIdFilter = $contractorIdFilter ?? 0;
$filterPreset       = $filterPreset ?? '';
$filterStart        = $filterStart ?? '';
$filterEnd          = $filterEnd ?? '';
$contractorsDropdown= $contractorsDropdown ?? [];
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= isContractorUser() ? 'My Jobs' : 'Contractors' ?></div>
    <div class="page-subtitle"><?= isContractorUser() ? 'View and manage your assigned installation jobs.' : 'Manage contractor assignments, field dispatch, and installation jobs across all orders.' ?></div>
  </div>
  <div class="page-header-actions">
    <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'export'])) ?>" class="btn btn-secondary">
      <?= svgIcon('download') ?> Export CSV
    </a>
  </div>
</div>

<!-- Navigation Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
  <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-primary btn-sm" style="font-weight:600">
    ⚡ Active &amp; Ongoing Jobs (<?= count($assignments) ?>)
  </a>
  <a href="<?= APP_URL ?>/?page=contractor&action=closed_jobs" class="btn btn-ghost btn-sm" style="font-weight:600">
    <?= svgIcon('check') ?> Closed Jobs
  </a>
  <a href="<?= APP_URL ?>/?page=contractor&action=teams" class="btn btn-ghost btn-sm" style="font-weight:600">
    👥 Teams &amp; Technicians
  </a>
  <a href="<?= APP_URL ?>/?page=surveys" class="btn btn-ghost btn-sm" style="font-weight:600">
    🛰️ Allocated Surveys
  </a>
</div>

<!-- Redesigned 6 Status Summary Cards -->
<style>
.contractor-status-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.contractor-status-card {
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  position: relative;
  overflow: hidden;
}
.contractor-status-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  border-color: var(--border-hover, #cbd5e1);
}
.contractor-status-card.active-status {
  border-color: var(--primary, #0F4C81);
  box-shadow: 0 0 0 1px var(--primary, #0F4C81), 0 4px 12px rgba(15, 76, 129, 0.1);
  background: var(--surface-hover, #f8fafc);
}
.status-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.status-icon-box {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.status-icon-box svg {
  width: 16px;
  height: 16px;
}
.status-pill {
  font-size: 0.68rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  letter-spacing: 0.02em;
}
.status-number {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.15;
  color: var(--text-primary, #0f172a);
  letter-spacing: -0.02em;
}
.status-label {
  font-size: 0.76rem;
  font-weight: 500;
  color: var(--text-secondary, #64748b);
  margin-top: 3px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
@media (max-width: 1200px) {
  .contractor-status-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }
}
@media (max-width: 640px) {
  .contractor-status-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .status-number {
    font-size: 1.35rem;
  }
}
@media (max-width: 380px) {
  .contractor-status-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="contractor-status-grid">
  <!-- 1. New Assignments -->
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => ($statusFilter === 'Assigned' ? null : 'Assigned')])) ?>"
     class="contractor-status-card <?= $statusFilter === 'Assigned' ? 'active-status' : '' ?>"
     title="Filter by Assigned jobs">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#EEF2FF;color:#4F46E5">
        <?= svgIcon('clock') ?>
      </div>
      <span class="status-pill" style="background:rgba(79,70,229,0.08);color:#4F46E5">Assigned</span>
    </div>
    <div>
      <div class="status-number"><?= (int)$counts['Assigned'] ?></div>
      <div class="status-label">New Assignments</div>
    </div>
  </a>

  <!-- 2. Accepted / Scheduled -->
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => ($statusFilter === 'Accepted' ? null : 'Accepted')])) ?>"
     class="contractor-status-card <?= $statusFilter === 'Accepted' ? 'active-status' : '' ?>"
     title="Filter by Accepted jobs">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#E0F2FE;color:#0284C7">
        <?= svgIcon('check') ?>
      </div>
      <span class="status-pill" style="background:rgba(2,132,199,0.08);color:#0284C7">Accepted</span>
    </div>
    <div>
      <div class="status-number"><?= (int)$counts['Accepted'] ?></div>
      <div class="status-label">Accepted / Scheduled</div>
    </div>
  </a>

  <!-- 3. In Progress -->
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => ($statusFilter === 'In Progress' ? null : 'In Progress')])) ?>"
     class="contractor-status-card <?= $statusFilter === 'In Progress' ? 'active-status' : '' ?>"
     title="Filter by In Progress jobs">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#F3E8FF;color:#7C3AED">
        <?= svgIcon('project') ?>
      </div>
      <span class="status-pill" style="background:rgba(124,58,237,0.08);color:#7C3AED">In Progress</span>
    </div>
    <div>
      <div class="status-number"><?= (int)$counts['In Progress'] ?></div>
      <div class="status-label">In Progress</div>
    </div>
  </a>

  <!-- 4. Returned for Correction -->
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => ($statusFilter === 'Returned' ? null : 'Returned')])) ?>"
     class="contractor-status-card <?= $statusFilter === 'Returned' ? 'active-status' : '' ?>"
     title="Filter by Returned jobs">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#FEE2E2;color:#DC2626">
        <?= svgIcon('refresh') ?>
      </div>
      <span class="status-pill" style="background:rgba(220,38,38,0.08);color:#DC2626">Returned</span>
    </div>
    <div>
      <div class="status-number"><?= (int)$counts['Returned'] ?></div>
      <div class="status-label">Returned for Correction</div>
    </div>
  </a>

  <!-- 5. Completed Submitted -->
  <a href="?<?= http_build_query(array_merge($_GET, ['status' => ($statusFilter === 'Completed Submitted' ? null : 'Completed Submitted')])) ?>"
     class="contractor-status-card <?= $statusFilter === 'Completed Submitted' ? 'active-status' : '' ?>"
     title="Filter by Completed Submitted jobs">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#ECFDF5;color:#059669">
        <?= svgIcon('upload') ?>
      </div>
      <span class="status-pill" style="background:rgba(5,150,105,0.08);color:#059669">Submitted</span>
    </div>
    <div>
      <div class="status-number"><?= (int)$counts['Completed Submitted'] ?></div>
      <div class="status-label">Completed Submitted</div>
    </div>
  </a>

  <!-- 6. SLA / Due Today -->
  <div class="contractor-status-card">
    <div class="status-card-header">
      <div class="status-icon-box" style="background:#FEF3C7;color:#D97706">
        <?= svgIcon('clock') ?>
      </div>
      <span class="status-pill" style="background:rgba(217,119,6,0.08);color:#D97706">Due Today</span>
    </div>
    <div>
      <div class="status-number"><?= (int)($counts['SLA Due Today'] ?? 0) ?></div>
      <div class="status-label">SLA / Due Today</div>
    </div>
  </div>
</div>

<!-- Period & Table Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="" style="display:flex;flex-direction:column;gap:12px;margin:0">
      <input type="hidden" name="page" value="contractor">

      <!-- Row 1: Period Presets & Date Range -->
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

      <!-- Row 2: Search & Column Filters -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <!-- Search Box -->
        <div style="flex:2;min-width:220px">
          <div class="search-box" style="width:100%">
            <?= svgIcon('search') ?>
            <input type="text" name="q" placeholder="Search order #, customer, location, contractor..." value="<?= e($search) ?>">
          </div>
        </div>

        <!-- Job Status -->
        <div style="flex:1;min-width:140px">
          <select name="status" class="form-control" style="font-size:0.85rem">
            <option value="">All Job Statuses</option>
            <?php foreach (['Assigned', 'Accepted', 'In Progress', 'Completed Submitted', 'Completed', 'Returned', 'Cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Service Type -->
        <div style="flex:1;min-width:150px">
          <select name="service_type" class="form-control" style="font-size:0.85rem">
            <option value="">All Service Types</option>
            <?php foreach (['Layer 2 ( last mile)', 'FTTH', 'FTTB', 'BIA (Broadband Internet Access)', 'Remote Hands Only'] as $stype): ?>
            <option value="<?= $stype ?>" <?= $serviceFilter === $stype ? 'selected' : '' ?>><?= $stype ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Contractor Organization (Admin / PM view only) -->
        <?php if (!empty($contractorsDropdown)): ?>
        <div style="flex:1;min-width:150px">
          <select name="contractor_id" class="form-control" style="font-size:0.85rem">
            <option value="">All Contractors</option>
            <?php foreach ($contractorsDropdown as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $contractorIdFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px">
          <?= svgIcon('filter') ?> Filter
        </button>
        <?php if ($search !== '' || $statusFilter !== '' || $serviceFilter !== '' || $contractorIdFilter > 0 || $filterStart !== '' || $filterEnd !== '' || ($filterPreset !== '' && $filterPreset !== 'all')): ?>
        <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-secondary btn-sm" style="height:38px;padding:0 14px;display:inline-flex;align-items:center">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Jobs Table -->
<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <div class="card-title"><?= isContractorUser() ? 'My Assigned Jobs' : 'Contractor Jobs' ?> (<?= count($assignments) ?>)</div>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Service</th>
          <th>Location</th>
          <?php if (!isContractorUser()): ?><th>Contractor</th><?php endif; ?>
          <th>Status</th>
          <th>Assigned</th>
          <th>Due</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
        <tr><td colspan="<?= !isContractorUser() ? '9' : '8' ?>"><div class="empty-state"><div class="empty-state-icon"><?= svgIcon('project', 32) ?></div><div class="empty-state-title">No jobs found</div><div class="empty-state-text">No jobs match the selected filter criteria.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($assignments as $a): ?>
        <?php
          $statusClass = match($a['status']) {
              'Assigned' => 'badge-warning',
              'Accepted' => 'badge-primary',
              'In Progress' => 'badge-primary',
              'Completed Submitted' => 'badge-info',
              'Completed' => 'badge-success',
              'Returned' => 'badge-danger',
              default => 'badge-secondary'
          };
          $svc = trim($a['service_type'] ?? '');
          if ($svc === '') {
              if (!empty($a['fttx_package'])) {
                  $svc = 'FTTH';
              } elseif (!empty($a['aggregate_capacity'])) {
                  $svc = 'Layer 2 (last mile)';
              } elseif (!empty($a['bandwidth'])) {
                  $svc = 'BIA (Broadband Internet Access)';
              } elseif ((float)($a['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($a['base_nrc_usd'] ?? 0) == 80000 || (float)($a['standard_nrc'] ?? 0) == 80000) {
                  $svc = 'Remote Hands Only';
              } else {
                  $svc = 'Service not specified';
              }
          }
        ?>
        <tr>
          <td class="font-600">
            <a href="<?= APP_URL ?>/?page=contractor&action=job&id=<?= $a['id'] ?>" style="color:var(--primary);text-decoration:none">
              <?= e($a['order_number']) ?>
            </a>
          </td>
          <td class="font-600"><?= e($a['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($svc) ?></span></td>
          <td class="text-muted font-sm"><?= e($a['customer_location'] ?: '—') ?></td>
          <?php if (!isContractorUser()): ?><td><span class="badge badge-secondary"><?= e($a['contractor_name'] ?? '—') ?></span></td><?php endif; ?>
          <td><span class="badge <?= $statusClass ?>"><?= e($a['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($a['assigned_at']) ?></td>
          <td class="text-muted font-sm"><?= $a['target_date'] ? fmtDate($a['target_date']) : '—' ?></td>
          <td>
            <a href="<?= APP_URL ?>/?page=contractor&action=job&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Job">
              <?= svgIcon('eye') ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
