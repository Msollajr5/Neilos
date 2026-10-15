<?php
// ============================================================
// Surveys Management Hub & Field Team Monitoring View
// ============================================================
$surveys                = $surveys ?? [];
$teamMetrics            = $teamMetrics ?? [];
$completedInstallations = $completedInstallations ?? [];
$activityFeed           = $activityFeed ?? [];
$teamsList              = $teamsList ?? [];
$ordersForSurvey        = $ordersForSurvey ?? [];
$kpiCounts              = $kpiCounts ?? ['total'=>0,'assigned'=>0,'in_progress'=>0,'completed'=>0,'approved'=>0,'feasible'=>0];
$activeTab              = $activeTab ?? 'surveys';
$statusFilter           = $statusFilter ?? '';
$teamFilter             = $teamFilter ?? 0;
$priorityFilter         = $priorityFilter ?? '';
$serviceFilter          = $serviceFilter ?? '';
$search                 = $search ?? '';
$dateStart              = $dateStart ?? '';
$dateEnd                = $dateEnd ?? '';

// Calculate overall installation count
$totalCompletedInstallations = count($completedInstallations);
?>

<style>
/* Ensure all survey cards and tables are immediately visible without being suppressed by scroll-reveal opacity */
.card.reveal, .reveal, .stat-card.reveal {
  opacity: 1 !important;
  transform: none !important;
  visibility: visible !important;
}
</style>

