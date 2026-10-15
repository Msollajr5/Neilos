<?php
// ============================================================
// Neilos Partner Portal — KYC Applications (Ongoing & Approved Tabs)
// ============================================================
$isPMUser = isProjectManager();
$activeTab = $activeTab ?? 'ongoing';
?>
<div class="page-header" style="margin-bottom:20px">
  <div class="page-header-left">
    <h1 class="page-title"><?= $isPMUser ? 'Contractor KYC Applications' : 'KYC Applications' ?></h1>
    <div class="page-subtitle"><?= $totalCount ?> total application(s) registered · Compliance &amp; Regulatory Review</div>
  </div>
  <?php if (isAdmin() || hasRole('Management', 'Project Manager', 'KAM') || hasPermission('kyc.create')): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=kyc&action=new" class="btn btn-primary" style="color:#ffffff !important">
      <?= svgIcon('plus') ?> <?= $isPMUser ? 'New Contractor KYC' : 'New KYC Application' ?>
    </a>
  </div>
  <?php endif; ?>
</div>

<!-- 1. KPI Summary Cards Grid -->
<div class="stats-grid mb-24" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px">
  <!-- Total Applications -->
  <div class="stat-card kyc-kpi-card <?= $activeTab === 'ongoing' && count($ongoingApps) > 0 ? 'active-kpi' : '' ?>" 
       style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px;cursor:pointer" 
       onclick="switchKycAdminTab('<?= count($ongoingApps) > 0 ? 'ongoing' : 'approved' ?>'); resetOngoingFilters(); resetApprovedFilters();"
       title="View all registered applications">
    <div class="stat-icon blue" style="width:44px;height:44px;border-radius:10px;background:rgba(15,76,129,0.1);color:var(--accent);display:flex;align-items:center;justify-content:center">
      <?= svgIcon('document', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $totalCount ?></div>
      <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Total Registered</div>
    </div>
  </div>

  <!-- Ongoing / Awaiting Review -->
  <div class="stat-card kyc-kpi-card <?= $activeTab === 'ongoing' ? 'active-kpi' : '' ?>" 
       style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px;cursor:pointer" 
       onclick="switchKycAdminTab('ongoing'); filterOngoingByStatus('Submitted for Approval');"
       title="View pending &amp; ongoing applications">
    <div class="stat-icon yellow" style="width:44px;height:44px;border-radius:10px;background:rgba(217,119,6,0.1);color:var(--warning);display:flex;align-items:center;justify-content:center">
      <?= svgIcon('clock', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2;color:#d97706"><?= count($ongoingApps) ?></div>
      <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Ongoing / Pending</div>
    </div>
  </div>

  <!-- Approved -->
  <div class="stat-card kyc-kpi-card <?= $activeTab === 'approved' ? 'active-kpi' : '' ?>" 
       style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px;cursor:pointer" 
       onclick="switchKycAdminTab('approved'); resetApprovedFilters();"
       title="View approved KYC applications">
    <div class="stat-icon green" style="width:44px;height:44px;border-radius:10px;background:rgba(5,150,105,0.1);color:var(--success);display:flex;align-items:center;justify-content:center">
      <?= svgIcon('check', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2;color:#059669"><?= count($approvedApps) ?></div>
      <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Approved KYC</div>
    </div>
  </div>

  <!-- Drafts & Rejected -->
  <div class="stat-card kyc-kpi-card" 
       style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;gap:14px;cursor:pointer" 
       onclick="switchKycAdminTab('ongoing'); filterOngoingByStatus('Draft');"
       title="Filter by drafts &amp; rejected">
    <div class="stat-icon" style="width:44px;height:44px;border-radius:10px;background:rgba(82,82,91,0.1);color:var(--text-secondary);display:flex;align-items:center;justify-content:center">
      <?= svgIcon('edit', 22) ?>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.5rem;font-weight:700;line-height:1.2"><?= $draftCount ?> <span style="font-size:0.85rem;font-weight:400;color:var(--text-muted)">/ <?= $rejectedCount ?> rej</span></div>
      <div class="stat-label" style="font-size:0.82rem;color:var(--text-secondary)">Drafts / Rejected</div>
    </div>
  </div>
</div>

<!-- 2. Primary Tabs: Ongoing KYC Applications vs Approved KYC -->
<div class="kyc-tabs-container" style="margin-bottom:20px;border-bottom:2px solid var(--border);display:flex;gap:10px">
  <a href="<?= APP_URL ?>/?page=kyc&tab=ongoing" class="kyc-tab-btn <?= $activeTab === 'ongoing' ? 'active' : '' ?>" id="tabBtnOngoing" onclick="switchKycAdminTab('ongoing', event)" style="text-decoration:none">
    <?= svgIcon('clock', 16) ?> Ongoing KYC Applications
    <span class="badge badge-warning" style="margin-left:8px;font-size:0.75rem;padding:3px 8px">
      <?= count($ongoingApps) ?>
    </span>
  </a>
  <a href="<?= APP_URL ?>/?page=kyc&tab=approved" class="kyc-tab-btn <?= $activeTab === 'approved' ? 'active' : '' ?>" id="tabBtnApproved" onclick="switchKycAdminTab('approved', event)" style="text-decoration:none">
    <?= svgIcon('check-circle', 16) ?> Approved KYC Applications
    <span class="badge badge-success" style="margin-left:8px;font-size:0.75rem;padding:3px 8px">
      <?= count($approvedApps) ?>
    </span>
  </a>
</div>

<!-- ═════════════════════════════════════════════════════════════════ -->
<!-- TAB 1 PANE: ONGOING KYC APPLICATIONS (Drafts, Submitted, Rejected) -->
<!-- ═════════════════════════════════════════════════════════════════ -->
<div id="paneOngoingKyc" style="<?= $activeTab === 'ongoing' ? '' : 'display:none' ?>">
  <!-- Ongoing Search & Filter Bar -->
  <div class="card" style="margin-bottom:16px;border:1px solid var(--border);border-radius:var(--radius)">
    <div class="card-body" style="padding:14px 18px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <!-- Search input -->
        <div style="flex:1;min-width:240px;position:relative">
          <input type="text" id="ongoingSearchInput" class="form-control" placeholder="Search ongoing by organization, partner name, TIN..." onkeyup="filterOngoingTable()" style="padding-left:36px;font-size:0.9rem">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none">
            <?= svgIcon('search', 16) ?>
          </span>
        </div>

        <!-- Type Filter -->
        <div style="display:flex;align-items:center;gap:8px">
          <label for="ongoingTypeFilter" style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin:0;white-space:nowrap">Type:</label>
          <select id="ongoingTypeFilter" class="form-control" onchange="filterOngoingTable()" style="width:auto;min-width:130px;font-size:0.88rem;padding:6px 10px">
            <option value="">All Types</option>
            <option value="Partner">Partner</option>
            <option value="Contractor">Contractor</option>
          </select>
        </div>

        <!-- Status Filter (Ongoing Only) -->
        <div style="display:flex;align-items:center;gap:8px">
          <label for="ongoingStatusFilter" style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin:0;white-space:nowrap">Status:</label>
          <select id="ongoingStatusFilter" class="form-control" onchange="filterOngoingTable()" style="width:auto;min-width:160px;font-size:0.88rem;padding:6px 10px">
            <option value="">All Ongoing Statuses</option>
            <option value="Submitted for Approval">Submitted for Approval</option>
            <option value="Draft">Draft</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <button type="button" class="btn btn-secondary btn-sm" onclick="resetOngoingFilters()" style="padding:7px 12px">
          Reset
        </button>
      </div>
    </div>
  </div>

  <!-- Ongoing Table Card -->
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div class="table-responsive">
      <table class="data-table" id="ongoingApplicationsTable" style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px">Organization &amp; Entity</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:110px">Type</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:140px">Current Status</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:120px">Submitted</th>
            <th style="padding:12px 16px;text-align:right;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:140px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ongoingApps)): ?>
          <tr id="emptyOngoingRow">
            <td colspan="5">
              <div class="empty-state" style="padding:40px 16px;text-align:center">
                <div style="font-size:32px;margin-bottom:8px">✓</div>
                <div class="empty-state-title" style="font-weight:700;font-size:1.1rem;color:var(--text-primary)">No Ongoing Applications</div>
                <div style="font-size:0.88rem;color:var(--text-muted);max-width:400px;margin:6px auto 16px">All registered applications are approved, or no pending applications exist.</div>
                <?php if (isAdmin() || hasRole('Management', 'Project Manager', 'KAM') || hasPermission('kyc.create')): ?>
                <a href="<?= APP_URL ?>/?page=kyc&action=new" class="btn btn-primary btn-sm" style="color:#ffffff !important">
                  <?= svgIcon('plus', 13) ?> New KYC Application
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($ongoingApps as $a): ?>
          <?php
            $orgPrimary = $a['registered_name'] ?: ($a['partner_name'] ?: 'Unnamed Entity');
            $tinVal     = !empty($a['effective_tin']) ? $a['effective_tin'] : (!empty($a['tin']) ? $a['tin'] : '');
            $regNoVal   = !empty($a['effective_reg_no']) ? $a['effective_reg_no'] : (!empty($a['registration_number']) ? $a['registration_number'] : '');
            $st         = $a['status'] ?: 'Draft';
            $isPartner  = ($a['kyc_type'] ?? 'Partner') === 'Partner';

            $badgeCls = match(true) {
                $st === 'Rejected'                                                       => 'badge-danger',
                in_array($st, ['Submitted for Approval','Pending Approval','Submitted']) => 'badge-warning',
                default                                                                  => 'badge-secondary',
            };
          ?>
          <tr id="ongoing-row-<?= (int)$a['id'] ?>" 
              data-type="<?= e($a['kyc_type'] ?: 'Partner') ?>" 
              data-status="<?= e($st) ?>" 
              data-search="<?= e(strtolower($orgPrimary . ' ' . ($a['partner_name'] ?? '') . ' ' . $tinVal . ' ' . $regNoVal)) ?>"
              style="border-bottom:1px solid var(--border);transition:background 150ms">
            
            <td style="padding:14px 16px;vertical-align:middle">
              <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);line-height:1.3;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <a href="<?= APP_URL ?>/?page=kyc&action=edit&id=<?= $a['id'] ?>" style="color:inherit;text-decoration:none">
                  <?= e($orgPrimary) ?>
                </a>
                <?php if (!empty($a['partner_name']) && $a['partner_name'] !== $orgPrimary): ?>
                  <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $a['partner_id'] ?>&tab=kyc" class="badge badge-secondary" style="font-size:0.72rem;padding:2px 6px;text-decoration:none">
                    <?= e($a['partner_name']) ?>
                  </a>
                <?php endif; ?>
              </div>
            </td>

            <td style="padding:14px 16px;vertical-align:middle">
              <span class="badge <?= $isPartner ? 'badge-info' : 'badge-warning' ?>" style="display:inline-flex;align-items:center;gap:5px;font-weight:600;font-size:0.8rem;padding:4px 8px">
                <?= $isPartner ? svgIcon('building', 13) : svgIcon('users', 13) ?>
                <?= e($a['kyc_type'] ?: 'Partner') ?>
              </span>
            </td>

            <td style="padding:14px 16px;vertical-align:middle">
              <span class="badge <?= $badgeCls ?>" style="font-weight:600;font-size:0.8rem;padding:5px 10px;white-space:nowrap">
                <?= e($st) ?>
              </span>
            </td>

            <td style="padding:14px 16px;vertical-align:middle;font-size:0.85rem">
              <?php if (!empty($a['submitted_at'])): ?>
                <span style="color:var(--text-primary);font-weight:500"><?= fmtDate($a['submitted_at']) ?></span>
              <?php else: ?>
                <span class="text-muted">Not Submitted</span>
              <?php endif; ?>
            </td>

            <td style="padding:14px 16px;vertical-align:middle;text-align:right">
              <div class="actions" style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                <a href="<?= APP_URL ?>/?page=kyc&action=view&id=<?= $a['id'] ?>" class="btn btn-primary btn-sm" title="View KYC Details" style="padding:5px 10px;font-size:0.82rem;color:#ffffff !important;display:inline-flex;align-items:center;gap:4px">
                  <?= svgIcon('eye', 14) ?> View
                </a>
                <a href="<?= APP_URL ?>/?page=kyc&action=edit&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm" title="Edit / Review Application" style="padding:5px 10px;font-size:0.82rem;display:inline-flex;align-items:center;gap:4px">
                  <?= svgIcon('edit', 14) ?> Edit
                </a>
                <?php if ((hasPermission('kyc.approve') || isFinance() || isAdmin() || isManagement() || hasRole('Management', 'Billing', 'Finance')) && in_array($st, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
                <button type="button" class="btn btn-success btn-sm btn-quick-approve"
                        onclick="openQuickApproveKycModal(event, <?= (int)$a['id'] ?>, '<?= e(addslashes($orgPrimary)) ?>', '<?= e($a['kyc_type'] ?: 'Partner') ?>')"
                        title="Approve KYC Application (Finance)"
                        style="padding:5px 10px;font-size:0.82rem;background:#059669;color:#ffffff !important;border-color:#059669;font-weight:600;display:inline-flex;align-items:center;gap:4px">
                  <?= svgIcon('check', 14) ?> Approve
                </button>
                <?php endif; ?>
                <?php if (!empty($a['partner_id'])): ?>
                <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $a['partner_id'] ?>&tab=kyc" class="btn btn-secondary btn-sm" title="View in Partner Management" style="padding:5px 8px;font-size:0.82rem">
                  <?= svgIcon('building', 13) ?>
                </a>
                <?php endif; ?>
                <?php if (isAdmin() || isManagement() || hasRole('Management')): ?>
                <button type="button" class="btn btn-danger btn-sm"
                        onclick="openDeleteKycModal(event, <?= (int)$a['id'] ?>, '<?= e(addslashes($orgPrimary)) ?>', '<?= e($a['kyc_type'] ?: 'Partner') ?>', '<?= e($st) ?>')"
                        title="Delete Application" style="padding:5px 9px;font-size:0.82rem">
                  <?= svgIcon('trash', 14) ?>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          <tr id="noMatchesOngoingRow" style="display:none">
            <td colspan="5">
              <div class="empty-state" style="padding:32px 16px;text-align:center">
                <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary)">No matching ongoing applications</div>
                <div class="empty-state-text" style="font-size:0.85rem;color:var(--text-muted);margin-top:4px">Try adjusting your search query or filter options.</div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Ongoing Pagination Controls -->
    <div id="ongoingPaginationWrapper" class="table-pagination-footer" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--border);background:var(--surface-2);font-size:0.85rem;flex-wrap:wrap;gap:10px">
      <div id="ongoingPaginationInfo" style="color:var(--text-secondary)"></div>
      <div id="ongoingPaginationControls" style="display:flex;align-items:center;gap:6px"></div>
    </div>
  </div>
</div>

<!-- ═════════════════════════════════════════════════════════════════ -->
<!-- TAB 2 PANE: APPROVED KYC APPLICATIONS (Status == 'Approved')       -->
<!-- ═════════════════════════════════════════════════════════════════ -->
<div id="paneApprovedKyc" style="<?= $activeTab === 'approved' ? '' : 'display:none' ?>">
  <!-- Approved Search & Filter Bar -->
  <div class="card" style="margin-bottom:16px;border:1px solid var(--border);border-radius:var(--radius)">
    <div class="card-body" style="padding:14px 18px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <!-- Search input -->
        <div style="flex:1;min-width:240px;position:relative">
          <input type="text" id="approvedSearchInput" class="form-control" placeholder="Search approved KYC by organization, partner name, TIN..." onkeyup="filterApprovedTable()" style="padding-left:36px;font-size:0.9rem">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none">
            <?= svgIcon('search', 16) ?>
          </span>
        </div>

        <!-- Type Filter -->
        <div style="display:flex;align-items:center;gap:8px">
          <label for="approvedTypeFilter" style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin:0;white-space:nowrap">Type:</label>
          <select id="approvedTypeFilter" class="form-control" onchange="filterApprovedTable()" style="width:auto;min-width:140px;font-size:0.88rem;padding:6px 10px">
            <option value="">All Types</option>
            <option value="Partner">Partner</option>
            <option value="Contractor">Contractor</option>
          </select>
        </div>

        <button type="button" class="btn btn-secondary btn-sm" onclick="resetApprovedFilters()" style="padding:7px 12px">
          Reset
        </button>
      </div>
    </div>
  </div>

  <!-- Approved Table Card -->
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div class="table-responsive">
      <table class="data-table" id="approvedApplicationsTable" style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:var(--surface-3);border-bottom:1px solid var(--border)">
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px">Approved Organization</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:110px">Type</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:130px">Status</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:130px">Approved Date</th>
            <th style="padding:12px 16px;text-align:left;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:140px">Partner Profile</th>
            <th style="padding:12px 16px;text-align:right;font-size:0.82rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;min-width:140px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($approvedApps)): ?>
          <tr id="emptyApprovedRow">
            <td colspan="6">
              <div class="empty-state" style="padding:40px 16px;text-align:center">
                <div style="font-size:32px;margin-bottom:8px">📋</div>
                <div class="empty-state-title" style="font-weight:700;font-size:1.1rem;color:var(--text-primary)">No Approved KYC Applications Found</div>
                <div style="font-size:0.88rem;color:var(--text-muted);max-width:420px;margin:6px auto 16px">Applications that receive review approval will automatically appear here.</div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="switchKycAdminTab('ongoing')">
                  View Ongoing Applications
                </button>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($approvedApps as $a): ?>
          <?php
            $orgPrimary = $a['registered_name'] ?: ($a['partner_name'] ?: 'Unnamed Entity');
            $tinVal     = !empty($a['effective_tin']) ? $a['effective_tin'] : (!empty($a['tin']) ? $a['tin'] : '');
            $regNoVal   = !empty($a['effective_reg_no']) ? $a['effective_reg_no'] : (!empty($a['registration_number']) ? $a['registration_number'] : '');
            $isPartner  = ($a['kyc_type'] ?? 'Partner') === 'Partner';
          ?>
          <tr id="approved-row-<?= (int)$a['id'] ?>" 
              data-type="<?= e($a['kyc_type'] ?: 'Partner') ?>" 
              data-search="<?= e(strtolower($orgPrimary . ' ' . ($a['partner_name'] ?? '') . ' ' . $tinVal . ' ' . $regNoVal)) ?>"
              style="border-bottom:1px solid var(--border);transition:background 150ms">
            
            <td style="padding:14px 16px;vertical-align:middle">
              <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);line-height:1.3">
                <a href="<?= APP_URL ?>/?page=kyc&action=view&id=<?= $a['id'] ?>" style="color:inherit;text-decoration:none">
                  <?= e($orgPrimary) ?>
                </a>
              </div>
            </td>

            <td style="padding:14px 16px;vertical-align:middle">
              <span class="badge <?= $isPartner ? 'badge-info' : 'badge-warning' ?>" style="display:inline-flex;align-items:center;gap:5px;font-weight:600;font-size:0.8rem;padding:4px 8px">
                <?= $isPartner ? svgIcon('building', 13) : svgIcon('users', 13) ?>
                <?= e($a['kyc_type'] ?: 'Partner') ?>
              </span>
            </td>

            <td style="padding:14px 16px;vertical-align:middle">
              <span class="badge badge-success" style="font-weight:700;font-size:0.82rem;padding:5px 11px;white-space:nowrap;display:inline-flex;align-items:center;gap:4px">
                ✓ Approved
              </span>
            </td>

            <td style="padding:14px 16px;vertical-align:middle;font-size:0.85rem">
              <span style="color:var(--text-primary);font-weight:500">
                <?= !empty($a['approved_at']) ? fmtDate($a['approved_at']) : fmtDate($a['updated_at']) ?>
              </span>
            </td>

            <td style="padding:14px 16px;vertical-align:middle">
              <?php if (!empty($a['partner_id'])): ?>
              <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $a['partner_id'] ?>&tab=kyc" class="btn btn-secondary btn-xs" style="display:inline-flex;align-items:center;gap:4px;font-size:0.8rem;padding:4px 8px;text-decoration:none">
                <?= svgIcon('building', 13) ?> View Partner
              </a>
              <?php else: ?>
              <span class="text-muted font-sm">—</span>
              <?php endif; ?>
            </td>

            <td style="padding:14px 16px;vertical-align:middle;text-align:right">
              <div class="actions" style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                <a href="<?= APP_URL ?>/?page=kyc&action=view&id=<?= $a['id'] ?>" class="btn btn-primary btn-sm" title="View Approved KYC Details" style="padding:5px 12px;font-size:0.82rem">
                  <?= svgIcon('eye', 14) ?> View KYC
                </a>
                <?php if (isAdmin() || hasRole('Management')): ?>
                <a href="<?= APP_URL ?>/?page=kyc&action=edit&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Application" style="padding:5px 9px;font-size:0.82rem">
                  <?= svgIcon('edit', 13) ?>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          <tr id="noMatchesApprovedRow" style="display:none">
            <td colspan="6">
              <div class="empty-state" style="padding:32px 16px;text-align:center">
                <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary)">No matching approved applications</div>
                <div class="empty-state-text" style="font-size:0.85rem;color:var(--text-muted);margin-top:4px">Try adjusting your search query or type filter.</div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Approved Pagination Controls -->
    <div id="approvedPaginationWrapper" class="table-pagination-footer" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--border);background:var(--surface-2);font-size:0.85rem;flex-wrap:wrap;gap:10px">
      <div id="approvedPaginationInfo" style="color:var(--text-secondary)"></div>
      <div id="approvedPaginationControls" style="display:flex;align-items:center;gap:6px"></div>
    </div>
  </div>
