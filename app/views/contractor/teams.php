<?php
// ============================================================
// Neilos Portal — Contractor Teams & Technicians Management View
// ============================================================
$canManageTeams = isContractorUser() || hasRole('Project Manager') || isAdmin() || hasRole('Management');
$isPMOrAdmin = hasRole('Project Manager') || isAdmin() || hasRole('Management');
$activeTab = $_GET['tab'] ?? 'teams';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <?= svgIcon('users', 24) ?> Teams &amp; Technicians Directory
    </h1>
    <div class="page-subtitle">
      <?= $contractorPartner ? e($contractorPartner['name']) . ' · ' : '' ?>Field Operations Personnel &amp; Team Management
    </div>
  </div>
  <div class="page-header-actions">
    <button type="button" class="btn btn-secondary" onclick="openModal('addTeamModal')">
      <?= svgIcon('plus') ?> Register Team
    </button>
    <button type="button" class="btn btn-primary" onclick="openModal('addTechnicianModal')">
      <?= svgIcon('plus') ?> Add Technician
    </button>
  </div>
</div>

<!-- Navigation Tabs -->
<div class="tabs mb-24" data-group="teams_tabs" style="margin-bottom:20px">
  <button class="tab-btn <?= $activeTab === 'teams' ? 'active' : '' ?>" data-tab="tab_teams" data-tab-group="teams_tabs">
    <?= svgIcon('users', 16) ?> Teams &amp; Assignments (<?= count($teams) ?>)
  </button>
  <button class="tab-btn <?= $activeTab === 'technicians' ? 'active' : '' ?>" data-tab="tab_technicians" data-tab-group="teams_tabs">
    <?= svgIcon('user', 16) ?> All Technicians / Staff (<?= count($technicians) ?>)
  </button>
</div>

