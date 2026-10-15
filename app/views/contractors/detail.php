<?php
$caps = [
    ['cap_ftth_install',       'FTTH Installation'],
    ['cap_sme_install',        'SME Installation'],
    ['cap_enterprise_install', 'Enterprise Installation'],
    ['cap_site_survey',        'Site Survey'],
    ['cap_maintenance',        'Maintenance & Support'],
    ['cap_remote_support',     'Remote Hands Support'],
];
$capabilities = array_filter($caps, fn($c) => !empty($contractor[$c[0]]));
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($contractor['name']) ?></div>
    <div class="page-subtitle">Contractor · <?= e($contractor['partner_type']) ?> · Since <?= fmtDate($contractor['created_at']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=contractors" class="btn btn-secondary"><?= svgIcon('list') ?> All Contractors</a>
    <a href="<?= APP_URL ?>/?page=contractors&action=edit&id=<?= $contractor['id'] ?>" class="btn btn-primary"><?= svgIcon('edit') ?> Edit Contractor</a>
    <?php if (isAdmin() || hasRole('Management')): ?>
    <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=delete" style="display:inline" data-confirm="Permanently delete contractor <?= e($contractor['name']) ?>?">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $contractor['id'] ?>">
      <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete Contractor</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid mb-24">
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $stats['total'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Total Jobs</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--warning, #f59e0b)"><?= $stats['active'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Active Jobs</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--success)"><?= $stats['completed'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Completed</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= count($users) ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Linked Users</div>
    </div>
  </div>
</div>

<!-- Tabs Header -->
<div class="tabs mb-24" data-group="contractor_detail_tabs" style="margin-bottom:20px">
  <button class="tab-btn active" data-tab="tab_overview" data-tab-group="contractor_detail_tabs">
    <?= svgIcon('building', 16) ?> Overview &amp; Profile
  </button>
  <button class="tab-btn" data-tab="tab_teams_techs" data-tab-group="contractor_detail_tabs">
    <?= svgIcon('users', 16) ?> Teams &amp; Technicians (<?= count($teams ?? []) ?> Teams · <?= count($technicians ?? []) ?> Staff)
  </button>
  <button class="tab-btn" data-tab="tab_jobs" data-tab-group="contractor_detail_tabs">
    <?= svgIcon('briefcase', 16) ?> Work Orders (<?= count($assignments) ?>)
  </button>
</div>

<!-- ============================================================ -->
<!-- TAB 1: OVERVIEW & PROFILE -->
<!-- ============================================================ -->
<div class="tab-panel active" data-tab-panel="tab_overview" data-tab-group="contractor_detail_tabs">
  <div class="grid-2col mb-24">
    <div class="card">
      <div class="card-header"><div class="card-title">General Information</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Company Name</label><div class="font-600"><?= e($contractor['name']) ?></div></div>
          <div class="form-group"><label>Trading Name</label><div><?= e($contractor['trading_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Type</label><div><span class="badge badge-primary"><?= e($contractor['partner_type']) ?></span></div></div>
          <div class="form-group"><label>Status</label><div>
            <?php if ($contractor['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($contractor['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </div></div>
          <div class="form-group"><label>Registration Number</label><div><?= e($contractor['registration_number'] ?: '—') ?></div></div>
          <div class="form-group"><label>TIN</label><div><?= e($contractor['tin'] ?: '—') ?></div></div>
          <div class="form-group"><label>VAT / VRN</label><div><?= e($contractor['vat_vrn'] ?: '—') ?></div></div>
          <?php if ($contractor['industry_sector']): ?><div class="form-group"><label>Industry Sector</label><div><?= e($contractor['industry_sector']) ?></div></div><?php endif; ?>
          <?php if ($contractor['customer_category']): ?><div class="form-group"><label>Customer Category</label><div><?= e($contractor['customer_category']) ?></div></div><?php endif; ?>
        </div>
        <?php if ($contractor['nature_of_business']): ?>
        <div class="divider"></div>
        <div class="form-group"><label>Nature of Business</label><div><?= e($contractor['nature_of_business']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Location & KYC</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Address</label><div><?= e($contractor['address'] ?: '—') ?></div></div>
          <div class="form-group"><label>City / Region</label><div><?= e($contractor['city_region'] ?: '—') ?></div></div>
          <div class="form-group"><label>Country</label><div><?= e($contractor['country']) ?></div></div>
          <div class="form-group"><label>KYC Status</label><div>
            <?php $kycClass = match($contractor['kyc_status'] ?? '') {
                'Approved' => 'badge-success',
                'Submitted', 'Under Review' => 'badge-info',
                'Rejected' => 'badge-danger',
                default => 'badge-warning'
            }; ?>
            <span class="badge <?= $kycClass ?>"><?= e($contractor['kyc_status'] ?? 'Draft') ?></span>
          </div></div>
        </div>
        <?php if (!empty($contractor['review_notes'])): ?>
        <div class="divider"></div>
        <div class="form-group"><label>KYC Review Notes</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;white-space:pre-wrap"><?= e($contractor['review_notes']) ?></div></div>
        <?php endif; ?>
        <?php if ($capabilities): ?>
        <div class="divider"></div>
        <div class="form-group"><label>Capabilities</label><div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php foreach ($capabilities as $c): ?>
          <span class="badge badge-success"><?= e($c[1]) ?></span>
          <?php endforeach; ?>
        </div></div>
        <?php endif; ?>
        <?php if ($contractor['service_regions']): ?>
        <div class="divider"></div>
        <div class="form-group"><label>Service Regions</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;white-space:pre-wrap"><?= e($contractor['service_regions']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid-2col mb-24">
    <div class="card">
      <div class="card-header"><div class="card-title">Contacts</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Main Contact</label><div class="font-600"><?= e($contractor['main_contact_name'] ?: '—') ?></div>
            <?php if ($contractor['main_contact_phone'] || $contractor['main_contact_email']): ?>
            <div class="text-muted font-sm"><?= e($contractor['main_contact_phone'] ?: '') ?><?= $contractor['main_contact_phone'] && $contractor['main_contact_email'] ? ' · ' : '' ?><?= e($contractor['main_contact_email'] ?: '') ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group"><label>Operations Contact</label><div class="font-600"><?= e($contractor['ops_contact_name'] ?: '—') ?></div>
            <?php if ($contractor['ops_contact_phone'] || $contractor['ops_contact_email']): ?>
            <div class="text-muted font-sm"><?= e($contractor['ops_contact_phone'] ?: '') ?><?= $contractor['ops_contact_phone'] && $contractor['ops_contact_email'] ? ' · ' : '' ?><?= e($contractor['ops_contact_email'] ?: '') ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group"><label>Technical Supervisor</label><div class="font-600"><?= e($contractor['tech_supervisor_name'] ?: '—') ?></div>
            <?php if ($contractor['tech_supervisor_phone'] || $contractor['tech_supervisor_email']): ?>
            <div class="text-muted font-sm"><?= e($contractor['tech_supervisor_phone'] ?: '') ?><?= $contractor['tech_supervisor_phone'] && $contractor['tech_supervisor_email'] ? ' · ' : '' ?><?= e($contractor['tech_supervisor_email'] ?: '') ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group"><label>Escalation Contact</label><div class="font-600"><?= e($contractor['escalation_contact_name'] ?: '—') ?></div>
            <?php if ($contractor['escalation_contact_phone'] || $contractor['escalation_contact_email']): ?>
            <div class="text-muted font-sm"><?= e($contractor['escalation_contact_phone'] ?: '') ?><?= $contractor['escalation_contact_phone'] && $contractor['escalation_contact_email'] ? ' · ' : '' ?><?= e($contractor['escalation_contact_email'] ?: '') ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Banking Details</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Bank</label><div class="font-600"><?= e($contractor['bank_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Branch</label><div><?= e($contractor['bank_branch'] ?: '—') ?></div></div>
          <div class="form-group"><label>Account Name</label><div><?= e($contractor['bank_account_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Account Number</label><div><?= e($contractor['bank_account_number'] ?: '—') ?></div></div>
          <div class="form-group"><label>Payment Terms</label><div><?= e($contractor['bank_payment_terms'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- TAB 2: TEAMS & TECHNICIANS (PM / ADMIN OVERSIGHT & APPROVAL) -->
<!-- ============================================================ -->
<div class="tab-panel" data-tab-panel="tab_teams_techs" data-tab-group="contractor_detail_tabs">
  <!-- Teams Section -->
  <div class="card mb-24" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:24px">
    <div class="card-header" style="background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;padding:14px 20px">
      <div style="font-weight:700;font-size:1rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <?= svgIcon('users', 18) ?> Contractor Field Teams (<?= count($teams ?? []) ?>)
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Team Name</th>
            <th>Team Leader &amp; Phone</th>
            <th>Coverage Region</th>
            <th style="text-align:center">Technicians Count</th>
            <th>Status</th>
            <th>Registered Date</th>
            <th style="text-align:right">Approval Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teams)): ?>
          <tr><td colspan="7" class="text-muted text-center" style="padding:24px">No teams registered for this contractor.</td></tr>
          <?php else: ?>
          <?php foreach ($teams as $t): ?>
          <tr>
            <td><strong><?= e($t['team_name']) ?></strong></td>
            <td><?= e($t['leader_name'] ?: '—') ?> <span class="text-muted font-sm"><?= e($t['leader_phone'] ?: '') ?></span></td>
            <td><span class="badge badge-secondary"><?= e($t['coverage_region'] ?: 'General') ?></span></td>
            <td style="text-align:center"><span class="badge badge-primary font-600"><?= (int)$t['member_count'] ?> Members</span></td>
            <td>
              <?php if ($t['status'] === 'Approved'): ?>
                <span class="badge badge-success font-600">✓ Approved</span>
              <?php elseif ($t['status'] === 'Pending'): ?>
                <span class="badge badge-warning font-600">⏳ Pending PM Approval</span>
              <?php else: ?>
                <span class="badge badge-secondary"><?= e($t['status']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-muted font-sm"><?= fmtDate($t['created_at']) ?></td>
            <td style="text-align:right">
              <?php if ($t['status'] === 'Pending'): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=approve_team" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $contractor['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success" style="padding:4px 10px;font-size:0.75rem"><?= svgIcon('check', 12) ?> Approve Team</button>
              </form>
              <?php else: ?>
              <span class="text-muted font-sm">Verified</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Technicians Section -->
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="card-header" style="background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;padding:14px 20px">
      <div style="font-weight:700;font-size:1rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <?= svgIcon('user', 18) ?> Contractor Staff &amp; Technicians Directory (<?= count($technicians ?? []) ?>)
      </div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Technician &amp; Photo</th>
            <th>NIDA Number</th>
            <th>Sex</th>
            <th>Contact</th>
            <th>Assigned Team</th>
            <th>Skill Level</th>
            <th>Status</th>
            <th style="text-align:right">Actions &amp; Approval</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($technicians)): ?>
          <tr><td colspan="8" class="text-muted text-center" style="padding:24px">No technicians registered for this contractor.</td></tr>
          <?php else: ?>
          <?php foreach ($technicians as $tech): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;background:var(--surface-3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?php if (!empty($tech['photo_path'])): ?>
                    <img src="<?= APP_URL . '/' . ltrim($tech['photo_path'], '/') ?>" alt="<?= e($tech['full_name']) ?>" style="width:100%;height:100%;object-fit:cover">
                  <?php else: ?>
                    <span style="font-size:0.85rem;font-weight:700;color:var(--text-muted)"><?= strtoupper(substr($tech['full_name'], 0, 1)) ?></span>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--text-primary);font-size:0.88rem"><?= e($tech['full_name']) ?></div>
                  <div class="text-muted font-sm">#<?= $tech['id'] ?></div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-family:monospace;font-weight:700;font-size:0.85rem;color:var(--primary)"><?= e($tech['nida_number']) ?></div>
              <?php if (!empty($tech['nida_photo_path'])): ?>
                <a href="<?= APP_URL . '/' . ltrim($tech['nida_photo_path'], '/') ?>" target="_blank" style="font-size:0.72rem;color:var(--text-secondary);text-decoration:underline">NIDA Card ↗</a>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $tech['gender'] === 'Female' ? 'badge-warning' : 'badge-secondary' ?>" style="font-size:0.72rem"><?= e($tech['gender']) ?></span></td>
            <td>
              <div class="font-600 font-sm"><?= e($tech['phone']) ?></div>
              <?php if (!empty($tech['email'])): ?><div class="text-muted font-sm"><?= e($tech['email']) ?></div><?php endif; ?>
            </td>
            <td><?= !empty($tech['team_name']) ? '<span class="badge badge-primary font-sm">' . e($tech['team_name']) . '</span>' : '<span class="text-muted font-sm">Unassigned</span>' ?></td>
            <td><span class="badge badge-secondary font-sm"><?= e($tech['skill_level'] ?: 'Technician') ?></span></td>
            <td>
              <?php if ($tech['status'] === 'Approved'): ?>
                <span class="badge badge-success font-600">✓ Approved</span>
              <?php elseif ($tech['status'] === 'Pending'): ?>
                <span class="badge badge-warning font-600">⏳ Pending Approval</span>
              <?php elseif ($tech['status'] === 'Rejected'): ?>
                <span class="badge badge-danger font-600">✕ Rejected</span>
              <?php else: ?>
                <span class="badge badge-secondary"><?= e($tech['status']) ?></span>
              <?php endif; ?>
            </td>
            <td style="text-align:right">
              <?php if ($tech['status'] === 'Pending'): ?>
              <div style="display:inline-flex;gap:4px">
                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=approve_technician" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="technician_id" value="<?= $tech['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $contractor['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-success" style="padding:3px 8px;font-size:0.72rem"><?= svgIcon('check', 11) ?> Approve</button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=reject_technician" style="display:inline" data-confirm="Reject this technician application?">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="technician_id" value="<?= $tech['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $contractor['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" style="padding:3px 8px;font-size:0.72rem"><?= svgIcon('x', 11) ?> Reject</button>
                </form>
              </div>
              <?php else: ?>
                <span class="text-muted font-sm">Verified</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- TAB 3: WORK ORDERS / JOBS -->
<!-- ============================================================ -->
<div class="tab-panel" data-tab-panel="tab_jobs" data-tab-group="contractor_detail_tabs">
  <?php if (!empty($assignments)): ?>
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="card-header"><div class="card-title">Assigned Work Orders</div></div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Service</th>
            <th>Location</th>
            <th>Status</th>
            <th>Assigned</th>
            <th>Due</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $a): ?>
          <?php
            $aClass = match($a['status']) {
                'Assigned' => 'badge-warning',
                'Accepted', 'In Progress' => 'badge-primary',
                'Completed Submitted' => 'badge-info',
                'Completed' => 'badge-success',
                'Returned' => 'badge-danger',
                default => 'badge-secondary'
            };
          ?>
          <tr>
            <td class="font-600"><?= e($a['order_number']) ?></td>
            <td><?= e($a['customer_name']) ?></td>
            <td><span class="badge badge-primary"><?= e($a['service_type']) ?></span></td>
            <td class="text-muted font-sm"><?= e($a['customer_location'] ?: '—') ?></td>
            <td><span class="badge <?= $aClass ?>"><?= e($a['status']) ?></span></td>
            <td class="text-muted font-sm"><?= fmtDate($a['assigned_at']) ?></td>
            <td class="text-muted font-sm"><?= $a['target_date'] ? fmtDate($a['target_date']) : '—' ?></td>
            <td><a href="<?= APP_URL ?>/?page=contractor&action=job&id=<?= $a['id'] ?>" class="btn btn-sm btn-icon" style="background:transparent;border:none;color:var(--text-primary)" title="View Job"><?= svgIcon('eye') ?></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="card"><div class="card-body text-center text-muted" style="padding:32px">No jobs assigned yet.</div></div>
  <?php endif; ?>
</div>