</div>

<?php if (hasPermission('kyc.approve') || isFinance() || isAdmin() || isManagement() || hasRole('Management', 'Billing', 'Finance')): ?>
<!-- Quick Approve Confirmation Modal (Finance / Management) -->
<div class="modal" id="quickApproveKycModal" style="display:none" onclick="handleApproveBackdropClick(event)">
  <div class="modal-dialog" style="max-width:500px" onclick="event.stopPropagation()">
    <form id="quickApproveKycForm" onsubmit="handleKycQuickApprove(event)">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="_action" value="approve">
      <input type="hidden" name="kyc_id" id="quickApproveKycId">
      
      <div class="modal-header" style="border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
        <div class="modal-title" style="color:var(--success);font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <?= svgIcon('check', 20) ?> Approve KYC Application
        </div>
        <button type="button" class="modal-close" onclick="closeQuickApproveKycModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-muted)">&times;</button>
      </div>

      <div class="modal-body" style="padding:20px">
        <p style="margin:0 0 16px;font-size:0.95rem;color:var(--text-primary);line-height:1.5">
          Approve compliance documentation and activate this partner/entity in the system.
        </p>

        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px">
          <div style="font-size:0.85rem;margin-bottom:6px"><strong>Organization:</strong> <span id="quickApproveKycName" style="color:var(--primary);font-weight:600">—</span></div>
          <div style="font-size:0.85rem"><strong>KYC Type:</strong> <span id="quickApproveKycType" class="badge badge-info">—</span></div>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label for="quickApproveRemarks" style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;color:var(--text-secondary)">Approval Remarks / Review Notes (Optional):</label>
          <textarea id="quickApproveRemarks" name="remarks" class="form-control" rows="3" placeholder="e.g. Compliance verified, TIN and documentation validated."></textarea>
        </div>

        <div id="quickApproveError" style="display:none;padding:10px 14px;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);font-size:0.88rem;margin-top:12px"></div>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeQuickApproveKycModal()">Cancel</button>
        <button type="submit" class="btn btn-success" id="quickApproveSubmitBtn" style="background:#059669;color:#fff;border-color:#059669;display:inline-flex;align-items:center;gap:6px">
          <?= svgIcon('check', 16) ?> Confirm &amp; Approve
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (isAdmin() || isManagement() || hasRole('Management')): ?>
<!-- Delete Confirmation Modal (Admin & Management Only) -->
<div class="modal" id="deleteKycModal" style="display:none" onclick="handleBackdropClick(event)">
  <div class="modal-dialog" style="max-width:500px" onclick="event.stopPropagation()">
    <form id="deleteKycForm" onsubmit="handleKycDeletion(event)">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" id="deleteKycId">
      
      <div class="modal-header" style="border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
        <div class="modal-title" style="color:var(--danger);font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <?= svgIcon('trash', 20) ?> Delete KYC Application?
        </div>
        <button type="button" class="modal-close" onclick="closeDeleteKycModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-muted)">&times;</button>
      </div>

      <div class="modal-body" style="padding:20px">
        <p style="margin:0 0 16px;font-size:0.95rem;color:var(--text-primary);line-height:1.5">
          Are you sure you want to delete this KYC application? This action cannot be undone.
        </p>

        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px">
          <div style="font-size:0.85rem;margin-bottom:6px"><strong>Organization:</strong> <span id="deleteKycName" style="color:var(--primary);font-weight:600">—</span></div>
          <div style="font-size:0.85rem;margin-bottom:6px"><strong>KYC Type:</strong> <span id="deleteKycType" class="badge badge-info">—</span></div>
          <div style="font-size:0.85rem"><strong>Current Status:</strong> <span id="deleteKycStatus" class="badge badge-secondary">—</span></div>
        </div>

        <div id="deleteKycError" style="display:none;padding:10px 14px;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);font-size:0.88rem;margin-bottom:12px"></div>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteKycModal()">Cancel</button>
        <button type="submit" class="btn btn-danger" id="deleteKycSubmitBtn">
          Delete KYC Application
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
.kyc-kpi-card {
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  user-select: none;
}
.kyc-kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md, 0 4px 14px rgba(0,0,0,0.08));
  border-color: var(--accent) !important;
}
.kyc-kpi-card.active-kpi {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 2px rgba(15, 76, 129, 0.18);
}
.kyc-tab-btn {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  padding: 12px 20px;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  text-decoration: none;
}
.kyc-tab-btn:hover {
  color: var(--text-primary);
  background: var(--surface-2);
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}
.kyc-tab-btn.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
  font-weight: 700;
}
</style>