<!-- ============================================================ -->
<!-- TAB 1: TEAMS & ASSIGNMENTS -->
<!-- ============================================================ -->
<div class="tab-panel <?= $activeTab === 'teams' ? 'active' : '' ?>" data-tab-panel="tab_teams" data-tab-group="teams_tabs">
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="card-header" style="background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;padding:14px 20px">
      <div style="font-weight:700;font-size:1rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <?= svgIcon('users', 18) ?> Operational Field Teams
      </div>
      <span class="badge badge-primary" style="font-size:0.75rem"><?= count($teams) ?> Registered Teams</span>
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
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teams)): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state" style="padding:40px 20px;text-align:center">
                <div style="font-size:2rem;margin-bottom:8px">👥</div>
                <div class="empty-state-title" style="font-weight:700;font-size:1rem;color:var(--text-primary)">No Field Teams Registered</div>
                <div class="empty-state-text" style="color:var(--text-secondary);font-size:0.85rem;margin-top:4px">
                  Click "+ Register Team" to create your first operational team.
                </div>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($teams as $t): ?>
          <tr>
            <td>
              <div style="font-weight:700;color:var(--primary);font-size:0.92rem"><?= e($t['team_name']) ?></div>
              <div class="text-muted" style="font-size:0.75rem">ID: #<?= $t['id'] ?></div>
            </td>
            <td>
              <div style="font-weight:600"><?= e($t['leader_name'] ?: '—') ?></div>
              <div class="text-muted" style="font-size:0.78rem"><?= e($t['leader_phone'] ?: '') ?></div>
            </td>
            <td>
              <span class="badge badge-secondary" style="font-size:0.75rem"><?= e($t['coverage_region'] ?: 'General') ?></span>
            </td>
            <td style="text-align:center">
              <span class="badge badge-primary" style="font-size:0.8rem;font-weight:700;padding:4px 10px">
                <?= (int)$t['member_count'] ?> Members
              </span>
            </td>
            <td>
              <?php if ($t['status'] === 'Approved'): ?>
                <span class="badge badge-success" style="font-weight:700">✓ Approved</span>
              <?php elseif ($t['status'] === 'Pending'): ?>
                <span class="badge badge-warning" style="font-weight:700">⏳ Pending PM Approval</span>
              <?php else: ?>
                <span class="badge badge-secondary"><?= e($t['status']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-muted font-sm"><?= fmtDate($t['created_at']) ?></td>
            <td style="text-align:right">
              <div style="display:inline-flex;gap:6px">
                <?php if ($isPMOrAdmin && $t['status'] === 'Pending'): ?>
                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=approve_team" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=teams">
                  <button type="submit" class="btn btn-sm btn-success" style="padding:4px 8px;font-size:0.75rem" title="Approve Team">
                    <?= svgIcon('check', 12) ?> Approve
                  </button>
                </form>
                <?php endif; ?>

                <button type="button" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:0.75rem" onclick="openEditTeamModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)">
                  <?= svgIcon('edit', 12) ?> Edit
                </button>

                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=delete_team" style="display:inline" data-confirm="Delete team '<?= e($t['team_name']) ?>'?">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="team_id" value="<?= $t['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=teams">
                  <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px;font-size:0.75rem" title="Delete Team">
                    <?= svgIcon('trash', 12) ?>
                  </button>
                </form>
              </div>
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
<!-- TAB 2: ALL TECHNICIANS / STAFF DIRECTORY -->
<!-- ============================================================ -->
<div class="tab-panel <?= $activeTab === 'technicians' ? 'active' : '' ?>" data-tab-panel="tab_technicians" data-tab-group="teams_tabs">
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="card-header" style="background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;padding:14px 20px;flex-wrap:wrap;gap:12px">
      <div style="font-weight:700;font-size:1rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <?= svgIcon('users', 18) ?> Contractor Staff &amp; Technicians Directory
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <input type="text" id="techSearchInput" class="form-control" placeholder="Search technician, NIDA, phone..." style="height:32px;font-size:0.8rem;width:240px" onkeyup="filterTechTable()">
        <span class="badge badge-primary" style="font-size:0.75rem"><?= count($technicians) ?> Staff Records</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table" id="techniciansTable">
        <thead>
          <tr>
            <th>Technician &amp; Photo</th>
            <th>NIDA Number</th>
            <th>Sex</th>
            <th>Contact Info</th>
            <th>Assigned Team</th>
            <th>Skill Level</th>
            <th>Status</th>
            <th>Date Added</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($technicians)): ?>
          <tr>
            <td colspan="9">
              <div class="empty-state" style="padding:40px 20px;text-align:center">
                <div style="font-size:2rem;margin-bottom:8px">👷</div>
                <div class="empty-state-title" style="font-weight:700;font-size:1rem;color:var(--text-primary)">No Technicians Registered</div>
                <div class="empty-state-text" style="color:var(--text-secondary);font-size:0.85rem;margin-top:4px">
                  Click "+ Add Technician" to register field staff with NIDA and photo.
                </div>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($technicians as $tech): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:38px;height:38px;border-radius:50%;overflow:hidden;background:var(--surface-3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?php if (!empty($tech['photo_path'])): ?>
                    <img src="<?= APP_URL . '/' . ltrim($tech['photo_path'], '/') ?>" alt="<?= e($tech['full_name']) ?>" style="width:100%;height:100%;object-fit:cover">
                  <?php else: ?>
                    <span style="font-size:0.9rem;font-weight:700;color:var(--text-muted)">
                      <?= strtoupper(substr($tech['full_name'], 0, 1)) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:700;color:var(--text-primary);font-size:0.9rem"><?= e($tech['full_name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem">ID: #<?= $tech['id'] ?></div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-family:monospace;font-weight:700;font-size:0.85rem;color:var(--primary)"><?= e($tech['nida_number']) ?></div>
              <?php if (!empty($tech['nida_photo_path'])): ?>
                <a href="<?= APP_URL . '/' . ltrim($tech['nida_photo_path'], '/') ?>" target="_blank" style="font-size:0.72rem;color:var(--text-secondary);text-decoration:underline">
                  View NIDA Card ↗
                </a>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $tech['gender'] === 'Female' ? 'badge-warning' : 'badge-secondary' ?>" style="font-size:0.72rem;font-weight:600">
                <?= e($tech['gender']) ?>
              </span>
            </td>
            <td>
              <div style="font-weight:600;font-size:0.85rem"><?= e($tech['phone']) ?></div>
              <?php if (!empty($tech['email'])): ?>
                <div class="text-muted" style="font-size:0.75rem"><?= e($tech['email']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($tech['team_name'])): ?>
                <span class="badge badge-primary" style="font-size:0.75rem;font-weight:600">
                  <?= e($tech['team_name']) ?>
                </span>
              <?php else: ?>
                <span class="text-muted font-sm">Unassigned</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge-secondary" style="font-size:0.72rem"><?= e($tech['skill_level'] ?: 'Technician') ?></span>
            </td>
            <td>
              <?php if ($tech['status'] === 'Approved'): ?>
                <span class="badge badge-success" style="font-weight:700">✓ Approved</span>
              <?php elseif ($tech['status'] === 'Pending'): ?>
                <span class="badge badge-warning" style="font-weight:700">⏳ Pending Approval</span>
              <?php elseif ($tech['status'] === 'Rejected'): ?>
                <span class="badge badge-danger" style="font-weight:700">✕ Rejected</span>
                <?php if (!empty($tech['rejection_reason'])): ?>
                  <div class="text-muted" style="font-size:0.7rem;max-width:140px"><?= e($tech['rejection_reason']) ?></div>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge-secondary"><?= e($tech['status']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-muted font-sm"><?= fmtDate($tech['created_at']) ?></td>
            <td style="text-align:right">
              <div style="display:inline-flex;gap:6px">
                <?php if ($isPMOrAdmin && $tech['status'] === 'Pending'): ?>
                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=approve_technician" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="technician_id" value="<?= $tech['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=technicians">
                  <button type="submit" class="btn btn-sm btn-success" style="padding:4px 8px;font-size:0.75rem" title="Approve Staff">
                    <?= svgIcon('check', 12) ?> Approve
                  </button>
                </form>
                <?php endif; ?>

                <button type="button" class="btn btn-sm btn-secondary" style="padding:4px 8px;font-size:0.75rem" onclick="openEditTechModal(<?= htmlspecialchars(json_encode($tech), ENT_QUOTES, 'UTF-8') ?>)">
                  <?= svgIcon('edit', 12) ?> Edit
                </button>

                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=delete_technician" style="display:inline" data-confirm="Delete technician '<?= e($tech['full_name']) ?>'?">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="technician_id" value="<?= $tech['id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=technicians">
                  <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px;font-size:0.75rem" title="Delete Technician">
                    <?= svgIcon('trash', 12) ?>
                  </button>
                </form>
              </div>
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
<!-- MODALS -->
<!-- ============================================================ -->

<!-- Modal: Register Team -->
<div class="modal-backdrop" id="addTeamModal">
  <div class="modal" style="max-width:520px;width:95%">
    <div class="modal-header">
      <div class="modal-title"><?= svgIcon('users') ?> Register New Field Team</div>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=add_team">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="contractor_partner_id" value="<?= $effPartnerId ?>">
      <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=teams">
      <div class="modal-body">
        <div class="form-group">
          <label>Team Name <span class="required">*</span></label>
          <input type="text" name="team_name" class="form-control" placeholder="e.g. Dar Metro Fiber Splicing Crew" required>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Team Leader Name</label>
            <input type="text" name="leader_name" class="form-control" placeholder="Full name of team lead">
          </div>
          <div class="form-group">
            <label>Leader Phone</label>
            <input type="text" name="leader_phone" class="form-control" placeholder="+255 7XX XXX XXX">
          </div>
        </div>
        <div class="form-group">
          <label>Coverage Region / Zone</label>
          <input type="text" name="coverage_region" class="form-control" placeholder="e.g. Dar es Salaam, Arusha, Moshi">
        </div>
        <div class="alert alert-info" style="font-size:0.8rem;margin-bottom:0">
          ℹ Newly registered teams will remain in <strong>Pending Approval</strong> until verified by the Project Coordinator/Manager.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Submit Team</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Team -->
<div class="modal-backdrop" id="editTeamModal">
  <div class="modal" style="max-width:520px;width:95%">
    <div class="modal-header">
      <div class="modal-title"><?= svgIcon('edit') ?> Edit Field Team</div>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=edit_team">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="team_id" id="edit_team_id" value="">
      <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=teams">
      <div class="modal-body">
        <div class="form-group">
          <label>Team Name <span class="required">*</span></label>
          <input type="text" name="team_name" id="edit_team_name" class="form-control" required>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Team Leader Name</label>
            <input type="text" name="leader_name" id="edit_leader_name" class="form-control">
          </div>
          <div class="form-group">
            <label>Leader Phone</label>
            <input type="text" name="leader_phone" id="edit_leader_phone" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label>Coverage Region / Zone</label>
          <input type="text" name="coverage_region" id="edit_coverage_region" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add Technician -->
<div class="modal-backdrop" id="addTechnicianModal">
  <div class="modal" style="max-width:620px;width:95%">
    <div class="modal-header">
      <div class="modal-title"><?= svgIcon('user') ?> Register Technician / Staff</div>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=add_technician" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="contractor_partner_id" value="<?= $effPartnerId ?>">
      <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=technicians">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Juma Ally Rashid" required>
          </div>
          <div class="form-group">
            <label>NIDA National ID No. <span class="required">*</span></label>
            <input type="text" name="nida_number" class="form-control" placeholder="e.g. 19900101-12345-00001-20" required>
          </div>
        </div>

        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label>Sex / Gender <span class="required">*</span></label>
            <select name="gender" class="form-control" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="form-group">
            <label>Phone Number <span class="required">*</span></label>
            <input type="text" name="phone" class="form-control" placeholder="+255 7XX XXX XXX" required>
          </div>
          <div class="form-group">
            <label>Email (Optional)</label>
            <input type="email" name="email" class="form-control" placeholder="tech@domain.com">
          </div>
        </div>

        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Assign to Team</label>
            <select name="team_id" class="form-control">
              <option value="">-- No Team (Unassigned) --</option>
              <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['team_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Skill Level / Role</label>
            <select name="skill_level" class="form-control">
              <option value="Senior Splicing Engineer">Senior Splicing Engineer</option>
              <option value="Fiber Technician" selected>Fiber Technician</option>
              <option value="Rigging / Tower Specialist">Rigging / Tower Specialist</option>
              <option value="CPE &amp; LAN Installer">CPE &amp; LAN Installer</option>
              <option value="Assistant Technician">Assistant Technician</option>
            </select>
          </div>
        </div>

        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Staff Passport / Headshot Photo</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
          </div>
          <div class="form-group">
            <label>NIDA Card Copy / Photo</label>
            <input type="file" name="nida_photo" class="form-control" accept="image/*,application/pdf">
          </div>
        </div>

        <div class="alert alert-info" style="font-size:0.8rem;margin-bottom:0">
          ℹ Newly registered technicians will be marked <strong>Pending Approval</strong> until reviewed and approved by the Project Manager.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Submit Technician</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Technician -->
<div class="modal-backdrop" id="editTechnicianModal">
  <div class="modal" style="max-width:620px;width:95%">
    <div class="modal-header">
      <div class="modal-title"><?= svgIcon('edit') ?> Edit Technician</div>
      <button type="button" class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=edit_technician" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="technician_id" id="edit_tech_id" value="">
      <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=contractor&action=teams&tab=technicians">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" id="edit_tech_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>NIDA National ID No. <span class="required">*</span></label>
            <input type="text" name="nida_number" id="edit_tech_nida" class="form-control" required>
          </div>
        </div>

        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label>Sex / Gender <span class="required">*</span></label>
            <select name="gender" id="edit_tech_gender" class="form-control" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="form-group">
            <label>Phone Number <span class="required">*</span></label>
            <input type="text" name="phone" id="edit_tech_phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="edit_tech_email" class="form-control">
          </div>
        </div>

        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Assign to Team</label>
            <select name="team_id" id="edit_tech_team_id" class="form-control">
              <option value="">-- No Team (Unassigned) --</option>
              <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['team_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Skill Level / Role</label>
            <select name="skill_level" id="edit_tech_skill" class="form-control">
              <option value="Senior Splicing Engineer">Senior Splicing Engineer</option>
              <option value="Fiber Technician">Fiber Technician</option>
              <option value="Rigging / Tower Specialist">Rigging / Tower Specialist</option>
              <option value="CPE &amp; LAN Installer">CPE &amp; LAN Installer</option>
              <option value="Assistant Technician">Assistant Technician</option>
            </select>
          </div>
        </div>

        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Replace Photo</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
          </div>
          <div class="form-group">
            <label>Replace NIDA Card Photo</label>
            <input type="file" name="nida_photo" class="form-control" accept="image/*,application/pdf">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function openEditTeamModal(team) {
  document.getElementById('edit_team_id').value = team.id;
  document.getElementById('edit_team_name').value = team.team_name || '';
  document.getElementById('edit_leader_name').value = team.leader_name || '';
  document.getElementById('edit_leader_phone').value = team.leader_phone || '';
  document.getElementById('edit_coverage_region').value = team.coverage_region || '';
  openModal('editTeamModal');
}

function openEditTechModal(tech) {
  document.getElementById('edit_tech_id').value = tech.id;
  document.getElementById('edit_tech_name').value = tech.full_name || '';
  document.getElementById('edit_tech_nida').value = tech.nida_number || '';
  document.getElementById('edit_tech_gender').value = tech.gender || 'Male';
  document.getElementById('edit_tech_phone').value = tech.phone || '';
  document.getElementById('edit_tech_email').value = tech.email || '';
  document.getElementById('edit_tech_team_id').value = tech.team_id || '';
  document.getElementById('edit_tech_skill').value = tech.skill_level || 'Fiber Technician';
  openModal('editTechnicianModal');
}

function filterTechTable() {
  const query = document.getElementById('techSearchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#techniciansTable tbody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(query) ? '' : 'none';
  });
}
</script>
