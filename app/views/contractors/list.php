<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Contractors</h1>
    <div class="page-subtitle">All registered contractor organizations, field teams, and technicians in the system</div>
  </div>
  <div class="page-header-actions">
    <button type="button" class="btn btn-primary" onclick="openModal('addContractorModal')">
      <?= svgIcon('plus') ?> Register Contractor
    </button>
    <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'export'])) ?>" class="btn btn-secondary">
      <?= svgIcon('download') ?> Export CSV
    </a>
  </div>
</div>

<!-- KPI Stats Row -->
<div class="stats-grid mb-24" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="card">
    <div class="card-body" style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59, 130, 246, 0.12); color: var(--accent); display: flex; align-items: center; justify-content: center;">
        <?= svgIcon('users', 22) ?>
      </div>
      <div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.2;"><?= $total ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Total Contractors</div>
      </div>
    </div>
  </div>
  
  <div class="card">
    <div class="card-body" style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); color: var(--success); display: flex; align-items: center; justify-content: center;">
        <?= svgIcon('check-circle', 22) ?>
      </div>
      <div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--success); line-height: 1.2;"><?= $activeContractors ?? $total ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Active Contractors</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body" style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(139, 92, 246, 0.12); color: #8b5cf6; display: flex; align-items: center; justify-content: center;">
        <?= svgIcon('users', 22) ?>
      </div>
      <div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #8b5cf6; line-height: 1.2;"><?= $totalTeams ?? 0 ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Total Field Teams</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body" style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); color: var(--warning); display: flex; align-items: center; justify-content: center;">
        <?= svgIcon('user-check', 22) ?>
      </div>
      <div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.2;">
          <?= $totalTechnicians ?? 0 ?>
          <?php if (!empty($pendingTechs) && $pendingTechs > 0): ?>
          <span class="badge badge-warning" style="font-size:0.68rem;vertical-align:middle;margin-left:4px"><?= $pendingTechs ?> pending</span>
          <?php endif; ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Total Technicians</div>
      </div>
    </div>
  </div>
</div>