<script>
let targetKycRow = null;

// Primary tab switcher between Ongoing vs Approved
function switchKycAdminTab(tab, event) {
  if (event) {
    event.preventDefault();
  }
  const paneOngoing = document.getElementById('paneOngoingKyc');
  const paneApproved = document.getElementById('paneApprovedKyc');
  const btnOngoing = document.getElementById('tabBtnOngoing');
  const btnApproved = document.getElementById('tabBtnApproved');

  if (tab === 'approved') {
    if (paneOngoing) paneOngoing.style.display = 'none';
    if (paneApproved) paneApproved.style.display = 'block';
    if (btnOngoing) btnOngoing.classList.remove('active');
    if (btnApproved) btnApproved.classList.add('active');
    history.replaceState(null, null, '?page=kyc&tab=approved');
    paginateApprovedTable(1);
  } else {
    if (paneOngoing) paneOngoing.style.display = 'block';
    if (paneApproved) paneApproved.style.display = 'none';
    if (btnOngoing) btnOngoing.classList.add('active');
    if (btnApproved) btnApproved.classList.remove('active');
    history.replaceState(null, null, '?page=kyc&tab=ongoing');
    paginateOngoingTable(1);
  }
}

// Filter ongoing table by status (e.g. from KPI cards)
function filterOngoingByStatus(statusVal) {
  const statusSelect = document.getElementById('ongoingStatusFilter');
  if (statusSelect) {
    statusSelect.value = statusVal;
  }
  paginateOngoingTable(1);
}