<div class="page-header" style="margin-bottom:20px">
  <div class="page-header-left">
    <h1 class="page-title" style="font-size:1.5rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:10px;margin-bottom:4px">
      <span>🛰️</span> <?= isContractorUser() ? 'Allocated Survey Tasks' : 'Survey Management & Field Teams' ?>
    </h1>
    <div class="page-subtitle" style="color:var(--text-secondary);font-size:0.9rem">
      <?= isContractorUser() 
          ? 'View and execute allocated survey tasks, record site feasibility, and upload findings.' 
          : 'Assign surveys to designated field teams, track conducted surveys, and monitor team installations & field performance.' ?>
    </div>
  </div>
  <div class="page-header-actions" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <?php if (!$isContractor): ?>
    <button type="button" class="btn btn-primary" onclick="openAssignSurveyModal()" style="font-weight:600">
      <?= svgIcon('plus') ?> Assign New Survey
    </button>
    <?php endif; ?>
    <div class="dropdown" style="display:inline-block">
      <a href="<?= APP_URL ?>/?page=surveys&action=export&export_type=surveys" class="btn btn-secondary" title="Export Survey Tasks to CSV">
        <?= svgIcon('download') ?> Export Surveys CSV
      </a>
    </div>
    <?php if (!$isContractor): ?>
    <a href="<?= APP_URL ?>/?page=surveys&tab=monitoring" class="btn btn-ghost" title="View Team Performance Matrix & Report">
      <?= svgIcon('chart') ?> Teams Report
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- KPI Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px;margin-bottom:24px">
  <!-- Card 1: Total Surveys -->
  <div class="card" style="padding:16px;border-left:4px solid var(--primary);box-shadow:0 1px 3px rgba(0,0,0,0.04)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:0.8rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em">Total Surveys</span>
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(15,76,129,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('map', 18) ?>
      </div>
    </div>
    <div style="font-size:1.85rem;font-weight:800;color:var(--text-primary);line-height:1"><?= count($surveys) ?></div>
    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px;display:flex;gap:8px">
      <span>Assigned: <strong><?= $kpiCounts['assigned'] ?></strong></span>
      <span>·</span>
      <span>In Progress: <strong><?= $kpiCounts['in_progress'] ?></strong></span>
    </div>
  </div>

  <!-- Card 2: Completed Surveys -->
  <div class="card" style="padding:16px;border-left:4px solid var(--success);box-shadow:0 1px 3px rgba(0,0,0,0.04)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:0.8rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em">Surveys Conducted</span>
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(16,185,129,0.1);color:var(--success);display:flex;align-items:center;justify-content:center">
        <?= svgIcon('check', 18) ?>
      </div>
    </div>
    <div style="font-size:1.85rem;font-weight:800;color:var(--success);line-height:1">
      <?= $kpiCounts['completed'] + $kpiCounts['approved'] ?>
    </div>
    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">
      <span>Approved: <strong><?= $kpiCounts['approved'] ?></strong></span> · 
      <span>Feasible: <strong style="color:var(--success)"><?= $kpiCounts['feasible'] ?></strong></span>
    </div>
  </div>

  <!-- Card 3: Completed Installations -->
  <div class="card" style="padding:16px;border-left:4px solid #8b5cf6;box-shadow:0 1px 3px rgba(0,0,0,0.04)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:0.8rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em">Completed Installations</span>
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(139,92,246,0.1);color:#8b5cf6;display:flex;align-items:center;justify-content:center">
        <?= svgIcon('project', 18) ?>
      </div>
    </div>
    <div style="font-size:1.85rem;font-weight:800;color:#8b5cf6;line-height:1"><?= $totalCompletedInstallations ?></div>
    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">
      <span>Tracked across all active field teams</span>
    </div>
  </div>

  <!-- Card 4: Active Field Teams -->
  <div class="card" style="padding:16px;border-left:4px solid #f59e0b;box-shadow:0 1px 3px rgba(0,0,0,0.04)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:0.8rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em">Designated Teams</span>
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(245,158,11,0.1);color:#f59e0b;display:flex;align-items:center;justify-content:center">
        <?= svgIcon('users', 18) ?>
      </div>
    </div>
    <div style="font-size:1.85rem;font-weight:800;color:var(--text-primary);line-height:1"><?= count($teamMetrics) ?></div>
    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">
      <span>Approved &amp; active in the field</span>
    </div>
  </div>
</div>

<!-- Tab Navigation Bar -->
<div style="display:flex;gap:6px;border-bottom:2px solid var(--border);margin-bottom:20px;overflow-x:auto;padding-bottom:2px">
  <a href="<?= APP_URL ?>/?page=surveys&tab=surveys" 
     class="btn btn-sm <?= $activeTab === 'surveys' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-weight:600;border-radius:6px 6px 0 0;padding:8px 16px;display:flex;align-items:center;gap:6px">
    <span>📋</span> All Surveys <span class="badge" style="background:rgba(0,0,0,0.1);font-size:0.75rem"><?= count($surveys) ?></span>
  </a>

  <?php if (!$isContractor): ?>
  <a href="<?= APP_URL ?>/?page=surveys&tab=monitoring" 
     class="btn btn-sm <?= $activeTab === 'monitoring' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-weight:600;border-radius:6px 6px 0 0;padding:8px 16px;display:flex;align-items:center;gap:6px">
    <span>📈</span> Team Monitoring &amp; Performance <span class="badge" style="background:rgba(0,0,0,0.1);font-size:0.75rem"><?= count($teamMetrics) ?></span>
  </a>
  <?php endif; ?>

  <a href="<?= APP_URL ?>/?page=surveys&tab=surveys_by_team" 
     class="btn btn-sm <?= $activeTab === 'surveys_by_team' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-weight:600;border-radius:6px 6px 0 0;padding:8px 16px;display:flex;align-items:center;gap:6px">
    <span>🛰️</span> Surveys Conducted by Team
  </a>

  <a href="<?= APP_URL ?>/?page=surveys&tab=installations_by_team" 
     class="btn btn-sm <?= $activeTab === 'installations_by_team' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-weight:600;border-radius:6px 6px 0 0;padding:8px 16px;display:flex;align-items:center;gap:6px">
    <span>🛠️</span> Installations Completed by Team <span class="badge" style="background:rgba(0,0,0,0.1);font-size:0.75rem"><?= $totalCompletedInstallations ?></span>
  </a>

  <a href="<?= APP_URL ?>/?page=surveys&tab=activity" 
     class="btn btn-sm <?= $activeTab === 'activity' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-weight:600;border-radius:6px 6px 0 0;padding:8px 16px;display:flex;align-items:center;gap:6px">
    <span>⚡</span> Field Activity Feed
  </a>
</div>

<?php // ============================================================ ?>
<?php // TAB 1: ALL SURVEYS DIRECTORY & FILTERS                       ?>
<?php // ============================================================ ?>
<?php if ($activeTab === 'surveys'): ?>

  <!-- Filters Bar -->
  <div class="card" style="padding:16px;margin-bottom:20px;background:var(--surface)">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="page" value="surveys">
      <input type="hidden" name="tab" value="surveys">

      <div style="flex:2;min-width:180px">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:4px">Search</label>
        <div style="position:relative">
          <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search customer, location, survey #..." class="form-control" style="font-size:0.875rem">
        </div>
      </div>

      <div style="flex:1;min-width:140px">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:4px">Status</label>
        <select name="status" class="form-control" style="font-size:0.875rem">
          <option value="">All Statuses</option>
          <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
          <option value="Accepted" <?= $statusFilter === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
          <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
          <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
          <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
          <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>

      <?php if (!$isContractor): ?>
      <div style="flex:1;min-width:170px">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:4px">Designated Team</label>
        <select name="team_id" class="form-control" style="font-size:0.875rem">
          <option value="">All Teams</option>
          <?php foreach ($teamsList as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $teamFilter === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['team_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div style="flex:1;min-width:120px">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:4px">Priority</label>
        <select name="priority" class="form-control" style="font-size:0.875rem">
          <option value="">All Priorities</option>
          <option value="Urgent" <?= $priorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
          <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High</option>
          <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium</option>
          <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary" style="padding:8px 16px">
          <?= svgIcon('search', 14) ?> Filter
        </button>
        <?php if ($search || $statusFilter || $teamFilter || $priorityFilter): ?>
        <a href="<?= APP_URL ?>/?page=surveys&tab=surveys" class="btn btn-secondary" title="Clear Filters" style="display:inline-flex;align-items:center;gap:4px">
          <?= svgIcon('x', 14) ?> Clear
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Surveys Table -->
  <div class="card" style="overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
    <div style="overflow-x:auto">
      <table class="table" style="width:100%;margin:0;font-size:0.875rem">
        <thead>
          <tr style="background:var(--surface-hover);border-bottom:1px solid var(--border)">
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:left">Survey #</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:left">Customer &amp; Location</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:left">Service</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:left">Designated Team</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:center">Priority</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:center">Due Date</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:center">Status</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:center">Feasibility</th>
            <th style="padding:12px 16px;font-weight:700;color:var(--text-secondary);text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($surveys)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:48px 16px;color:var(--text-muted)">
              <div style="font-size:2rem;margin-bottom:8px">🔍</div>
              <div style="font-weight:600;font-size:1rem;color:var(--text-primary)">No survey tasks found</div>
              <div style="font-size:0.85rem;margin-top:4px">No surveys matched your filter criteria.</div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($surveys as $s): ?>
          <?php
            $pBadgeClass = match($s['priority']) {
                'Urgent' => 'background:#fee2e2;color:#991b1b;border:1px solid #fecaca',
                'High'   => 'background:#ffedd5;color:#9a3412;border:1px solid #fed7aa',
                'Medium' => 'background:#e0f2fe;color:#075985;border:1px solid #bae6fd',
                default  => 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0',
            };

            $statusBadgeClass = match($s['status']) {
                'Approved'    => 'background:#d1fae5;color:#065f46;border:1px solid #a7f3d0',
                'Completed'   => 'background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe',
                'In Progress' => 'background:#fef3c7;color:#92400e;border:1px solid #fde68a',
                'Accepted'    => 'background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd',
                'Rejected'    => 'background:#fee2e2;color:#991b1b;border:1px solid #fecaca',
                default       => 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1',
            };

            $feasClass = match($s['feasibility_result'] ?? '') {
                'Feasible'                      => 'background:#d1fae5;color:#065f46',
                'Feasible with Extension/Civils'=> 'background:#fef3c7;color:#92400e',
                'Not Feasible'                  => 'background:#fee2e2;color:#991b1b',
                default                         => 'background:#f8fafc;color:#94a3b8',
            };
          ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:14px 16px;white-space:nowrap">
              <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $s['id'] ?>" style="font-weight:700;color:var(--primary);text-decoration:none">
                <?= e($s['survey_number']) ?>
              </a>
              <?php if (!empty($s['order_id'])): ?>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px">
                Order: #<?= e($s['order_id']) ?>
              </div>
              <?php endif; ?>
            </td>
            <td style="padding:14px 16px">
              <div style="font-weight:600;color:var(--text-primary)"><?= e($s['customer_name']) ?></div>
              <div style="font-size:0.78rem;color:var(--text-secondary);display:flex;align-items:center;gap:4px;margin-top:2px">
                <span>📍</span> <?= e($s['site_location']) ?><?= $s['building_name'] ? ' (' . e($s['building_name']) . ')' : '' ?>
              </div>
            </td>
            <td style="padding:14px 16px;white-space:nowrap">
              <span class="badge" style="background:var(--surface-hover);color:var(--text-primary);font-weight:600;font-size:0.75rem">
                <?= e($s['service_type']) ?>
              </span>
            </td>
            <td style="padding:14px 16px">
              <div style="font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px">
                <span>👷</span> <?= e($s['team_name']) ?>
              </div>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px">
                <?= e($s['leader_name'] ?: 'Team Lead') ?> · <?= e($s['leader_phone'] ?: 'N/A') ?>
              </div>
            </td>
            <td style="padding:14px 16px;text-align:center;white-space:nowrap">
              <span class="badge" style="<?= $pBadgeClass ?>;font-weight:700;font-size:0.75rem;padding:3px 8px;border-radius:4px">
                <?= e($s['priority']) ?>
              </span>
            </td>
            <td style="padding:14px 16px;text-align:center;white-space:nowrap;font-size:0.8rem">
              <?= $s['target_date'] ? fmtDate($s['target_date']) : '<span style="color:var(--text-muted)">—</span>' ?>
            </td>
            <td style="padding:14px 16px;text-align:center;white-space:nowrap">
              <span class="badge" style="<?= $statusBadgeClass ?>;font-weight:700;font-size:0.75rem;padding:3px 10px;border-radius:4px">
                <?= e($s['status']) ?>
              </span>
            </td>
            <td style="padding:14px 16px;text-align:center;white-space:nowrap">
              <?php if (!empty($s['feasibility_result'])): ?>
                <span class="badge" style="<?= $feasClass ?>;font-weight:700;font-size:0.72rem;padding:2px 8px;border-radius:4px">
                  <?= e($s['feasibility_result']) ?>
                </span>
                <?php if ($s['fiber_distance_meters']): ?>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px"><?= number_format($s['fiber_distance_meters'], 1) ?>m fiber</div>
                <?php endif; ?>
              <?php else: ?>
                <span style="font-size:0.75rem;color:var(--text-muted)">Pending Site Visit</span>
              <?php endif; ?>
            </td>
            <td style="padding:14px 16px;text-align:right;white-space:nowrap">
              <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" style="font-weight:600">
                <?= svgIcon('eye', 13) ?> View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>

<?php // ============================================================ ?>
<?php // TAB 2: TEAM MONITORING & PERFORMANCE MATRIX                  ?>
<?php // ============================================================ ?>
<?php if ($activeTab === 'monitoring' && !$isContractor): ?>

  <div class="card" style="margin-bottom:20px;padding:18px;background:var(--surface)">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div>
        <h2 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0">Field Teams Performance &amp; Activity Matrix</h2>
        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">Real-time overview of surveys conducted, installations completed, and active workload by designated field team.</div>
      </div>
      <a href="<?= APP_URL ?>/?page=surveys&action=export&export_type=teams" class="btn btn-secondary btn-sm">
        <?= svgIcon('download', 14) ?> Download Matrix CSV
      </a>
    </div>

    <div style="overflow-x:auto">
      <table class="table" style="width:100%;margin:0;font-size:0.875rem">
        <thead>
          <tr style="background:var(--surface-hover);border-bottom:1px solid var(--border)">
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary)">Field Team</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary)">Contractor Org</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary)">Team Lead &amp; Contact</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary)">Region</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary);text-align:center">Surveys Conducted</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary);text-align:center">Feasibility Rate</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary);text-align:center">Completed Installations</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary);text-align:center">Active Workload</th>
            <th style="padding:12px 14px;font-weight:700;color:var(--text-secondary);text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teamMetrics as $tm): ?>
          <?php
            $surveysTotal = (int)$tm['total_surveys_assigned'];
            $surveysDone  = (int)$tm['surveys_completed'];
            $surveysFeas  = (int)$tm['surveys_feasible'];
            $instDone     = (int)$tm['installations_completed'];
            $activeLoad   = (int)$tm['surveys_in_progress'] + (int)$tm['installations_in_progress'];
            $feasRate     = ($surveysDone > 0) ? round(($surveysFeas / $surveysDone) * 100) : 0;
          ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:14px;font-weight:700;color:var(--text-primary)">
              <div style="display:flex;align-items:center;gap:8px">
                <div style="width:30px;height:30px;border-radius:6px;background:rgba(15,76,129,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.8rem">
                  FT
                </div>
                <div>
                  <?= e($tm['team_name']) ?>
                  <div style="font-size:0.75rem;color:var(--text-muted);font-weight:400">
                    <?= (int)$tm['technician_count'] ?> Certified Technicians
                  </div>
                </div>
              </div>
            </td>
            <td style="padding:14px;color:var(--text-secondary);font-weight:500">
              <?= e($tm['contractor_name']) ?>
            </td>
            <td style="padding:14px">
              <div style="font-weight:600;color:var(--text-primary)"><?= e($tm['leader_name'] ?: 'Not assigned') ?></div>
              <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px">
                📞 <?= e($tm['leader_phone'] ?: 'No phone') ?>
              </div>
            </td>
            <td style="padding:14px;color:var(--text-secondary);font-size:0.82rem">
              📍 <?= e($tm['coverage_region'] ?: 'General') ?>
            </td>
            <td style="padding:14px;text-align:center">
              <div style="font-size:1.05rem;font-weight:800;color:var(--primary)">
                <?= $surveysDone ?> <span style="font-size:0.75rem;color:var(--text-muted);font-weight:500">/ <?= $surveysTotal ?></span>
              </div>
              <div style="font-size:0.72rem;color:var(--text-muted)">Surveys</div>
            </td>
            <td style="padding:14px;text-align:center">
              <span class="badge" style="background:<?= $feasRate >= 80 ? '#d1fae5;color:#065f46' : ($feasRate >= 50 ? '#fef3c7;color:#92400e' : '#f1f5f9;color:#475569') ?>;font-weight:800;padding:3px 8px;border-radius:4px">
                <?= $feasRate ?>%
              </span>
              <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px"><?= $surveysFeas ?> Feasible</div>
            </td>
            <td style="padding:14px;text-align:center">
              <div style="font-size:1.05rem;font-weight:800;color:#8b5cf6">
                <?= $instDone ?>
              </div>
              <div style="font-size:0.72rem;color:var(--text-muted)">Installations</div>
            </td>
            <td style="padding:14px;text-align:center">
              <?php if ($activeLoad > 0): ?>
                <span class="badge" style="background:#fef3c7;color:#b45309;font-weight:700;padding:3px 8px;border-radius:4px">
                  ⚡ <?= $activeLoad ?> In Flight
                </span>
              <?php else: ?>
                <span class="badge" style="background:#d1fae5;color:#065f46;font-weight:600;padding:3px 8px;border-radius:4px">
                  Available
                </span>
              <?php endif; ?>
            </td>
            <td style="padding:14px;text-align:right">
              <button type="button" class="btn btn-secondary btn-sm" onclick="openAssignSurveyModal(<?= (int)$tm['team_id'] ?>)" style="font-weight:600">
                <?= svgIcon('plus', 12) ?> Assign Survey
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>

