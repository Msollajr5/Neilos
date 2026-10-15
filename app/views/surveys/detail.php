<?php
// ============================================================
// Survey Detail & Technical Findings View
// ============================================================
$survey        = $survey ?? [];
$evidenceFiles = $evidenceFiles ?? [];
$timeline      = $timeline ?? [];
$allTeams      = $allTeams ?? [];
$isPM          = isProjectManager();
$isAdminUser   = isAdmin() || hasRole('Management');
$isContractor  = isContractorUser();

$status = $survey['status'] ?? 'Assigned';
$priority = $survey['priority'] ?? 'Medium';

$statusColor = match($status) {
    'Approved'    => '#10b981',
    'Completed'   => '#6366f1',
    'In Progress' => '#f59e0b',
    'Accepted'    => '#0284c7',
    'Rejected'    => '#ef4444',
    default       => '#64748b',
};

$priorityColor = match($priority) {
    'Urgent' => '#ef4444',
    'High'   => '#f97316',
    'Medium' => '#0284c7',
    default  => '#64748b',
};
?>

<!-- Header Bar -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
  <div>
    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px">
      <a href="<?= APP_URL ?>/?page=surveys" style="color:var(--primary);text-decoration:none">Surveys</a> &rsaquo; 
      <span><?= e($survey['survey_number']) ?></span>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin:0">
        Survey <?= e($survey['survey_number']) ?>
      </h1>
      <span class="badge" style="background:<?= $statusColor ?>;color:#fff;font-weight:700;font-size:0.8rem;padding:4px 10px;border-radius:4px">
        <?= e($status) ?>
      </span>
      <span class="badge" style="background:rgba(0,0,0,0.06);color:<?= $priorityColor ?>;border:1px solid <?= $priorityColor ?>;font-weight:700;font-size:0.75rem;padding:3px 8px;border-radius:4px">
        Priority: <?= e($priority) ?>
      </span>
      <?php if (!empty($survey['order_id'])): ?>
      <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $survey['order_id'] ?>" class="badge badge-info" style="font-size:0.75rem;padding:3px 8px;text-decoration:none">
        Linked Order #<?= e($survey['order_number'] ?: $survey['order_id']) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:flex;gap:8px;align-items:center">
    <a href="<?= APP_URL ?>/?page=surveys" class="btn btn-secondary btn-sm">
      &larr; Back to Surveys
    </a>
    <?php if (!empty($survey['gps_coordinates'])): ?>
    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($survey['gps_coordinates']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="color:var(--primary)">
      <?= svgIcon('map', 14) ?> View on Google Maps
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Main 2-Column Content Layout -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

  <!-- LEFT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Card 1: Site & Customer Information -->
    <div class="card" style="padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:10px">
        <div style="font-weight:700;font-size:1rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
          <span>📍</span> Site &amp; Customer Information
        </div>
        <span class="badge" style="background:var(--surface-hover);font-weight:600;font-size:0.75rem">
          Service: <?= e($survey['service_type']) ?>
        </span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:0.875rem">
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Customer Name</div>
          <div style="font-weight:700;color:var(--text-primary);font-size:1rem;margin-top:2px"><?= e($survey['customer_name']) ?></div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Target Survey Date</div>
          <div style="font-weight:600;color:var(--text-primary);margin-top:2px">
            <?= $survey['target_date'] ? fmtDate($survey['target_date']) : '<span style="color:var(--text-muted)">Flexible</span>' ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Site Physical Address</div>
          <div style="color:var(--text-primary);margin-top:2px;font-weight:500">
            <?= e($survey['site_location']) ?>
            <?php if ($survey['building_name']): ?>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Building: <?= e($survey['building_name']) ?><?= $survey['floor_number'] ? ' (Floor: ' . e($survey['floor_number']) . ')' : '' ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">City &amp; Region</div>
          <div style="color:var(--text-primary);margin-top:2px">
            <?= e($survey['city_region'] ?: 'Dar es Salaam') ?>
          </div>
        </div>

        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Customer Contact Person</div>
          <div style="color:var(--text-primary);margin-top:2px;font-weight:600">
            <?= e($survey['customer_contact_name'] ?: 'N/A') ?>
          </div>
          <?php if ($survey['customer_contact_phone']): ?>
          <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px">
            📞 <a href="tel:<?= e($survey['customer_contact_phone']) ?>" style="color:inherit"><?= e($survey['customer_contact_phone']) ?></a>
          </div>
          <?php endif; ?>
          <?php if ($survey['customer_contact_email']): ?>
          <div style="font-size:0.8rem;color:var(--text-secondary)">
            ✉️ <?= e($survey['customer_contact_email']) ?>
          </div>
          <?php endif; ?>
        </div>

        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">GPS Coordinates</div>
          <div style="color:var(--text-primary);margin-top:2px;display:flex;align-items:center;gap:6px">
            <?= e($survey['gps_coordinates'] ?: 'Pending field survey') ?>
            <?php if ($survey['gps_coordinates']): ?>
            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($survey['gps_coordinates']) ?>" target="_blank" style="color:var(--primary);font-size:0.8rem;text-decoration:none">
              [Open Map]
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($survey['scope_notes'])): ?>
      <div style="margin-top:16px;background:var(--surface-hover);padding:12px 14px;border-radius:6px;border-left:3px solid var(--primary)">
        <div style="font-size:0.78rem;font-weight:700;color:var(--primary);margin-bottom:4px">PM Scope Instructions for Field Team:</div>
        <div style="font-size:0.875rem;color:var(--text-secondary);line-height:1.5"><?= nl2br(e($survey['scope_notes'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Card 2: Technical Survey Findings Report (if conducted) -->
    <?php if (in_array($status, ['Completed', 'Approved', 'Rejected']) || !empty($survey['feasibility_result'])): ?>
    <div class="card" style="padding:20px;border-top:4px solid <?= $statusColor ?>;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:10px">
        <div style="font-weight:700;font-size:1.05rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
          <span>📋</span> Technical Feasibility Findings
        </div>
        <div>
          <span class="badge" style="<?= match($survey['feasibility_result']) {
            'Feasible' => 'background:#d1fae5;color:#065f46',
            'Feasible with Extension/Civils' => 'background:#fef3c7;color:#92400e',
            default => 'background:#fee2e2;color:#991b1b'
          } ?>;font-weight:800;font-size:0.82rem;padding:4px 10px;border-radius:4px">
            <?= e($survey['feasibility_result']) ?>
          </span>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;font-size:0.875rem;margin-bottom:16px">
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600">Nearest DP / Splitter ID</div>
          <div style="font-weight:700;color:var(--text-primary);margin-top:2px"><?= e($survey['nearest_dp_or_splitter'] ?: '—') ?></div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600">Measured Fiber Distance</div>
          <div style="font-weight:800;color:var(--primary);margin-top:2px;font-size:1rem">
            <?= $survey['fiber_distance_meters'] ? number_format($survey['fiber_distance_meters'], 1) . ' meters' : '—' ?>
          </div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600">Cable Route Type</div>
          <div style="font-weight:600;color:var(--text-primary);margin-top:2px"><?= e($survey['cable_route_type'] ?: 'Aerial') ?></div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600">Power Available at Site</div>
          <div style="font-weight:600;color:var(--text-primary);margin-top:2px"><?= e($survey['power_available'] ?: 'Standard AC 220V') ?></div>
        </div>
      </div>

      <div style="margin-bottom:14px">
        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600">Building Ingress &amp; Access Status</div>
        <div style="font-size:0.875rem;color:var(--text-secondary);margin-top:2px"><?= e($survey['building_access_status'] ?: 'Direct access confirmed') ?></div>
      </div>

      <?php if ($survey['civil_works_required']): ?>
      <div style="margin-bottom:14px;background:#fffbeb;border:1px solid #fde68a;padding:12px;border-radius:6px">
        <div style="font-weight:700;color:#92400e;font-size:0.82rem;display:flex;align-items:center;gap:6px">
          <span>⚠️</span> Civil Works &amp; Extension Required
        </div>
        <div style="font-size:0.85rem;color:#78350f;margin-top:4px"><?= e($survey['civil_works_details'] ?: 'Trenching / road cut necessary') ?></div>
      </div>
      <?php endif; ?>

      <div style="background:var(--surface);padding:14px;border-radius:6px;border:1px solid var(--border)">
        <div style="font-size:0.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:4px">Surveyor Observations &amp; Technical Notes:</div>
        <div style="font-size:0.875rem;color:var(--text-primary);line-height:1.6"><?= nl2br(e($survey['surveyor_notes'])) ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:10px">
          Conducted on <strong><?= fmtDate($survey['survey_date'] ?: $survey['completed_at']) ?></strong> by <strong><?= e($survey['completed_by_name'] ?: 'Field Team') ?></strong>
        </div>
      </div>

      <?php if ($survey['pm_review_remarks']): ?>
      <div style="margin-top:14px;background:rgba(16,185,129,0.08);border-left:3px solid var(--success);padding:12px 14px;border-radius:4px">
        <div style="font-size:0.78rem;font-weight:700;color:var(--success)">Project Manager Review Remarks:</div>
        <div style="font-size:0.875rem;color:var(--text-primary);margin-top:4px"><?= e($survey['pm_review_remarks']) ?></div>
        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:6px">Reviewed by <?= e($survey['reviewed_by_name'] ?: 'PM') ?> on <?= fmtDateTime($survey['reviewed_at']) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Field Team Workflow Action: Accept & Start Survey -->
    <?php if ($status === 'Assigned'): ?>
    <div class="card" style="padding:20px;border-left:4px solid #0284c7;background:#f0f9ff">
      <div style="font-weight:700;font-size:1rem;color:#0369a1;margin-bottom:6px">
        Step 1 — Accept Allocated Survey Task
      </div>
      <p style="font-size:0.875rem;color:#075985;margin-bottom:14px">
        This survey task has been assigned to team <strong><?= e($survey['team_name']) ?></strong>. Confirm task acceptance to notify the Project Coordinator.
      </p>
      <form method="POST" action="<?= APP_URL ?>/?page=surveys&action=accept">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
        <button type="submit" class="btn btn-primary" style="font-weight:700">
          <?= svgIcon('check') ?> Accept Allocated Survey Task
        </button>
      </form>
    </div>
    <?php elseif ($status === 'Accepted'): ?>
    <div class="card" style="padding:20px;border-left:4px solid #f59e0b;background:#fffbeb">
      <div style="font-weight:700;font-size:1rem;color:#92400e;margin-bottom:6px">
        Step 2 — Dispatched to Site / Start Survey
      </div>
      <p style="font-size:0.875rem;color:#b45309;margin-bottom:14px">
        Mark the survey <strong>In Progress</strong> when the field team arrives on site to begin route tracing and physical measurements.
      </p>
      <form method="POST" action="<?= APP_URL ?>/?page=surveys&action=start">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
        <div class="form-group" style="margin-bottom:12px">
          <input type="text" name="start_notes" class="form-control" placeholder="Optional check-in notes (e.g. Arrived on site, met building caretaker)...">
        </div>
        <button type="submit" class="btn btn-warning" style="font-weight:700">
          <?= svgIcon('map') ?> Field Team on Site — Start Survey
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Field Team Workflow Action: Submit Technical Findings (In Progress or Re-submission) -->
    <?php if (in_array($status, ['Accepted', 'In Progress'])): ?>
    <div class="card" style="padding:20px;border-top:4px solid var(--primary);box-shadow:0 2px 6px rgba(0,0,0,0.04)">
      <div style="font-weight:700;font-size:1.1rem;color:var(--text-primary);margin-bottom:4px">
        Submit On-Site Survey Findings &amp; Technical Report
      </div>
      <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:18px">
        Record physical route measurements, DP details, civil works requirements, and upload site photos.
      </div>

      <form method="POST" action="<?= APP_URL ?>/?page=surveys&action=submit_findings" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">

        <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Feasibility Verdict <span class="required" style="color:red">*</span></label>
            <select name="feasibility_result" class="form-control" required style="font-weight:600">
              <option value="">-- Select Verdict --</option>
              <option value="Feasible">🟢 Technically Feasible (Standard Drop)</option>
              <option value="Feasible with Extension/Civils">🟡 Feasible with Extension / Civil Works</option>
              <option value="Not Feasible">🔴 Technically Not Feasible</option>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Measured Fiber Distance (Meters) <span class="required" style="color:red">*</span></label>
            <input type="number" step="0.1" name="fiber_distance_meters" class="form-control" placeholder="e.g. 145.5" required>
          </div>
        </div>

        <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Nearest DP / Splitter ID <span class="required" style="color:red">*</span></label>
            <input type="text" name="nearest_dp_or_splitter" class="form-control" placeholder="e.g. DP-MWENGE-04 (Port 2)" required>
          </div>

          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Cable Route Type</label>
            <select name="cable_route_type" class="form-control">
              <option value="Aerial">Aerial (Poles / Wall brackets)</option>
              <option value="Underground">Underground (Ducts / Trenching)</option>
              <option value="Hybrid">Hybrid (Aerial + Underground Ingress)</option>
            </select>
          </div>
        </div>

        <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Power Available at Premise</label>
            <select name="power_available" class="form-control">
              <option value="AC 220V + UPS">AC 220V + UPS Backup</option>
              <option value="Standard AC 220V">Standard AC 220V</option>
              <option value="DC 48V Telecom Power">DC 48V Telecom Power</option>
              <option value="Generator Backed">Generator Backed</option>
              <option value="No Power on Site">No Power on Site</option>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Building Access Status</label>
            <select name="building_access_status" class="form-control">
              <option value="Easy Direct Access">Easy Direct Access</option>
              <option value="Building Management Permit Required">Building Management Permit Required</option>
              <option value="Guarded / Restricted Access">Guarded / Restricted Access</option>
              <option value="Riser Key Required from Caretaker">Riser Key Required from Caretaker</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:14px">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary);display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="civil_works_required" value="1" id="civilsCheck" onchange="document.getElementById('civilsDetailsGroup').style.display = this.checked ? 'block' : 'none'">
            <span>Civil Works or Special Extension Required?</span>
          </label>
          <div id="civilsDetailsGroup" style="display:none;margin-top:8px">
            <textarea name="civil_works_details" class="form-control" rows="2" placeholder="Describe required road cuts, trenching, poles, or core drilling..."></textarea>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:14px">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Surveyor Technical Observations &amp; Remarks <span class="required" style="color:red">*</span></label>
          <textarea name="surveyor_notes" class="form-control" rows="3" placeholder="Provide complete technical breakdown of cable path, ODF availability, signal attenuation, rack space, and any hazards..." required></textarea>
        </div>

        <!-- Site Evidence / Photo Upload -->
        <div style="background:var(--surface-hover);padding:14px;border-radius:6px;margin-bottom:18px">
          <div style="font-weight:700;font-size:0.85rem;color:var(--text-primary);margin-bottom:4px">
            📸 Attach Site Photos &amp; Route Evidence
          </div>
          <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:10px">
            Upload site entrance photo, nearest DP/ODF port photo, cable ingress path, and building riser.
          </div>
          <input type="file" name="evidence_files[]" class="form-control" multiple accept="image/*,.pdf">
        </div>

        <button type="submit" class="btn btn-success" style="font-weight:700;padding:10px 20px">
          <?= svgIcon('check') ?> Submit Completed Survey Findings
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Project Manager Review & Approval Form -->
    <?php if ($status === 'Completed' && ($isPM || $isAdminUser)): ?>
    <div class="card" style="padding:20px;border-left:4px solid var(--primary);background:#f8fafc">
      <div style="font-weight:700;font-size:1.1rem;color:var(--text-primary);margin-bottom:4px">
        Project Coordinator / Manager Review
      </div>
      <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px">
        Review the technical survey report submitted by the field team. Approving verifies site readiness and can sync directly to the service order.
      </p>

      <form method="POST" action="<?= APP_URL ?>/?page=surveys&action=pm_approve">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">

        <div class="form-group" style="margin-bottom:12px">
          <label style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">Review Remarks &amp; Project Notes</label>
          <textarea name="pm_review_remarks" class="form-control" rows="2" placeholder="e.g. Verified feasible per Neilos network standards. Technical scope approved."></textarea>
        </div>

        <?php if (!empty($survey['order_id'])): ?>
        <div class="form-group" style="margin-bottom:16px">
          <label style="font-size:0.85rem;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="sync_to_order" value="1" checked>
            <span>Automatically Sync Findings to Service Order Feasibility Review</span>
          </label>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-success" style="font-weight:700">
            <?= svgIcon('check') ?> Approve Survey Report
          </button>
          <button type="submit" formaction="<?= APP_URL ?>/?page=surveys&action=pm_reject" class="btn btn-warning" onclick="return confirm('Request re-survey or reject findings?')">
            <?= svgIcon('refresh') ?> Request Re-survey
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Card: Designated Field Team Info -->
    <div class="card" style="padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">
        👷 Designated Field Team
      </div>

      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <div style="width:40px;height:40px;border-radius:8px;background:rgba(15,76,129,0.1);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800">
          FT
        </div>
        <div>
          <div style="font-weight:700;color:var(--text-primary);font-size:0.95rem"><?= e($survey['team_name']) ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted)"><?= e($survey['contractor_name']) ?></div>
        </div>
      </div>

      <div style="font-size:0.82rem;line-height:1.6;color:var(--text-secondary)">
        <div>Team Lead: <strong><?= e($survey['leader_name'] ?: 'Not specified') ?></strong></div>
        <div>Phone: <strong><?= e($survey['leader_phone'] ?: 'N/A') ?></strong></div>
        <div>Coverage Region: <strong><?= e($survey['coverage_region'] ?: 'General') ?></strong></div>
      </div>

      <?php if (($isPM || $isAdminUser) && !empty($allTeams)): ?>
      <div style="margin-top:16px;border-top:1px dashed var(--border);padding-top:12px">
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('reassignForm').style.display = document.getElementById('reassignForm').style.display === 'none' ? 'block' : 'none'" style="width:100%">
          <?= svgIcon('refresh', 12) ?> Reassign to Another Team
        </button>

        <form id="reassignForm" method="POST" action="<?= APP_URL ?>/?page=surveys&action=reassign" style="display:none;margin-top:10px">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">
          <div class="form-group" style="margin-bottom:8px">
            <select name="new_contractor_team_id" class="form-control" style="font-size:0.8rem" required>
              <option value="">Select New Team...</option>
              <?php foreach ($allTeams as $at): ?>
              <?php if ((int)$at['id'] !== (int)$survey['contractor_team_id']): ?>
              <option value="<?= $at['id'] ?>"><?= e($at['team_name']) ?> (<?= e($at['contractor_name']) ?>)</option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:8px">
            <input type="text" name="reassign_note" class="form-control" placeholder="Reason for reallocation..." style="font-size:0.8rem">
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="width:100%">Confirm Reassign</button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Card: Evidence & Site Photos Gallery -->
    <div class="card" style="padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">
        <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">
          📸 Site Photos &amp; Evidence
        </div>
        <span class="badge" style="background:var(--surface-hover);font-weight:700;font-size:0.75rem"><?= count($evidenceFiles) ?></span>
      </div>

      <?php if (empty($evidenceFiles)): ?>
      <p style="font-size:0.8rem;color:var(--text-muted);text-align:center;padding:16px 0">
        No photos attached yet. Field team will upload during on-site visit.
      </p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($evidenceFiles as $ef): ?>
        <?php
          $filePath = APP_URL . '/' . ltrim($ef['file_path'], '/');
          $isImg = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $ef['file_name']);
        ?>
        <div style="border:1px solid var(--border);border-radius:6px;padding:8px 10px;display:flex;align-items:center;gap:10px;background:var(--surface)">
          <div style="width:36px;height:36px;border-radius:4px;background:var(--surface-hover);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
            <?php if ($isImg): ?>
              <img src="<?= $filePath ?>" style="width:100%;height:100%;object-fit:cover" alt="thumbnail">
            <?php else: ?>
              📄
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:0.8rem;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= e($ef['file_name']) ?>
            </div>
            <div style="font-size:0.7rem;color:var(--text-muted)">
              <span class="badge" style="font-size:0.65rem"><?= e($ef['file_type']) ?></span>
              · <?= fmtDate($ef['uploaded_at']) ?>
            </div>
          </div>
          <a href="<?= APP_URL ?>/?page=download&table=survey_evidence&id=<?= $ef['id'] ?>&inline=1" target="_blank" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:0.75rem" title="View Document / Photo">
            <?= svgIcon('eye', 11) ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Card: Timeline / Audit Events -->
    <div class="card" style="padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.03)">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">
        ⏱️ Survey Audit Trail
      </div>

      <div style="position:relative;padding-left:18px;border-left:2px solid var(--border);font-size:0.8rem">
        <?php foreach ($timeline as $t): ?>
        <div style="position:relative;margin-bottom:14px">
          <div style="position:absolute;left:-23px;top:2px;width:8px;height:8px;border-radius:50%;background:var(--primary)"></div>
          <div style="font-weight:700;color:var(--text-primary)"><?= e($t['status']) ?></div>
          <div style="color:var(--text-secondary);font-size:0.75rem;margin-top:1px"><?= e($t['note']) ?></div>
          <div style="color:var(--text-muted);font-size:0.7rem;margin-top:2px">
            <?= fmtDateTime($t['created_at']) ?><?= $t['actor_name'] ? ' · ' . e($t['actor_name']) : '' ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</div>