// ============================================================
// KYC Applications Table Pagination & Filter Controls
// ============================================================
const KYC_PAGE_SIZE = 10;
let ongoingCurrentPage = 1;
let approvedCurrentPage = 1;

function renderPaginationControls(containerId, infoId, totalItems, currentPage, pageSize, onPageChange) {
  const container = document.getElementById(containerId);
  const infoEl = document.getElementById(infoId);
  if (!container || !infoEl) return;

  const totalPages = Math.ceil(totalItems / pageSize) || 1;
  const start = totalItems === 0 ? 0 : (currentPage - 1) * pageSize + 1;
  const end = Math.min(currentPage * pageSize, totalItems);

  infoEl.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${totalItems}</strong> applications`;

  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  let html = '';
  html += `<button type="button" class="btn btn-secondary btn-xs" ${currentPage === 1 ? 'disabled' : ''} onclick="${onPageChange}(${currentPage - 1})" style="padding:4px 9px;font-size:0.8rem">&laquo; Prev</button>`;

  for (let p = 1; p <= totalPages; p++) {
    if (totalPages > 7 && Math.abs(p - currentPage) > 2 && p !== 1 && p !== totalPages) {
      if (p === 2 || p === totalPages - 1) {
        html += `<span style="padding:0 4px;color:var(--text-muted)">...</span>`;
      }
      continue;
    }
    const isActive = (p === currentPage);
    html += `<button type="button" class="btn ${isActive ? 'btn-primary' : 'btn-secondary'} btn-xs" onclick="${onPageChange}(${p})" style="padding:4px 9px;font-size:0.8rem;${isActive ? 'color:#fff !important;font-weight:700;' : ''}">${p}</button>`;
  }

  html += `<button type="button" class="btn btn-secondary btn-xs" ${currentPage === totalPages ? 'disabled' : ''} onclick="${onPageChange}(${currentPage + 1})" style="padding:4px 9px;font-size:0.8rem">Next &raquo;</button>`;

  container.innerHTML = html;
}

function paginateOngoingTable(targetPage = 1) {
  ongoingCurrentPage = targetPage;
  const query = (document.getElementById('ongoingSearchInput')?.value || '').trim().toLowerCase();
  const typeFilter = document.getElementById('ongoingTypeFilter')?.value || '';
  const statusFilter = document.getElementById('ongoingStatusFilter')?.value || '';

  const rows = Array.from(document.querySelectorAll('#ongoingApplicationsTable tbody tr[id^="ongoing-row-"]'));
  
  const matchingRows = rows.filter(row => {
    const rowType = row.getAttribute('data-type') || '';
    const rowStatus = row.getAttribute('data-status') || '';
    const rowSearch = row.getAttribute('data-search') || '';

    const matchesQuery = !query || rowSearch.includes(query);
    const matchesType = !typeFilter || rowType === typeFilter;
    const matchesStatus = !statusFilter || rowStatus === statusFilter;
    return matchesQuery && matchesType && matchesStatus;
  });

  const totalItems = matchingRows.length;
  const totalPages = Math.ceil(totalItems / KYC_PAGE_SIZE) || 1;
  if (ongoingCurrentPage > totalPages) ongoingCurrentPage = totalPages;
  if (ongoingCurrentPage < 1) ongoingCurrentPage = 1;

  const startIndex = (ongoingCurrentPage - 1) * KYC_PAGE_SIZE;
  const endIndex = startIndex + KYC_PAGE_SIZE;

  rows.forEach(r => r.style.display = 'none');
  matchingRows.slice(startIndex, endIndex).forEach(r => r.style.display = '');

  const noMatchesRow = document.getElementById('noMatchesOngoingRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (totalItems === 0 && rows.length > 0) ? '' : 'none';
  }

  renderPaginationControls('ongoingPaginationControls', 'ongoingPaginationInfo', totalItems, ongoingCurrentPage, KYC_PAGE_SIZE, 'paginateOngoingTable');
}

function paginateApprovedTable(targetPage = 1) {
  approvedCurrentPage = targetPage;
  const query = (document.getElementById('approvedSearchInput')?.value || '').trim().toLowerCase();
  const typeFilter = document.getElementById('approvedTypeFilter')?.value || '';

  const rows = Array.from(document.querySelectorAll('#approvedApplicationsTable tbody tr[id^="approved-row-"]'));
  
  const matchingRows = rows.filter(row => {
    const rowType = row.getAttribute('data-type') || '';
    const rowSearch = row.getAttribute('data-search') || '';

    const matchesQuery = !query || rowSearch.includes(query);
    const matchesType = !typeFilter || rowType === typeFilter;
    return matchesQuery && matchesType;
  });

  const totalItems = matchingRows.length;
  const totalPages = Math.ceil(totalItems / KYC_PAGE_SIZE) || 1;
  if (approvedCurrentPage > totalPages) approvedCurrentPage = totalPages;
  if (approvedCurrentPage < 1) approvedCurrentPage = 1;

  const startIndex = (approvedCurrentPage - 1) * KYC_PAGE_SIZE;
  const endIndex = startIndex + KYC_PAGE_SIZE;

  rows.forEach(r => r.style.display = 'none');
  matchingRows.slice(startIndex, endIndex).forEach(r => r.style.display = '');

  const noMatchesRow = document.getElementById('noMatchesApprovedRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (totalItems === 0 && rows.length > 0) ? '' : 'none';
  }

  renderPaginationControls('approvedPaginationControls', 'approvedPaginationInfo', totalItems, approvedCurrentPage, KYC_PAGE_SIZE, 'paginateApprovedTable');
}

// Filter for Ongoing Table
function filterOngoingTable() {
  paginateOngoingTable(1);
}

function resetOngoingFilters() {
  const search = document.getElementById('ongoingSearchInput');
  const type = document.getElementById('ongoingTypeFilter');
  const status = document.getElementById('ongoingStatusFilter');
  if (search) search.value = '';
  if (type) type.value = '';
  if (status) status.value = '';
  paginateOngoingTable(1);
}

// Filter for Approved Table
function filterApprovedTable() {
  paginateApprovedTable(1);
}

function resetApprovedFilters() {
  const search = document.getElementById('approvedSearchInput');
  const type = document.getElementById('approvedTypeFilter');
  if (search) search.value = '';
  if (type) type.value = '';
  paginateApprovedTable(1);
}

// Auto open tab from URL parameter if provided and initialize pagination
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'approved') {
    switchKycAdminTab('approved');
  }
  paginateOngoingTable(1);
  paginateApprovedTable(1);
});

// ============================================================
// Quick Approve Modal (Finance & Management)
// ============================================================
let targetApproveRow = null;

function openQuickApproveKycModal(event, kycId, orgName, kycType) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  targetApproveRow = document.getElementById('ongoing-row-' + kycId);
  const idEl = document.getElementById('quickApproveKycId');
  const nameEl = document.getElementById('quickApproveKycName');
  const typeEl = document.getElementById('quickApproveKycType');
  const remEl = document.getElementById('quickApproveRemarks');
  
  if (idEl) idEl.value = kycId;
  if (nameEl) nameEl.textContent = orgName || 'Unnamed Organization';
  if (typeEl) typeEl.textContent = kycType || 'Partner';
  if (remEl) remEl.value = '';

  const errEl = document.getElementById('quickApproveError');
  if (errEl) {
    errEl.style.display = 'none';
    errEl.textContent = '';
  }

  const submitBtn = document.getElementById('quickApproveSubmitBtn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<?= svgIcon("check", 16) ?> Confirm &amp; Approve';
  }

  const modal = document.getElementById('quickApproveKycModal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeQuickApproveKycModal() {
  const modal = document.getElementById('quickApproveKycModal');
  if (modal) {
    modal.style.display = 'none';
  }
  targetApproveRow = null;
}

function handleApproveBackdropClick(event) {
  if (event.target === document.getElementById('quickApproveKycModal')) {
    closeQuickApproveKycModal();
  }
}

async function handleKycQuickApprove(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = document.getElementById('quickApproveSubmitBtn');
  const errEl = document.getElementById('quickApproveError');

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Approving...';
  }
  if (errEl) errEl.style.display = 'none';

  try {
    const formData = new FormData(form);
    const response = await fetch('<?= APP_URL ?>/?page=kyc&action=approve', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (result.success) {
      closeQuickApproveKycModal();
      if (typeof neilosToast === 'function') {
        neilosToast(result.message || 'KYC application approved successfully.', 'success');
      } else {
        alert(result.message || 'KYC application approved successfully.');
      }
      setTimeout(() => {
        window.location.href = '<?= APP_URL ?>/?page=kyc&tab=approved';
      }, 700);
    } else {
      if (errEl) {
        errEl.textContent = result.message || 'Failed to approve application.';
        errEl.style.display = 'block';
      }
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<?= svgIcon("check", 16) ?> Confirm &amp; Approve';
      }
    }
  } catch (err) {
    if (errEl) {
      errEl.textContent = 'A network or server error occurred while approving.';
      errEl.style.display = 'block';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<?= svgIcon("check", 16) ?> Confirm &amp; Approve';
    }
  }
}

// ============================================================
// Modal & Deletion Management
// ============================================================
function openDeleteKycModal(event, kycId, orgName, kycType, kycStatus) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  targetKycRow = document.getElementById('ongoing-row-' + kycId);
  document.getElementById('deleteKycId').value = kycId;
  document.getElementById('deleteKycName').textContent = orgName || 'Unnamed Organization';
  document.getElementById('deleteKycType').textContent = kycType || 'Partner';
  document.getElementById('deleteKycStatus').textContent = kycStatus || 'Draft';

  const errEl = document.getElementById('deleteKycError');
  if (errEl) {
    errEl.style.display = 'none';
    errEl.textContent = '';
  }

  const submitBtn = document.getElementById('deleteKycSubmitBtn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Delete KYC Application';
  }

  const modal = document.getElementById('deleteKycModal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeDeleteKycModal() {
  const modal = document.getElementById('deleteKycModal');
  if (modal) {
    modal.style.display = 'none';
  }
  targetKycRow = null;
}

function handleBackdropClick(event) {
  if (event.target === document.getElementById('deleteKycModal')) {
    closeDeleteKycModal();
  }
}

async function handleKycDeletion(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = document.getElementById('deleteKycSubmitBtn');
  const errEl = document.getElementById('deleteKycError');
  const kycId = document.getElementById('deleteKycId').value;

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Deleting...';
  }

  if (errEl) errEl.style.display = 'none';

  try {
    const formData = new FormData(form);
    const response = await fetch('<?= APP_URL ?>/?page=kyc&action=delete', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (result.success) {
      if (targetKycRow) {
        targetKycRow.style.transition = 'opacity 300ms, transform 300ms';
        targetKycRow.style.opacity = '0';
        targetKycRow.style.transform = 'translateX(20px)';
        setTimeout(() => {
          targetKycRow.remove();
          filterOngoingTable();
        }, 300);
      }
      closeDeleteKycModal();
      if (typeof neilosToast === 'function') {
        neilosToast(result.message || 'KYC application deleted successfully.', 'success');
      } else {
        alert(result.message || 'KYC application deleted successfully.');
      }
    } else {
      if (errEl) {
        errEl.textContent = result.message || 'Failed to delete application.';
        errEl.style.display = 'block';
      }
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Delete KYC Application';
      }
    }
  } catch (err) {
    if (errEl) {
      errEl.textContent = 'A network or server error occurred while deleting.';
      errEl.style.display = 'block';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Delete KYC Application';
    }
  }
}
</script>