<!-- Period & Table Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="" style="display:flex;flex-direction:column;gap:12px;margin:0">
      <input type="hidden" name="page" value="contractors">

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
        <div style="flex:2;min-width:240px">
          <div class="search-box" style="width:100%">
            <?= svgIcon('search') ?>
            <input type="text" name="q" placeholder="Search contractor name, trading name, reg #, TIN, city..." value="<?= e($search) ?>">
          </div>
        </div>

        <!-- Organization Status -->
        <div style="flex:1;min-width:140px">
          <select name="status" class="form-control" style="font-size:0.85rem">
            <option value="">All Org Statuses</option>
            <?php foreach (['Active', 'Inactive', 'Suspended'] as $st): ?>
            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- KYC Status -->
        <div style="flex:1;min-width:140px">
          <select name="kyc_status" class="form-control" style="font-size:0.85rem">
            <option value="">All KYC Statuses</option>
            <?php foreach (['Approved', 'Submitted', 'Under Review', 'Draft', 'Rejected'] as $kst): ?>
            <option value="<?= $kst ?>" <?= $kycFilter === $kst ? 'selected' : '' ?>><?= $kst ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="height:38px;padding:0 16px">
          <?= svgIcon('filter') ?> Filter
        </button>
        <?php if ($search !== '' || $statusFilter !== '' || $kycFilter !== '' || $filterStart !== '' || $filterEnd !== '' || ($filterPreset !== '' && $filterPreset !== 'all')): ?>
        <a href="<?= APP_URL ?>/?page=contractors" class="btn btn-secondary btn-sm" style="height:38px;padding:0 14px;display:inline-flex;align-items:center">
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Contractor Organization</th>
          <th>Contact Info</th>
          <th>Teams &amp; Technicians</th>
          <th>Linked Users</th>
          <th>KYC Status</th>
          <th>Org Status</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($contractors)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state" style="padding:40px 20px;text-align:center">
              <?= svgIcon('users', 40) ?>
              <div class="empty-state-title" style="font-size:1.1rem;font-weight:700;margin-top:12px">No contractors found</div>
              <div class="empty-state-text" style="color:var(--text-muted);font-size:0.88rem;margin-top:4px">No contractor organizations match the selected filter criteria.</div>
              <button type="button" class="btn btn-primary btn-sm" style="margin-top:14px" onclick="openModal('addContractorModal')">
                <?= svgIcon('plus') ?> Register New Contractor
              </button>
            </div>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($contractors as $c): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-pale);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0">
                <?= strtoupper(substr($c['name'], 0, 2)) ?>
              </div>
              <div>
                <a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $c['id'] ?>" class="font-600" style="color:var(--text-primary);font-size:0.92rem;text-decoration:none">
                  <?= e($c['name']) ?>
                </a>
                <?php if (!empty($c['trading_name']) && $c['trading_name'] !== $c['name']): ?>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= e($c['trading_name']) ?></div>
                <?php endif; ?>
                <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px">
                  <?= svgIcon('map-pin', 11) ?> <?= e($c['city_region'] ?: 'Tanzania') ?>
                </div>
              </div>
            </div>
          </td>
          <td class="font-sm">
            <?php if (!empty($c['main_contact_name'])): ?>
            <div style="font-weight:600;color:var(--text-primary)"><?= e($c['main_contact_name']) ?></div>
            <?php endif; ?>
            <?php if (!empty($c['main_contact_phone'])): ?>
            <div style="color:var(--text-secondary)"><?= svgIcon('phone', 11) ?> <?= e($c['main_contact_phone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($c['main_contact_email'])): ?>
            <div style="color:var(--text-muted);font-size:0.78rem"><?= e($c['main_contact_email']) ?></div>
            <?php endif; ?>
            <?php if (empty($c['main_contact_name']) && empty($c['main_contact_phone']) && empty($c['main_contact_email'])): ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;flex-direction:column;gap:2px">
              <div style="font-weight:600;font-size:0.85rem;color:var(--text-primary)">
                <?= (int)($c['total_teams'] ?? 0) ?> <?= (int)($c['total_teams'] ?? 0) === 1 ? 'Team' : 'Teams' ?>
              </div>
              <div style="font-size:0.78rem;color:var(--text-secondary)">
                <?= (int)($c['total_technicians'] ?? 0) ?> <?= (int)($c['total_technicians'] ?? 0) === 1 ? 'Technician' : 'Technicians' ?>
                <?php if (!empty($c['pending_technicians']) && (int)$c['pending_technicians'] > 0): ?>
                <span class="badge badge-warning" style="font-size:0.65rem;padding:1px 5px"><?= (int)$c['pending_technicians'] ?> pending</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td class="font-sm">
            <span class="badge badge-secondary" style="font-size:0.75rem">
              <?= (int)($c['user_count'] ?? 0) ?> <?= (int)($c['user_count'] ?? 0) === 1 ? 'User' : 'Users' ?>
            </span>
          </td>
          <td>
            <?php
              $kst = $c['kyc_status'] ?? 'Draft';
              $kBadgeClass = match($kst) {
                'Approved'     => 'badge-success',
                'Submitted'    => 'badge-primary',
                'Under Review' => 'badge-warning',
                'Rejected'     => 'badge-danger',
                default        => 'badge-secondary'
              };
            ?>
            <span class="badge <?= $kBadgeClass ?>" style="font-size:0.75rem"><?= e($kst) ?></span>
          </td>
          <td>
            <?php if ($c['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($c['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <div class="actions" style="justify-content:flex-end;gap:6px">
              <a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="View Contractor Details &amp; Teams" style="display:inline-flex;align-items:center;gap:4px;font-size:0.78rem;padding:4px 10px">
                <?= svgIcon('eye', 13) ?> View
              </a>
              <a href="<?= APP_URL ?>/?page=contractors&action=edit&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit Organization"><?= svgIcon('edit', 13) ?></a>
              <?php if (isAdmin() || hasRole('Management')): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=delete" style="display:inline" data-confirm="Permanently delete contractor <?= e($c['name']) ?>?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Contractor"><?= svgIcon('trash', 13) ?></button>
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
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- MODAL: REGISTER NEW CONTRACTOR -->
<!-- ============================================================ -->
<div class="modal" id="addContractorModal">
  <div class="modal-dialog" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title"><?= svgIcon('plus', 18) ?> Register New Contractor Organization</div>
      <button class="modal-close" onclick="closeModal('addContractorModal')">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=create">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="modal-body" style="padding:20px 24px">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label" style="font-weight:600">Company / Contractor Name <span class="required" style="color:var(--danger)">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Fiber Works Ltd" required>
        </div>

        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label" style="font-weight:600">Trading Name</label>
          <input type="text" name="trading_name" class="form-control" placeholder="e.g. FiberWorks">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label class="form-label" style="font-weight:600">Contact Person Name</label>
            <input type="text" name="contact_name" class="form-control" placeholder="e.g. John Doe">
          </div>
          <div class="form-group">
            <label class="form-label" style="font-weight:600">Phone Number</label>
            <input type="tel" name="contact_phone" class="form-control" placeholder="+255 7xx xxx xxx">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label class="form-label" style="font-weight:600">Contact Email</label>
            <input type="email" name="contact_email" class="form-control" placeholder="contact@contractor.co.tz">
          </div>
          <div class="form-group">
            <label class="form-label" style="font-weight:600">City / Region</label>
            <input type="text" name="city_region" class="form-control" placeholder="e.g. Dar es Salaam">
          </div>
        </div>

        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label" style="font-weight:600">Physical Address</label>
          <textarea name="address" class="form-control" rows="2" placeholder="Office location / physical address..."></textarea>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" style="font-weight:600">Initial Status</label>
          <select name="status" class="form-control">
            <option value="Active" selected>Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addContractorModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Register Contractor</button>
      </div>
    </form>
  </div>
</div>