<?php // ============================================================ ?>
<?php // TAB 3: SURVEYS CONDUCTED BY TEAM                             ?>
<?php // ============================================================ ?>
<?php if ($activeTab === 'surveys_by_team'): ?>

  <div style="margin-bottom:20px">
    <h2 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0 0 4px">Surveys Conducted by Designated Field Teams</h2>
    <div style="font-size:0.85rem;color:var(--text-secondary)">Audit list of technical site feasibility assessments completed by each team.</div>
  </div>

  <div style="display:flex;flex-direction:column;gap:20px">
    <?php foreach ($teamMetrics as $tm): ?>
    <?php
      $teamId = (int)$tm['team_id'];
      // Filter surveys conducted by this team
      $teamSurveys = array_filter($surveys, fn($s) => (int)$s['contractor_team_id'] === $teamId);
    ?>
    <div class="card" style="padding:0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04)">
      <!-- Team Header Banner -->
      <div style="background:var(--surface-hover);padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:1.2rem">👷</span>
          <div>
            <div style="font-weight:700;font-size:1rem;color:var(--text-primary)">
              <?= e($tm['team_name']) ?>
              <span class="badge badge-primary" style="font-size:0.75rem;padding:2px 8px;margin-left:6px"><?= e($tm['contractor_name']) ?></span>
            </div>
            <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:2px">
              Leader: <strong><?= e($tm['leader_name'] ?: 'N/A') ?></strong> (<?= e($tm['leader_phone'] ?: 'No Phone') ?>) · Coverage: <?= e($tm['coverage_region'] ?: 'All Regions') ?>
            </div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="font-size:0.82rem;color:var(--text-secondary)">
            Conducted: <strong style="color:var(--primary)"><?= (int)$tm['surveys_completed'] ?></strong> · 
            Feasible: <strong style="color:var(--success)"><?= (int)$tm['surveys_feasible'] ?></strong>
          </div>
          <?php if (!$isContractor): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="openAssignSurveyModal(<?= $teamId ?>)">
            <?= svgIcon('plus', 12) ?> Assign Survey
          </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Team Surveys Table -->
      <div style="padding:0;overflow-x:auto">
        <table class="table" style="width:100%;margin:0;font-size:0.85rem">
          <thead>
            <tr style="background:#fafafa;border-bottom:1px solid var(--border)">
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Survey #</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Customer &amp; Site</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Service</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Survey Date</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Nearest DP / Splitter</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600">Fiber Distance</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600;text-align:center">Verdict</th>
              <th style="padding:10px 16px;color:var(--text-muted);font-weight:600;text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($teamSurveys)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted)">
                No surveys assigned or conducted yet by this team.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($teamSurveys as $ts): ?>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:12px 16px;white-space:nowrap">
                <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $ts['id'] ?>" style="font-weight:700;color:var(--primary);text-decoration:none">
                  <?= e($ts['survey_number']) ?>
                </a>
              </td>
              <td style="padding:12px 16px">
                <div style="font-weight:600;color:var(--text-primary)"><?= e($ts['customer_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= e($ts['site_location']) ?></div>
              </td>
              <td style="padding:12px 16px;white-space:nowrap">
                <?= e($ts['service_type']) ?>
              </td>
              <td style="padding:12px 16px;white-space:nowrap">
                <?= $ts['survey_date'] ? fmtDate($ts['survey_date']) : fmtDate($ts['assigned_at']) ?>
              </td>
              <td style="padding:12px 16px">
                <?= e($ts['nearest_dp_or_splitter'] ?: '—') ?>
              </td>
              <td style="padding:12px 16px;white-space:nowrap">
                <?= $ts['fiber_distance_meters'] ? number_format($ts['fiber_distance_meters'], 1) . ' m' : '—' ?>
              </td>
              <td style="padding:12px 16px;text-align:center;white-space:nowrap">
                <?php if ($ts['feasibility_result']): ?>
                  <span class="badge" style="<?= match($ts['feasibility_result']) {
                    'Feasible' => 'background:#d1fae5;color:#065f46',
                    'Feasible with Extension/Civils' => 'background:#fef3c7;color:#92400e',
                    default => 'background:#fee2e2;color:#991b1b'
                  } ?>;font-weight:700;font-size:0.72rem;padding:2px 8px">
                    <?= e($ts['feasibility_result']) ?>
                  </span>
                <?php else: ?>
                  <span class="badge badge-secondary" style="font-size:0.72rem"><?= e($ts['status']) ?></span>
                <?php endif; ?>
              </td>
              <td style="padding:12px 16px;text-align:right;white-space:nowrap">
                <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $ts['id'] ?>" class="btn btn-secondary btn-sm">
                  <?= svgIcon('eye', 12) ?> View Report
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php // ============================================================ ?>
<?php // TAB 4: INSTALLATIONS COMPLETED BY TEAM                       ?>
<?php // ============================================================ ?>
<?php if ($activeTab === 'installations_by_team'): ?>

  <div class="card" style="padding:18px;margin-bottom:20px;background:var(--surface)">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div>
        <h2 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0">Completed Installations Tracked by Field Team</h2>
        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">Detailed record of successfully deployed client installations, fiber drops, and verified customer locations.</div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <input type="text" id="installSearchInput" placeholder="Quick search order, customer, location..." class="form-control" style="font-size:0.82rem;width:260px;padding:6px 12px" onkeyup="filterInstallationsTable()">
        <span class="badge" style="background:rgba(139,92,246,0.12);color:#8b5cf6;font-size:0.82rem;font-weight:700;padding:6px 12px;border-radius:6px">
          <?= count($completedInstallations) ?> Total Completed
        </span>
      </div>
    </div>

    <div style="overflow-x:auto">
      <table class="table" style="width:100%;margin:0;font-size:0.875rem">
        <thead>
          <tr style="background:var(--surface-hover);border-bottom:1px solid var(--border)">
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700">Order #</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700">Customer &amp; Location</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700">Service Type</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700">Designated Team</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700">Contractor Org</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700;text-align:center">Completion Date</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700;text-align:center">Order Status</th>
            <th style="padding:12px 14px;color:var(--text-secondary);font-weight:700;text-align:right">Action</th>
          </tr>
        </thead>
        <tbody id="installationsTableBody">
          <?php if (empty($completedInstallations)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">
              No completed installations recorded yet for any team.
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($completedInstallations as $ci): ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:14px;white-space:nowrap">
              <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $ci['order_id'] ?>" style="font-weight:700;color:var(--primary);text-decoration:none">
                <?= e($ci['order_number']) ?>
              </a>
              <?php if (!empty($ci['circuit_id'])): ?>
              <div style="font-size:0.72rem;color:var(--text-muted)"><?= e($ci['circuit_id']) ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:14px">
              <div style="font-weight:600;color:var(--text-primary)"><?= e($ci['customer_name']) ?></div>
              <div style="font-size:0.78rem;color:var(--text-secondary)">
                📍 <?= e($ci['pickup_location'] ?: 'Customer Premise') ?>
              </div>
            </td>
            <td style="padding:14px;white-space:nowrap">
              <span class="badge" style="background:var(--surface-hover);color:var(--text-primary);font-weight:600;font-size:0.75rem">
                <?= e($ci['service_type']) ?>
              </span>
            </td>
            <td style="padding:14px;white-space:nowrap">
              <div style="font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:6px">
                <span>👷</span> <?= e($ci['team_name']) ?>
              </div>
              <div style="font-size:0.72rem;color:var(--text-muted)">Lead: <?= e($ci['leader_name'] ?: 'N/A') ?></div>
            </td>
            <td style="padding:14px;color:var(--text-secondary)">
              <?= e($ci['contractor_name']) ?>
            </td>
            <td style="padding:14px;text-align:center;white-space:nowrap;font-size:0.82rem">
              <?= $ci['completed_at'] ? fmtDate($ci['completed_at']) : ($ci['installation_date'] ? fmtDate($ci['installation_date']) : '—') ?>
            </td>
            <td style="padding:14px;text-align:center;white-space:nowrap">
              <span class="badge" style="background:#d1fae5;color:#065f46;font-weight:700;font-size:0.75rem;padding:3px 8px">
                <?= e($ci['order_status']) ?>
              </span>
            </td>
            <td style="padding:14px;text-align:right;white-space:nowrap">
              <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $ci['order_id'] ?>" class="btn btn-secondary btn-sm">
                <?= svgIcon('eye', 12) ?> View Order
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  function filterInstallationsTable() {
    var input = document.getElementById('installSearchInput');
    var filter = input ? input.value.toLowerCase() : '';
    var rows = document.querySelectorAll('#installationsTableBody tr');
    rows.forEach(function(row) {
      var text = row.textContent.toLowerCase();
      row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
    });
  }
  </script>

<?php endif; ?>

<?php // ============================================================ ?>
<?php // TAB 5: LIVE FIELD ACTIVITY FEED                             ?>
<?php // ============================================================ ?>
<?php if ($activeTab === 'activity'): ?>

  <div class="card" style="padding:20px;margin-bottom:20px;background:var(--surface)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div>
        <h2 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin:0">Live Field Teams Activity Feed</h2>
        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">Real-time audit log of team check-ins, site surveys, progress milestones, and installation submissions.</div>
      </div>
    </div>

    <div style="position:relative;padding-left:24px;border-left:2px solid var(--border)">
      <?php if (empty($activityFeed)): ?>
      <p style="color:var(--text-muted);font-size:0.875rem">No recent activity logged.</p>
      <?php else: ?>
      <?php foreach ($activityFeed as $af): ?>
      <?php 
        $isSurvey = ($af['item_type'] === 'survey');
        $bulletColor = $isSurvey ? 'var(--primary)' : '#8b5cf6';
        $iconEmoji = $isSurvey ? '🛰️' : '🛠️';
      ?>
      <div style="position:relative;margin-bottom:20px">
        <!-- Bullet dot -->
        <div style="position:absolute;left:-31px;top:2px;width:12px;height:12px;border-radius:50%;background:<?= $bulletColor ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $bulletColor ?>"></div>
        
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div style="font-weight:700;font-size:0.9rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
            <span><?= $iconEmoji ?></span>
            <?= $isSurvey ? 'Survey' : 'Installation' ?> 
            <a href="<?= APP_URL ?>/?page=<?= $isSurvey ? 'surveys&action=detail' : 'order_detail' ?>&id=<?= (int)$af['ref_id'] ?>" style="color:<?= $bulletColor ?>;text-decoration:none;font-weight:700">
              <?= e($af['ref_code']) ?>
            </a> 
            — <?= e($af['customer_name']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:0.75rem;color:var(--text-muted)">
              <?= fmtDateTime($af['created_at']) ?>
            </span>
            <a href="<?= APP_URL ?>/?page=<?= $isSurvey ? 'surveys&action=detail' : 'order_detail' ?>&id=<?= (int)$af['ref_id'] ?>" class="btn btn-secondary btn-sm" style="font-size:0.75rem;padding:3px 8px;font-weight:600">
              <?= svgIcon('eye', 11) ?> View
            </a>
          </div>
        </div>

        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:4px;line-height:1.5">
          <?= e($af['note']) ?>
        </div>

        <div style="display:flex;align-items:center;gap:8px;font-size:0.75rem;color:var(--text-muted);margin-top:4px">
          <span>Team: <strong><?= e($af['team_name']) ?></strong></span>
          <span>·</span>
          <span>Logged by: <?= e($af['actor_name'] ?: 'System') ?></span>
          <span>·</span>
          <span class="badge" style="font-size:0.7rem"><?= e($af['status']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>


<?php // ============================================================ ?>
<?php // MODAL: ASSIGN NEW SURVEY TASK (PROJECT MANAGER)              ?>
<?php // ============================================================ ?>
<?php if (!$isContractor): ?>
<style>
#assignSurveyModal.open {
  display: flex !important;
  opacity: 1 !important;
  pointer-events: all !important;
  visibility: visible !important;
}
#assignSurveyModal {
  display: none;
  opacity: 0;
  pointer-events: none;
  visibility: hidden;
  position: fixed !important;
  inset: 0 !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  background: rgba(15, 23, 42, 0.7) !important;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 99999 !important;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
  box-sizing: border-box;
}
#assignSurveyModal .modal-content-card {
  width: 100%;
  max-width: 720px;
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
  margin: auto;
  position: relative;
}
</style>
<div class="modal-backdrop" id="assignSurveyModal" onclick="if(event.target===this)closeAssignSurveyModal()">
  <div class="modal-content-card" onclick="event.stopPropagation()">
    <div style="padding:16px 20px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:1.3rem">🛰️</span>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#fff">Assign Survey Task to Field Team</h3>
          <div style="font-size:0.78rem;opacity:0.9">Project Manager Task Dispatch System</div>
        </div>
      </div>
      <button type="button" onclick="closeAssignSurveyModal()" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;padding:4px 8px">&times;</button>
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=surveys&action=create" style="padding:20px">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <!-- Order Selection (Optional) -->
      <div class="form-group" style="margin-bottom:14px">
        <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Link to Service Order (Optional)</label>
        <select name="order_id" id="modalOrderId" class="form-control" onchange="autoFillOrderDetails(this)">
          <option value="">-- Standalone Site Survey (No Order Link) --</option>
          <?php foreach ($ordersForSurvey as $of): ?>
          <option value="<?= $of['id'] ?>" 
                  data-customer="<?= e($of['customer_name']) ?>"
                  data-location="<?= e($of['customer_location']) ?>"
                  data-service="<?= e($of['service_type']) ?>">
            Order #<?= e($of['order_number']) ?> — <?= e($of['customer_name']) ?> (<?= e($of['service_type']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px">Selecting an order pre-fills customer and location details.</div>
      </div>

      <!-- Designated Field Team -->
      <div class="form-group" style="margin-bottom:14px">
        <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">
          Designate Field Team <span class="required" style="color:red">*</span>
        </label>
        <select name="contractor_team_id" id="modalTeamId" class="form-control" required>
          <option value="">-- Select Designated Field Team --</option>
          <?php foreach ($teamsList as $t): ?>
          <option value="<?= $t['id'] ?>">
            👷 <?= e($t['team_name']) ?> (Lead: <?= e($t['leader_name'] ?: 'N/A') ?> · <?= e($t['coverage_region'] ?: 'General') ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Customer / Site Name <span class="required" style="color:red">*</span></label>
          <input type="text" name="customer_name" id="modalCustomerName" class="form-control" placeholder="e.g. Twiga Cement HQ" required>
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Service Type <span class="required" style="color:red">*</span></label>
          <select name="service_type" id="modalServiceType" class="form-control" required>
            <option value="FTTH">FTTH (Fiber to the Home)</option>
            <option value="FTTB">FTTB (Fiber to the Building)</option>
            <option value="DIA (Dedicated Internet Access)">DIA (Dedicated Internet Access)</option>
            <option value="Dedicated Layer 2">Dedicated Layer 2</option>
            <option value="Layer 2 ( last mile)">Layer 2 (last mile)</option>
            <option value="BIA (Broadband Internet Access)">BIA (Broadband Internet Access)</option>
            <option value="Remote Hands Only">Remote Hands Only</option>
          </select>
        </div>
      </div>

      <div class="form-grid form-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Contact Person</label>
          <input type="text" name="customer_contact_name" class="form-control" placeholder="Site contact name">
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Contact Phone</label>
          <input type="text" name="customer_contact_phone" class="form-control" placeholder="+255 7XX XXX XXX">
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Contact Email</label>
          <input type="email" name="customer_contact_email" class="form-control" placeholder="client@company.co.tz">
        </div>
      </div>

      <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:14px">
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Site Physical Address / Street <span class="required" style="color:red">*</span></label>
          <input type="text" name="site_location" id="modalSiteLocation" class="form-control" placeholder="Plot / Street / Neighborhood" required>
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Building Name</label>
          <input type="text" name="building_name" class="form-control" placeholder="Building / Tower">
        </div>
      </div>

      <div class="form-grid form-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">City / Region</label>
          <input type="text" name="city_region" class="form-control" value="Dar es Salaam">
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">GPS Coordinates</label>
          <input type="text" name="gps_coordinates" class="form-control" placeholder="-6.7835, 39.2685">
        </div>
        <div class="form-group">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Target Due Date <span class="required" style="color:red">*</span></label>
          <input type="date" name="target_date" class="form-control" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:14px">
        <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Priority</label>
        <div style="display:flex;gap:16px">
          <label style="font-size:0.85rem;display:flex;align-items:center;gap:4px">
            <input type="radio" name="priority" value="Medium" checked> Medium
          </label>
          <label style="font-size:0.85rem;display:flex;align-items:center;gap:4px">
            <input type="radio" name="priority" value="High"> High
          </label>
          <label style="font-size:0.85rem;display:flex;align-items:center;gap:4px;color:#dc2626;font-weight:700">
            <input type="radio" name="priority" value="Urgent"> Urgent (24h)
          </label>
          <label style="font-size:0.85rem;display:flex;align-items:center;gap:4px;color:var(--text-muted)">
            <input type="radio" name="priority" value="Low"> Low
          </label>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:20px">
        <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Scope of Survey &amp; Instructions for Field Team</label>
        <textarea name="scope_notes" class="form-control" rows="3" placeholder="e.g. Verify nearest DP port availability, optical power level, cable route clearance, and server room ingress..."></textarea>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border);padding-top:14px">
        <button type="button" class="btn btn-secondary" onclick="closeAssignSurveyModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="font-weight:700">
          <?= svgIcon('check') ?> Assign Survey Task
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openAssignSurveyModal(preselectedTeamId) {
  var modal = document.getElementById('assignSurveyModal');
  if (modal) {
    modal.classList.add('open');
    modal.style.display = 'flex';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'all';
    modal.style.visibility = 'visible';
    document.body.style.overflow = 'hidden';
    if (preselectedTeamId) {
      var sel = document.getElementById('modalTeamId');
      if (sel) sel.value = preselectedTeamId;
    }
  }
}
function closeAssignSurveyModal() {
  var modal = document.getElementById('assignSurveyModal');
  if (modal) {
    modal.classList.remove('open');
    modal.style.display = 'none';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    modal.style.visibility = 'hidden';
    document.body.style.overflow = '';
  }
}
function autoFillOrderDetails(selectElem) {
  var opt = selectElem.options[selectElem.selectedIndex];
  if (opt && opt.value) {
    var cust = opt.getAttribute('data-customer');
    var loc = opt.getAttribute('data-location');
    var svc = opt.getAttribute('data-service');
    if (cust) document.getElementById('modalCustomerName').value = cust;
    if (loc) document.getElementById('modalSiteLocation').value = loc;
    if (svc) {
      var svcSel = document.getElementById('modalServiceType');
      for (var i = 0; i < svcSel.options.length; i++) {
        if (svcSel.options[i].value === svc) {
          svcSel.selectedIndex = i;
          break;
        }
      }
    }
  }
}
window.addEventListener('click', function(e) {
  var modal = document.getElementById('assignSurveyModal');
  if (e.target === modal) {
    closeAssignSurveyModal();
  }
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAssignSurveyModal();
  }
});
</script>
<?php endif; ?>
