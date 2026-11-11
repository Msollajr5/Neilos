<?php
// ============================================================
// Neilos Portal — Native Pipeline Management View
// Integrated with Neilos Design System, Database & Theme
// ============================================================
$currentUserName = $user['full_name'] ?? 'User';
$currentUserRole = $user['role'] ?? 'KAM';
// isAdmin() checks for 'System Admin'; hasRole also includes System Admin by default
$canDelete = (isAdmin() || hasRole('Management'));
?>

<style>
/* Scoped Pipeline Management Styles — Perfectly Aligned with Neilos */
.pipeline-shell {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.pipeline-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  background: var(--surface, #fff);
  border: 1px solid var(--border, rgba(0,0,0,0.08));
  border-radius: var(--radius-md, 12px);
  padding: 16px 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.pipeline-topbar-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.pipeline-title {
  font-family: 'Sora', sans-serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-primary, #09090B);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.pipeline-title .brand-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 999px;
  background: rgba(15,76,129,0.1);
  color: var(--accent, #0F4C81);
  border: 1px solid rgba(15,76,129,0.2);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.pipeline-subtitle {
  font-size: 0.82rem;
  color: var(--text-secondary, #52525B);
}
.pipeline-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.biz-switch {
  display: inline-flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
  border: 1px solid #e2e8f0;
}
.biz-switch button {
  border: 0;
  background: transparent;
  padding: 6px 12px;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}
.biz-switch button.active {
  background: var(--accent, #0F4C81);
  color: #fff;
  box-shadow: 0 1px 3px rgba(15,76,129,0.3);
}

/* Tab Navigation */
.pipeline-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 2px;
  border-bottom: 1px solid var(--border, #e2e8f0);
}
.pipeline-nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--text-secondary, #52525B);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.18s ease;
  white-space: nowrap;
}
.pipeline-nav-btn:hover {
  background: rgba(0,0,0,0.03);
  color: var(--accent, #0F4C81);
}
.pipeline-nav-btn.active {
  background: rgba(15,76,129,0.08);
  color: var(--accent, #0F4C81);
  border-color: rgba(15,76,129,0.22);
}
.pipeline-nav-badge {
  font-size: 0.72rem;
  padding: 1px 7px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #475569;
  font-weight: 700;
}
.pipeline-nav-btn.active .pipeline-nav-badge {
  background: var(--accent, #0F4C81);
  color: #fff;
}

/* KPI Card Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border, rgba(0,0,0,0.08));
  border-radius: 12px;
  padding: 16px 18px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.kpi-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent, #0F4C81);
}
.kpi-card.success::before { background: #10B981; }
.kpi-card.warning::before { background: #F59E0B; }
.kpi-card.info::before { background: #3B82F6; }
.kpi-card.danger::before { background: #EF4444; }

.kpi-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary, #64748b);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.kpi-value {
  font-size: 1.55rem;
  font-weight: 700;
  font-family: 'Sora', sans-serif;
  color: var(--text-primary, #09090B);
  margin-top: 8px;
}
.kpi-sub {
  font-size: 0.76rem;
  color: var(--text-muted, #94a3b8);
  margin-top: 4px;
}

/* Funnel & Charts Grid */
.charts-grid {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 18px;
}
@media (max-width: 980px) {
  .charts-grid { grid-template-columns: 1fr; }
}

/* Stage Progress Badges */
.stage-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}
.stage-lead          { background: rgba(100,116,139,0.12); color: #475569; }
.stage-qualification { background: rgba(59,130,246,0.12); color: #1d4ed8; }
.stage-proposal      { background: rgba(245,158,11,0.14); color: #b45309; }
.stage-negotiation   { background: rgba(139,92,246,0.14); color: #6d28d9; }
.stage-won           { background: rgba(16,185,129,0.15); color: #047857; }
.stage-lost          { background: rgba(239,68,68,0.12); color: #b91c1c; }

/* Slide-over Drawer */
.pipeline-drawer-overlay {
  position: fixed; inset: 0;
  background: rgba(15,23,42,0.45);
  backdrop-filter: blur(2px);
  z-index: 1040;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s ease;
}
.pipeline-drawer-overlay.active {
  opacity: 1;
  visibility: visible;
}
.pipeline-drawer {
  position: fixed;
  top: 0; right: -620px;
  width: 580px;
  max-width: 94vw;
  height: 100vh;
  background: var(--surface, #fff);
  box-shadow: -8px 0 32px rgba(0,0,0,0.18);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  transition: right 0.28s cubic-bezier(0.2, 0.9, 0.3, 1);
}
.pipeline-drawer.active {
  right: 0;
}
.drawer-header {
  padding: 18px 22px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.drawer-body {
  padding: 22px;
  overflow-y: auto;
  flex: 1;
}
.drawer-footer {
  padding: 16px 22px;
  border-top: 1px solid var(--border, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
}

/* Stage Visual Step Bar */
.stage-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 16px 0 24px;
  position: relative;
}
.stage-stepper::before {
  content: '';
  position: absolute;
  top: 14px; left: 20px; right: 20px;
  height: 2px;
  background: #e2e8f0;
  z-index: 1;
}
.step-node {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
}
.step-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.75rem;
  color: #64748b;
  transition: all 0.2s ease;
}
.step-node.completed .step-dot {
  background: #10B981;
  border-color: #10B981;
  color: #fff;
}
.step-node.current .step-dot {
  background: var(--accent, #0F4C81);
  border-color: var(--accent, #0F4C81);
  color: #fff;
  box-shadow: 0 0 0 4px rgba(15,76,129,0.15);
}
.step-node.current span {
  color: var(--accent, #0F4C81);
  font-weight: 700;
}

/* Modal Windows */
.pipeline-modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15,23,42,0.5);
  backdrop-filter: blur(2px);
  z-index: 1060;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.pipeline-modal-backdrop.active {
  display: flex;
}
.pipeline-modal {
  background: var(--surface, #fff);
  border-radius: 14px;
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
}

/* Toast Notification */
.pipeline-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #09090b;
  color: #fff;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 0.84rem;
  font-weight: 600;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 10px;
  opacity: 0;
  transform: translateY(12px);
  transition: all 0.22s ease;
  pointer-events: none;
}
.pipeline-toast.show {
  opacity: 1;
  transform: translateY(0);
}

/* ─── SAVANNA (SBE) ORANGE THEME PALETTE (from NNL_SBE_Lead_and_Ticket_Management_Portal.html) ─── */
.pipeline-shell.theme-sbe {
  --accent: #F58220 !important;
  --accent-hover: #b95606 !important;
  --accent-light: #fff3e8 !important;
  --accent-border: #ffd1a8 !important;
  --accent-shadow: rgba(245, 130, 32, 0.28) !important;
}

/* SBE / NNL Segment Switcher Buttons */
.pipeline-shell .biz-switch button#btnSBE.active {
  background: #F58220 !important;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(245, 130, 32, 0.38) !important;
}
.pipeline-shell .biz-switch button#btnNNL.active {
  background: #0F4C81 !important;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(15, 76, 129, 0.32) !important;
}

/* Brand Badge when SBE */
.pipeline-shell.theme-sbe .brand-badge {
  background: #fff3e8 !important;
  color: #F58220 !important;
  border-color: #ffd1a8 !important;
  box-shadow: 0 1px 3px rgba(245, 130, 32, 0.12) !important;
}

/* Navigation Tabs when SBE */
.pipeline-shell.theme-sbe .pipeline-nav-btn:hover {
  color: #F58220 !important;
  background: #fff7ef !important;
}
.pipeline-shell.theme-sbe .pipeline-nav-btn.active {
  background: #fff3e8 !important;
  color: #F58220 !important;
  border-color: #ffd1a8 !important;
}
.pipeline-shell.theme-sbe .pipeline-nav-btn.active .pipeline-nav-badge {
  background: #F58220 !important;
  color: #fff !important;
}

/* Primary Action Buttons */
.pipeline-shell.theme-sbe .btn-primary,
.pipeline-shell.theme-sbe button.btn-primary {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
  box-shadow: 0 3px 10px rgba(245, 130, 32, 0.28) !important;
}
.pipeline-shell.theme-sbe .btn-primary:hover,
.pipeline-shell.theme-sbe button.btn-primary:hover {
  background: #b95606 !important;
  border-color: #b95606 !important;
  box-shadow: 0 4px 14px rgba(185, 86, 6, 0.32) !important;
}

/* KPI Cards top border & accents */
.pipeline-shell.theme-sbe .kpi-card::before {
  background: #F58220 !important;
}
.pipeline-shell.theme-sbe .kpi-card:hover {
  border-color: #ffd1a8 !important;
}
.pipeline-shell.theme-sbe .pipeline-title svg {
  color: #F58220 !important;
}

/* Stepper Visual Nodes in Drawer */
.pipeline-shell.theme-sbe .step-node.completed,
.pipeline-shell.theme-sbe .step-node.current {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
}
.pipeline-shell.theme-sbe .step-node.completed .step-dot,
.pipeline-shell.theme-sbe .step-node.current .step-dot {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
}

/* Drawer Timeline */
.pipeline-shell.theme-sbe .drawer-timeline-item {
  border-left-color: #F58220 !important;
}

/* Input Focus */
.pipeline-shell.theme-sbe input:focus,
.pipeline-shell.theme-sbe select:focus,
.pipeline-shell.theme-sbe textarea:focus {
  border-color: #F58220 !important;
  outline-color: #F58220 !important;
  box-shadow: 0 0 0 3px rgba(245, 130, 32, 0.16) !important;
}

/* Modal and Drawer Headers */
.pipeline-shell.theme-sbe .modal-header h3,
.pipeline-shell.theme-sbe #oppDrawer .drawer-header h2 {
  color: #F58220 !important;
}
</style>

<div class="pipeline-shell" id="pipelineShell">
  <!-- Topbar Header -->
  <div class="pipeline-topbar">
    <div class="pipeline-topbar-left">
      <h1 class="pipeline-title">
        <?= svgIcon('chart', 22) ?>
        Pipeline Management
        <span class="brand-badge" id="companyBadge">NNL Wholesale</span>
      </h1>
      <div class="pipeline-subtitle">
        Commercial opportunity pipeline, service ordering (SO) governance &amp; delivery handover
      </div>
    </div>

    <div class="pipeline-controls">
      <!-- Company Switcher -->
      <div class="biz-switch">
        <button type="button" class="active" id="btnNNL" onclick="switchPipelineCompany('NNL')">NNL Network</button>
        <button type="button" id="btnSBE" onclick="switchPipelineCompany('SBE')">Savanna (SBE)</button>
      </div>

      <!-- Quick Role View Simulator -->
      <select class="form-control" id="roleSimulator" style="height:36px;font-size:0.8rem;padding:4px 10px;width:auto" onchange="switchRoleContext(this.value)">
        <option value="All">View: All Pipeline Roles</option>
        <option value="KAM" <?= $currentUserRole === 'KAM' ? 'selected' : '' ?>>Account Manager (KAM)</option>
        <option value="BSA" <?= $currentUserRole === 'BSA' ? 'selected' : '' ?>>BSA / Solutions Architect</option>
        <option value="Commercial" <?= $currentUserRole === 'Commercial' ? 'selected' : '' ?>>Commercial Director</option>
        <option value="PM" <?= $currentUserRole === 'Project Manager' ? 'selected' : '' ?>>Project Manager</option>
        <option value="Management" <?= $currentUserRole === 'Management' ? 'selected' : '' ?>>Executive Management</option>
      </select>

      <!-- New Opportunity Button -->
      <button class="btn btn-primary btn-sm" onclick="openNewOpportunityModal()" style="display:inline-flex;align-items:center;gap:6px">
        <?= svgIcon('plus', 15) ?> New Opportunity
      </button>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <div class="pipeline-nav">
    <button class="pipeline-nav-btn active" onclick="showPipelineTab('dashboard', this)">
      <?= svgIcon('dashboard', 16) ?> Dashboard
    </button>
    <button class="pipeline-nav-btn" onclick="showPipelineTab('opportunities', this)">
      <?= svgIcon('list', 16) ?> Opportunities
      <span class="pipeline-nav-badge" id="tabOppCount">0</span>
    </button>
    <button class="pipeline-nav-btn" onclick="showPipelineTab('so_management', this)">
      <?= svgIcon('document', 16) ?> SO Management
      <span class="pipeline-nav-badge" id="tabSoCount">0</span>
    </button>
    <button class="pipeline-nav-btn" onclick="showPipelineTab('projects', this)">
      <?= svgIcon('project', 16) ?> Projects Handover
      <span class="pipeline-nav-badge" id="tabProjCount">0</span>
    </button>
    <button class="pipeline-nav-btn" onclick="showPipelineTab('reports', this)">
      <?= svgIcon('chart', 16) ?> Reports &amp; Export
    </button>
    <button class="pipeline-nav-btn" onclick="showPipelineTab('admin', this)">
      <?= svgIcon('settings', 16) ?> Pipeline Settings
    </button>
  </div>

  <!-- ── 1. DASHBOARD TAB ── -->
  <div id="tab-dashboard" class="pipeline-tab-pane">
    <!-- KPI Row -->
    <div class="kpi-grid mb-20" style="margin-bottom:20px">
      <div class="kpi-card info">
        <div class="kpi-label">Active Opportunities <?= svgIcon('chart', 16) ?></div>
        <div class="kpi-value" id="kpiTotalDeals">0</div>
        <div class="kpi-sub" id="kpiWeightedVal">Weighted: $0</div>
      </div>
      <div class="kpi-card success">
        <div class="kpi-label">Active Pipeline (MRC) <?= svgIcon('dollar', 16) ?></div>
        <div class="kpi-value" id="kpiPipelineMrc">$0</div>
        <div class="kpi-sub" id="kpiPipelineMrcTzs">TZS 0</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Total Contract Value (TCV) <?= svgIcon('dollar-sign', 16) ?></div>
        <div class="kpi-value" id="kpiPipelineTcv">$0</div>
        <div class="kpi-sub">Annualized recurring + NRC</div>
      </div>
      <div class="kpi-card success">
        <div class="kpi-label">Won Conversion Rate <?= svgIcon('check', 16) ?></div>
        <div class="kpi-value" id="kpiWinRate">0%</div>
        <div class="kpi-sub" id="kpiWonCount">0 won deals</div>
      </div>
      <div class="kpi-card warning">
        <div class="kpi-label">In SO Management <?= svgIcon('document', 16) ?></div>
        <div class="kpi-value" id="kpiSoDeals">0</div>
        <div class="kpi-sub">Awaiting execution / countersign</div>
      </div>
      <div class="kpi-card danger">
        <div class="kpi-label">Overdue Follow-ups <?= svgIcon('alert', 16) ?></div>
        <div class="kpi-value" id="kpiOverdueCount">0</div>
        <div class="kpi-sub" style="color:#dc2626">Next action date past due</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-grid mb-20" style="margin-bottom:20px">
      <!-- Funnel by Stage -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('chart', 16) ?> Pipeline Volume by Sales Stage
          </div>
          <span style="font-size:0.75rem;color:var(--text-muted)">Stage conversion funnel</span>
        </div>
        <div class="card-body" style="padding:18px">
          <div id="stageFunnelBars" style="display:flex;flex-direction:column;gap:12px">
            <!-- Rendered via JS -->
          </div>
        </div>
      </div>

      <!-- Service Mix -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('building', 16) ?> Product &amp; Service Distribution
          </div>
          <span style="font-size:0.75rem;color:var(--text-muted)">By deal volume</span>
        </div>
        <div class="card-body" style="padding:18px;display:flex;flex-direction:column;gap:14px" id="productMixContainer">
          <!-- Rendered via JS -->
        </div>
      </div>
    </div>

    <!-- Tables Row: Stage Movement & Overdue Follow-ups -->
    <div class="charts-grid">
      <!-- Stage Movement Velocity -->
      <div class="card">
        <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('clock', 16) ?> Stage Movement Velocity (Avg. Days in Stage)
          </div>
        </div>
        <div class="card-body" style="padding:0">
          <div class="table-responsive">
            <table class="table table-hover" style="margin:0;font-size:0.83rem">
              <thead>
                <tr style="background:#f8fafc">
                  <th>Stage Transition</th>
                  <th>Target SLA</th>
                  <th>Avg. Days</th>
                  <th>Health Status</th>
                </tr>
              </thead>
              <tbody id="stageVelocityTableBody">
                <tr><td>Lead &rarr; Qualification</td><td>2 Days</td><td>1.4 Days</td><td><span class="badge badge-success">Fast</span></td></tr>
                <tr><td>Qualification &rarr; Proposal</td><td>3 Days</td><td>2.1 Days</td><td><span class="badge badge-success">On Track</span></td></tr>
                <tr><td>Proposal &rarr; Negotiation</td><td>5 Days</td><td>4.8 Days</td><td><span class="badge badge-success">On Track</span></td></tr>
                <tr><td>Negotiation &rarr; Won</td><td>7 Days</td><td>6.2 Days</td><td><span class="badge badge-success">On Track</span></td></tr>
                <tr><td>Won &rarr; Signed SOF</td><td>3 Days</td><td>1.8 Days</td><td><span class="badge badge-success">Excellent</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Overdue Follow-ups -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('alert', 16) ?> Attention Needed &bull; Overdue Follow-ups
          </div>
          <span class="badge badge-danger" id="badgeOverdueCount">0</span>
        </div>
        <div class="card-body" style="padding:0">
          <div class="table-responsive">
            <table class="table table-hover" style="margin:0;font-size:0.82rem">
              <thead>
                <tr style="background:#f8fafc">
                  <th>Customer</th>
                  <th>Stage</th>
                  <th>Due Date</th>
                  <th>Owner</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="overdueTableBody">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 2. OPPORTUNITIES TAB ── -->
  <div id="tab-opportunities" class="pipeline-tab-pane" style="display:none">
    <!-- Filter Card -->
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:14px 18px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <!-- Left: Search Box -->
          <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:280px">
            <div style="position:relative;width:100%">
              <input type="text" id="oppSearch" class="form-control" placeholder="Search customer, building, phone, GPS (-6.78, 39.26), contact..." oninput="renderOpportunitiesTable()" style="padding-left:34px;height:38px;font-size:0.85rem">
              <span style="position:absolute;left:11px;top:10px;color:#94a3b8"><?= svgIcon('search', 16) ?></span>
            </div>
          </div>

          <!-- Middle: Stage & Service Filters -->
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <select id="oppStageFilter" class="form-control" style="width:auto;height:38px;font-size:0.82rem" onchange="renderOpportunitiesTable()">
              <option value="All">All Stages</option>
              <option value="Lead">Lead</option>
              <option value="Qualification">Qualification</option>
              <option value="Proposal">Proposal</option>
              <option value="Negotiation">Negotiation</option>
              <option value="Won">Won (Closed)</option>
              <option value="Lost">Lost</option>
            </select>

            <select id="oppServiceFilter" class="form-control" style="width:auto;height:38px;font-size:0.82rem" onchange="renderOpportunitiesTable()">
              <option value="All">All Services</option>
              <option value="Dedicated Internet Access (DIA)">Dedicated Internet Access (DIA)</option>
              <option value="Layer 2 Ethernet Point-to-Point">Layer 2 Ethernet Point-to-Point</option>
              <option value="FTTH Wholesale Fiber Connection">FTTH Wholesale</option>
              <option value="Colocation Rack Space">Colocation</option>
              <option value="Managed Security">Managed Security</option>
              <option value="Managed Wi-Fi">Managed Wi-Fi</option>
            </select>

            <!-- Date Presets -->
            <div style="display:inline-flex;gap:4px">
              <button class="btn btn-sm btn-primary" id="period-all" onclick="setOppPeriod('all', this)">All Time</button>
              <button class="btn btn-sm btn-secondary" id="period-month" onclick="setOppPeriod('thisMonth', this)">This Month</button>
              <button class="btn btn-sm btn-secondary" id="period-last" onclick="setOppPeriod('lastMonth', this)">Last Month</button>
            </div>
          </div>
        </div>

        <!-- Commercial Totals Banner -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:0.82rem">
          <div style="color:var(--text-secondary)">
            Showing <strong id="oppFilteredCount">0</strong> opportunities
          </div>
          <div style="display:flex;align-items:center;gap:16px;font-weight:600">
            <span>Filtered NRC: <strong id="sumFilteredNrc" style="color:var(--text-primary)">$0</strong></span>
            <span>Filtered Monthly MRC: <strong id="sumFilteredMrc" style="color:var(--accent, #0F4C81)">$0</strong></span>
            <span>Annual Run-Rate (ARR): <strong id="sumFilteredArr" style="color:#059669">$0</strong></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Opportunity Table Card -->
    <div class="card">
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Ref / Code</th>
                <th>Customer / Prospect</th>
                <th>Building / Location</th>
                <th>Service &amp; Capacity</th>
                <th>Stage</th>
                <th style="text-align:right">NRC (USD)</th>
                <th style="text-align:right">MRC (USD)</th>
                <th>Expected Close</th>
                <th>Lead Owner</th>
                <th style="text-align:center">Action</th>
              </tr>
            </thead>
            <tbody id="oppTableBody">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 3. SO MANAGEMENT TAB ── -->
  <div id="tab-so_management" class="pipeline-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 4px">Won Deals &bull; Service Order (SO) Management</h3>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin:0">
              Closed deals automatically enter SO Management. Generates standard Neilos SOFs and hands off to Delivery Projects.
            </p>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <select class="form-control" id="soStatusFilter" style="height:36px;font-size:0.82rem;width:auto" onchange="renderSoTable()">
              <option value="All">All SO Statuses</option>
              <option value="SOF Sent">SOF Sent</option>
              <option value="Signed SOF Uploaded">Signed SOF Uploaded</option>
              <option value="Installation">Installation (Handed Over)</option>
              <option value="Closed">Closed &amp; Billing Active</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>SO Reference</th>
                <th>Order / Opportunity</th>
                <th>Client / Partner</th>
                <th>Service Requested</th>
                <th>Contract Term</th>
                <th style="text-align:right">NRC</th>
                <th style="text-align:right">Monthly MRC</th>
                <th>SO Status</th>
                <th>Execution Handover</th>
                <th style="text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody id="soTableBody">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 4. PROJECTS HANDOVER TAB ── -->
  <div id="tab-projects" class="pipeline-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 4px">Project Delivery Handover</h3>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin:0">
              Orders released from Commercial/SO into Field Installation, Technical Testing, and Customer UAT Acceptance.
            </p>
          </div>
          <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">
            <?= svgIcon('external-link', 14) ?> View in Core Order Tracking
          </a>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Order Ref</th>
                <th>Customer &amp; Building</th>
                <th>Service Type</th>
                <th>Assigned PM / Contractor</th>
                <th>Target Completion</th>
                <th>Project Stage</th>
                <th>Progress</th>
                <th style="text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody id="projectsTableBody">
              <!-- Rendered dynamically -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 5. REPORTS & EXPORTS TAB ── -->
  <div id="tab-reports" class="pipeline-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:18px">
        <h3 style="font-size:1rem;font-weight:700;margin:0 0 14px">Custom Pipeline Reports &amp; CSV Export</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Filter by Stage</label>
            <select id="reportStage" class="form-control" style="height:36px;font-size:0.82rem;margin-top:4px" onchange="renderReportPreview()">
              <option value="All">All Stages</option>
              <option value="Lead">Lead</option>
              <option value="Qualification">Qualification</option>
              <option value="Proposal">Proposal</option>
              <option value="Negotiation">Negotiation</option>
              <option value="Won">Won</option>
              <option value="Lost">Lost</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Lead Owner / KAM</label>
            <select id="reportOwner" class="form-control" style="height:36px;font-size:0.82rem;margin-top:4px" onchange="renderReportPreview()">
              <option value="All">All Owners</option>
              <?php foreach ($teamUsers as $tu): ?>
              <option value="<?= e($tu['full_name']) ?>"><?= e($tu['full_name']) ?> (<?= e($tu['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Close Date From</label>
            <input type="month" id="reportFrom" class="form-control" style="height:36px;font-size:0.82rem;margin-top:4px" value="<?= date('Y-01') ?>" onchange="renderReportPreview()">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Close Date To</label>
            <input type="month" id="reportTo" class="form-control" style="height:36px;font-size:0.82rem;margin-top:4px" value="<?= date('Y-12') ?>" onchange="renderReportPreview()">
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;margin-top:16px;flex-wrap:wrap">
          <button class="btn btn-primary btn-sm" onclick="downloadPipelineCSV(false)">
            <?= svgIcon('download', 14) ?> Export Standard CSV
          </button>
          <button class="btn btn-secondary btn-sm" onclick="downloadPipelineCSV(true)">
            <?= svgIcon('download', 14) ?> Export Revenue Forecast CSV
          </button>
          <button class="btn btn-secondary btn-sm" onclick="renderReportPreview()">
            <?= svgIcon('search', 14) ?> Refresh Preview
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Table -->
    <div class="card">
      <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border)">
        <strong>Live Report Preview</strong>
      </div>
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.82rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Ref</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Stage</th>
                <th style="text-align:right">NRC</th>
                <th style="text-align:right">MRC</th>
                <th style="text-align:right">TCV</th>
                <th>Win Prob</th>
                <th>Expected Close</th>
                <th>Owner</th>
              </tr>
            </thead>
            <tbody id="reportPreviewBody">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 6. ADMIN & PERMISSIONS TAB ── -->
  <div id="tab-admin" class="pipeline-tab-pane" style="display:none">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
      <!-- Role Matrix -->
      <div class="card">
        <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('shield', 16) ?> Pipeline Role Access Matrix
          </div>
        </div>
        <div class="card-body" style="padding:16px">
          <div class="table-responsive">
            <table class="table" style="font-size:0.8rem">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>View</th>
                  <th>Create</th>
                  <th>Price Override</th>
                  <th>SO Handoff</th>
                </tr>
              </thead>
              <tbody>
                <tr><td><strong>Account Manager (KAM)</strong></td><td>Own &amp; Assigned</td><td><span class="badge badge-success">Yes</span></td><td>Proposal Only</td><td><span class="badge badge-success">Yes</span></td></tr>
                <tr><td><strong>BSA Engineer</strong></td><td>All Technical</td><td><span class="badge badge-success">Yes</span></td><td>NRC Only</td><td><span class="badge badge-secondary">No</span></td></tr>
                <tr><td><strong>Commercial Director</strong></td><td>All Commercial</td><td><span class="badge badge-success">Yes</span></td><td><span class="badge badge-success">Full</span></td><td><span class="badge badge-success">Yes</span></td></tr>
                <tr><td><strong>Project Manager</strong></td><td>Won &amp; SO</td><td><span class="badge badge-secondary">No</span></td><td><span class="badge badge-secondary">No</span></td><td><span class="badge badge-success">Yes</span></td></tr>
                <tr><td><strong>Executive Management</strong></td><td>Full System</td><td><span class="badge badge-success">Yes</span></td><td><span class="badge badge-success">Full</span></td><td><span class="badge badge-success">Yes</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Stage Win Probabilities & Duplication Rules -->
      <div class="card">
        <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            <?= svgIcon('settings', 16) ?> Win Probability by Stage &amp; Duplicate Detection
          </div>
        </div>
        <div class="card-body" style="padding:16px">
          <div style="margin-bottom:16px">
            <label style="font-size:0.8rem;font-weight:600">Stage Forecast Confidence Weights</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;font-size:0.8rem">
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Lead: <strong>10%</strong></div>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Qualification: <strong>25%</strong></div>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Proposal / Quote: <strong>50%</strong></div>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Negotiation: <strong>75%</strong></div>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Won (Signed): <strong>100%</strong></div>
              <div style="padding:6px 10px;background:#f8fafc;border-radius:6px">Lost: <strong>0%</strong></div>
            </div>
          </div>

          <div>
            <label style="font-size:0.8rem;font-weight:600">Duplicate Matching Rules</label>
            <p style="font-size:0.78rem;color:var(--text-secondary);margin:4px 0 8px">
              Opportunities matching any of the following triggers are flagged to prevent sales collision:
            </p>
            <ul style="font-size:0.8rem;color:#475569;padding-left:18px;margin:0">
              <li>Exact Phone match on last 6 digits</li>
              <li>Exact GPS coordinates (within 50 meters radius)</li>
              <li>Same building name and floor number combination</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── NEW OPPORTUNITY MODAL ── -->
<div id="newOppBackdrop" class="pipeline-modal-backdrop" onclick="closeNewOppOnBackdrop(event)">
  <div class="pipeline-modal">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--text-primary)" id="newOppModalTitle">
        Capture New Commercial Opportunity
      </h3>
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeNewOpportunityModal()">&times;</button>
    </div>

    <form id="newOppForm" onsubmit="handleCreateOpportunity(event)">
      <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Customer / Company *</label>
            <input type="text" id="inpCustomer" class="form-control" required placeholder="e.g. Standard Chartered Bank" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Channel Partner / Client</label>
            <select id="inpPartner" class="form-control" style="margin-top:4px">
              <option value="">Direct Enterprise Client</option>
              <?php foreach ($partners as $p): ?>
              <option value="<?= e($p['name']) ?>"><?= e($p['name']) ?> (<?= e($p['partner_type']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Contact Person</label>
            <input type="text" id="inpContact" class="form-control" placeholder="Full Name" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Phone Number *</label>
            <input type="tel" id="inpPhone" class="form-control" required placeholder="07XXXXXXXX" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Email</label>
            <input type="email" id="inpEmail" class="form-control" placeholder="name@company.com" style="margin-top:4px">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Building / Location *</label>
            <input type="text" id="inpBuilding" class="form-control" required placeholder="e.g. Amani Tower, 4th Floor" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">City / Region</label>
            <input type="text" id="inpCity" class="form-control" value="Dar es Salaam" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">GPS Coordinates</label>
            <input type="text" id="inpGps" class="form-control" placeholder="-6.7835, 39.2685" style="margin-top:4px">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Service Requested *</label>
            <select id="inpService" class="form-control" required style="margin-top:4px" onchange="applyDefaultServicePricing(this.value)">
              <option value="Dedicated Internet Access (DIA)">Dedicated Internet Access (DIA)</option>
              <option value="Layer 2 Ethernet Point-to-Point">Layer 2 Ethernet Point-to-Point</option>
              <option value="FTTH Wholesale Fiber Connection">FTTH Wholesale Fiber</option>
              <option value="Colocation Rack Space">Colocation Rack Space</option>
              <option value="Managed Security">Managed Security</option>
              <option value="Managed Wi-Fi">Managed Wi-Fi</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Capacity / Bandwidth</label>
            <input type="text" id="inpBandwidth" class="form-control" placeholder="e.g. 50 Mbps" value="50 Mbps" style="margin-top:4px">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;background:#f8fafc;padding:12px;border-radius:8px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Base NRC ($)</label>
            <input type="number" step="0.01" id="inpNrc" class="form-control" value="300.00" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Monthly MRC ($) *</label>
            <input type="number" step="0.01" id="inpMrc" class="form-control" required value="450.00" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Contract Term</label>
            <select id="inpTerm" class="form-control" style="margin-top:4px">
              <option value="12">12 Months</option>
              <option value="24">24 Months</option>
              <option value="36">36 Months</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Initial Stage</label>
            <select id="inpStage" class="form-control" style="margin-top:4px">
              <option value="Lead">Lead (10%)</option>
              <option value="Qualification" selected>Qualification (25%)</option>
              <option value="Proposal">Proposal / Quote (50%)</option>
              <option value="Negotiation">Negotiation (75%)</option>
              <option value="Won">Won Deal (100%)</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Expected Close Date *</label>
            <input type="date" id="inpCloseDate" class="form-control" required style="margin-top:4px" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Opportunity Owner</label>
            <select id="inpOwner" class="form-control" style="margin-top:4px">
              <option value="<?= e($currentUserName) ?>"><?= e($currentUserName) ?> (You)</option>
              <?php foreach ($teamUsers as $tu): ?>
              <?php if ($tu['full_name'] !== $currentUserName): ?>
              <option value="<?= e($tu['full_name']) ?>"><?= e($tu['full_name']) ?></option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#f8fafc">
        <button type="button" class="btn btn-secondary" onclick="closeNewOpportunityModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Opportunity</button>
      </div>
    </form>
  </div>
</div>

<!-- ── SLIDE-OVER OPPORTUNITY DETAIL DRAWER ── -->
<div id="oppDrawerBackdrop" class="pipeline-drawer-overlay" onclick="closeOppDrawer()"></div>
<div id="oppDrawer" class="pipeline-drawer">
  <div class="drawer-header">
    <div>
      <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600" id="drwRef">OPP-XXXX</div>
      <h2 style="margin:2px 0 0;font-size:1.15rem;font-weight:700" id="drwCustomer">Customer Name</h2>
    </div>
    <button type="button" class="btn btn-sm btn-secondary" onclick="closeOppDrawer()">&times;</button>
  </div>

  <div class="drawer-body">
    <!-- Stepper -->
    <div class="stage-stepper" id="drwStepper">
      <div class="step-node" onclick="advanceToStage('Lead')">
        <div class="step-dot">1</div>
        <span>Lead</span>
      </div>
      <div class="step-node" onclick="advanceToStage('Qualification')">
        <div class="step-dot">2</div>
        <span>Qual</span>
      </div>
      <div class="step-node" onclick="advanceToStage('Proposal')">
        <div class="step-dot">3</div>
        <span>Proposal</span>
      </div>
      <div class="step-node" onclick="advanceToStage('Negotiation')">
        <div class="step-dot">4</div>
        <span>Negot</span>
      </div>
      <div class="step-node" onclick="advanceToStage('Won')">
        <div class="step-dot">&check;</div>
        <span>Won</span>
      </div>
    </div>

    <!-- Commercial Highlight Box -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:18px;display:grid;grid-template-columns:repeat(3, 1fr);gap:10px">
      <div>
        <div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Monthly MRC</div>
        <div style="font-size:1.25rem;font-weight:700;color:var(--accent, #0F4C81)" id="drwMrc">$0</div>
        <div style="font-size:0.72rem;color:#94a3b8" id="drwMrcTzs">TZS 0</div>
      </div>
      <div>
        <div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Installation NRC</div>
        <div style="font-size:1.25rem;font-weight:700" id="drwNrc">$0</div>
        <div style="font-size:0.72rem;color:#94a3b8">One-time setup</div>
      </div>
      <div>
        <div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Total TCV</div>
        <div style="font-size:1.25rem;font-weight:700;color:#059669" id="drwTcv">$0</div>
        <div style="font-size:0.72rem;color:#94a3b8" id="drwTerm">12 Mo Term</div>
      </div>
    </div>

    <!-- Details Sections -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div>
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 8px;color:#334155">Service &amp; Technical Requirements</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.82rem">
          <div><span style="color:#64748b">Service:</span> <strong id="drwService">-</strong></div>
          <div><span style="color:#64748b">Bandwidth:</span> <strong id="drwBandwidth">-</strong></div>
          <div><span style="color:#64748b">Building:</span> <strong id="drwBuilding">-</strong></div>
          <div><span style="color:#64748b">City / Region:</span> <strong id="drwCity">-</strong></div>
          <div><span style="color:#64748b">GPS Coordinates:</span> <strong id="drwGps">-</strong></div>
          <div><span style="color:#64748b">Partner Channel:</span> <strong id="drwPartner">-</strong></div>
        </div>
      </div>

      <div style="border-top:1px solid #f1f5f9;padding-top:14px">
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 8px;color:#334155">Contact &amp; Account Governance</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.82rem">
          <div><span style="color:#64748b">Contact Person:</span> <strong id="drwContact">-</strong></div>
          <div><span style="color:#64748b">Phone:</span> <strong id="drwPhone">-</strong></div>
          <div><span style="color:#64748b">Email:</span> <strong id="drwEmail">-</strong></div>
          <div><span style="color:#64748b">Opportunity Owner:</span> <strong id="drwOwner">-</strong></div>
          <div><span style="color:#64748b">Target Close Date:</span> <strong id="drwCloseDate">-</strong></div>
          <div><span style="color:#64748b">Current Stage:</span> <span id="drwStageBadge" class="stage-pill stage-lead">Lead</span></div>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div style="border-top:1px solid #f1f5f9;padding-top:14px">
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 10px;color:#334155">Activity &amp; Stage Movement Audit</h4>
        <div id="drwTimeline" style="display:flex;flex-direction:column;gap:10px;font-size:0.8rem">
          <!-- Rendered dynamically -->
        </div>
      </div>
    </div>
  </div>

  <div class="drawer-footer">
    <div style="display:flex;align-items:center;gap:8px">
      <button type="button" class="btn btn-sm btn-outline-danger" id="drwDeleteBtn" onclick="deleteOpportunityFromDrawer()" style="display:none;color:#dc2626;border-color:#fca5a5" title="Permanently Delete Opportunity">&#128465; Delete</button>
      <button type="button" class="btn btn-sm btn-danger" onclick="markOpportunityLost()">Mark Lost</button>
      <button type="button" class="btn btn-sm btn-secondary" onclick="markOpportunityWon()">Mark Won &rarr; SO</button>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeOppDrawer()">Close</button>
      <button type="button" class="btn btn-sm btn-primary" onclick="advanceOpportunityNextStage()">Advance Stage &rarr;</button>
    </div>
  </div>
</div>

<!-- Floating Toast Notification -->
<div id="pipelineToast" class="pipeline-toast">
  <span id="pipelineToastMsg">Action completed</span>
</div>

<script>
// ============================================================
// Live In-Memory & LocalStorage Pipeline Data Store
// ============================================================
const USD_TZS_RATE = 2600;

// Seed initial realistic pipeline deals
const DEFAULT_OPPORTUNITIES = [
  // ── NNL Wholesale Opportunities ──
  {
    id: 101,
    ref: 'OPP-260824-001',
    company: 'NNL',
    customer: 'Magari Mtaaani Logistics',
    partner: 'Afrilink Solutions Ltd',
    contact: 'Hellen Msangi',
    phone: '0623700877',
    email: 'hellen@magarimtaaani.co.tz',
    building: 'Mwanga Tower, 3rd Floor',
    city: 'Dar es Salaam',
    gps: '-6.7835, 39.2685',
    service: 'Layer 2 Ethernet Point-to-Point',
    bandwidth: '150 Mbps',
    nrc: 136.15,
    mrc: 84.62,
    term: 12,
    stage: 'Won',
    closeDate: '2026-08-24',
    owner: 'John KAM',
    soRef: 'SO-260824-001',
    soStatus: 'Installation',
    projStage: 'Closed',
    activities: [
      { date: '2026-08-20', note: 'Lead captured from Mwenge expansion survey.' },
      { date: '2026-08-22', note: 'Feasibility confirmed by BSA (Distance ~300m).' },
      { date: '2026-08-24', note: 'Deal won! Countersigned SOF executed. Order moved to Project delivery.' }
    ]
  },
  {
    id: 102,
    ref: 'OPP-260824-002',
    company: 'NNL',
    customer: 'Kilimanjaro Commercial Bank',
    partner: 'FastNet Communications Ltd',
    contact: 'Baraka Mrope',
    phone: '0713887984',
    email: 'b.mrope@kcb-tz.com',
    building: 'Victoria House, 4th Floor',
    city: 'Dar es Salaam',
    gps: '-6.7720, 39.2450',
    service: 'Dedicated Internet Access (DIA)',
    bandwidth: '200 Mbps',
    nrc: 300.00,
    mrc: 1250.00,
    term: 24,
    stage: 'Negotiation',
    closeDate: '2026-10-30',
    owner: 'John KAM',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-09-02', note: 'Proposal submitted for dual-homed 200M DIA.' },
      { date: '2026-09-18', note: 'Negotiating volume discount with Commercial Director.' }
    ]
  },
  {
    id: 104,
    ref: 'OPP-260824-004',
    company: 'NNL',
    customer: 'Dar Marine Tech Ltd',
    partner: 'Afrilink Solutions Ltd',
    contact: 'Salim Kessy',
    phone: '0712000007',
    email: 'skessy@darmarine.co.tz',
    building: 'Harbour View Commercial Suite',
    city: 'Dar es Salaam',
    gps: '-6.8180, 39.2890',
    service: 'Colocation Rack Space',
    bandwidth: 'Full 42U Rack',
    nrc: 500.00,
    mrc: 850.00,
    term: 36,
    stage: 'Qualification',
    closeDate: '2026-11-20',
    owner: 'System Administrator',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-09-22', note: 'Qualified A+B dual power feeds requirement.' }
    ]
  },
  {
    id: 106,
    ref: 'OPP-260824-006',
    company: 'NNL',
    customer: 'Sprint POP Interconnect',
    partner: 'SPRINT TANZANIA LIMITED',
    contact: 'Dennis Temba',
    phone: '0784992211',
    email: 'd.temba@sprint.co.tz',
    building: 'Makumbusho Central Node',
    city: 'Dar es Salaam',
    gps: '-6.7760, 39.2480',
    service: 'Dark Fiber Core Link',
    bandwidth: '4 Core Pair',
    nrc: 800.00,
    mrc: 2200.00,
    term: 36,
    stage: 'Proposal',
    closeDate: '2026-11-25',
    owner: 'John KAM',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-10-01', note: 'Core optical loop RFP response delivered.' }
    ]
  },
  {
    id: 107,
    ref: 'OPP-260824-007',
    company: 'NNL',
    customer: 'Afrilink Metro Substation Ring',
    partner: 'Afrilink Solutions Ltd',
    contact: 'Amani Joseph',
    phone: '0715667788',
    email: 'ajoseph@afrilink.tz',
    building: 'Tegeta Distribution Hub',
    city: 'Dar es Salaam',
    gps: '-6.6710, 39.1950',
    service: 'Metro Ring Transport (10G)',
    bandwidth: '10 Gbps',
    nrc: 1200.00,
    mrc: 3400.00,
    term: 24,
    stage: 'Lead',
    closeDate: '2026-12-10',
    owner: 'Sarah BSA',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-10-10', note: 'Capacity expansion request received.' }
    ]
  },

  // ── Savanna Enterprise SBE Opportunities ──
  {
    id: 103,
    ref: 'OPP-SBE-260824-003',
    company: 'SBE',
    customer: 'Serengeti Agro Industries',
    partner: 'Direct Enterprise',
    contact: 'Edward Lowassa',
    phone: '0755489475',
    email: 'elowassa@serengetiagro.com',
    building: 'Nyerere Road Industrial Zone',
    city: 'Dar es Salaam',
    gps: '-6.8520, 39.2310',
    service: 'FTTH Wholesale Fiber Connection',
    bandwidth: '100 Mbps',
    nrc: 150.00,
    mrc: 380.00,
    term: 12,
    stage: 'Proposal',
    closeDate: '2026-11-15',
    owner: 'Sarah BSA',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-09-10', note: 'Site survey completed. Splicing required at DP-04.' }
    ]
  },
  {
    id: 105,
    ref: 'OPP-SBE-260824-005',
    company: 'SBE',
    customer: 'Zanzibar Trading Hub',
    partner: 'Direct Enterprise',
    contact: 'Mwanahawa Juma',
    phone: '0777123456',
    email: 'juma@zanztra.co.tz',
    building: 'Malindi Port Complex',
    city: 'Zanzibar',
    gps: '-6.1580, 39.1920',
    service: 'Dedicated Internet Access (DIA)',
    bandwidth: '50 Mbps',
    nrc: 250.00,
    mrc: 420.00,
    term: 12,
    stage: 'Lead',
    closeDate: '2026-12-05',
    owner: 'John KAM',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-09-28', note: 'Initial enquiry from website lead capture.' }
    ]
  },
  {
    id: 108,
    ref: 'OPP-SBE-260824-008',
    company: 'SBE',
    customer: 'Bakhresa Milling Group HQ',
    partner: 'Direct Enterprise',
    contact: 'Mohamed Said',
    phone: '0714223344',
    email: 'msaid@bakhresa.com',
    building: 'Tazara Commercial Center',
    city: 'Dar es Salaam',
    gps: '-6.8340, 39.2480',
    service: 'Enterprise SD-WAN & Primary Fiber',
    bandwidth: '300 Mbps',
    nrc: 450.00,
    mrc: 2100.00,
    term: 36,
    stage: 'Won',
    closeDate: '2026-09-15',
    owner: 'John KAM',
    soRef: 'SO-SBE-260824-001',
    soStatus: 'Installation',
    projStage: 'Installation',
    activities: [
      { date: '2026-09-01', note: 'Executive RFP awarded to Savanna Enterprise.' },
      { date: '2026-09-15', note: 'Service contract executed. CPE delivery dispatched.' }
    ]
  },
  {
    id: 109,
    ref: 'OPP-SBE-260824-009',
    company: 'SBE',
    customer: 'Acrobat Logistics Central Office',
    partner: 'Direct Enterprise',
    contact: 'Grace Mallya',
    phone: '0767554433',
    email: 'gmallya@acrobatlogistics.co.tz',
    building: 'Kurasini Logistics Park',
    city: 'Dar es Salaam',
    gps: '-6.8450, 39.2780',
    service: 'Dedicated Cloud Connect',
    bandwidth: '100 Mbps',
    nrc: 300.00,
    mrc: 980.00,
    term: 24,
    stage: 'Negotiation',
    closeDate: '2026-10-28',
    owner: 'Sarah BSA',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-09-25', note: 'Cross-connect latency benchmarks verified at 12ms.' }
    ]
  },
  {
    id: 110,
    ref: 'OPP-SBE-260824-010',
    company: 'SBE',
    customer: 'Heritage Hotels & Lodges',
    partner: 'Direct Enterprise',
    contact: 'Rashid Bakari',
    phone: '0754112233',
    email: 'rbakari@heritagehotels.co.tz',
    building: 'Posta Kivukoni Suite',
    city: 'Dar es Salaam',
    gps: '-6.8150, 39.2940',
    service: 'Managed Hospitality Wi-Fi & DIA',
    bandwidth: '80 Mbps',
    nrc: 350.00,
    mrc: 650.00,
    term: 12,
    stage: 'Qualification',
    closeDate: '2026-11-18',
    owner: 'John KAM',
    soRef: null,
    soStatus: null,
    projStage: null,
    activities: [
      { date: '2026-10-05', note: 'Feasibility survey scheduled for main lobby and guest rooms.' }
    ]
  }
];

// Load from localStorage or seed
let pipelineOpportunities = [];
function loadPipelineData() {
  try {
    const saved = localStorage.getItem('neilos_pipeline_opportunities_v3');
    if (saved) {
      pipelineOpportunities = JSON.parse(saved);
    } else {
      pipelineOpportunities = JSON.parse(JSON.stringify(DEFAULT_OPPORTUNITIES));
      localStorage.setItem('neilos_pipeline_opportunities_v3', JSON.stringify(pipelineOpportunities));
    }
  } catch (e) {
    pipelineOpportunities = JSON.parse(JSON.stringify(DEFAULT_OPPORTUNITIES));
  }
}
loadPipelineData();

function savePipelineData() {
  try {
    localStorage.setItem('neilos_pipeline_opportunities_v3', JSON.stringify(pipelineOpportunities));
  } catch (e) {}
}

// Reset Pipeline Data
async function confirmResetPipelineData() {
  const msg = 'Are you sure you want to reset all pipeline data to the default clean state? This will clear any session changes.';
  let confirmed = false;
  if (typeof neilosConfirm === 'function') {
    confirmed = await neilosConfirm(msg, 'Reset Pipeline Data', 'Proceed', 'btn-danger', '⚠️');
  } else {
    confirmed = window.confirm(msg);
  }
  if (confirmed) {
    localStorage.removeItem('neilos_pipeline_opportunities_v3');
    localStorage.removeItem('neilos_pipeline_opportunities_v2');
    localStorage.removeItem('neilos_pipeline_opportunities');
    pipelineOpportunities = JSON.parse(JSON.stringify(DEFAULT_OPPORTUNITIES));
    savePipelineData();
    showPipelineTab('dashboard');
    renderDashboard();
    renderOpportunitiesTable();
    renderSoTable();
    renderProjectsTable();
    showPipelineToast('Pipeline demo data reset to default state.');
  }
}

let currentCompany = 'NNL';
try {
  currentCompany = localStorage.getItem('neilos_pipeline_current_company') || 'NNL';
} catch (e) {
  currentCompany = 'NNL';
}
let activeDrawerOppId = null;
let currentOppPeriod = 'all';

// Toast feedback
function showPipelineToast(msg) {
  const t = document.getElementById('pipelineToast');
  const m = document.getElementById('pipelineToastMsg');
  if (t && m) {
    m.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
  }
}

// Company Switch
function switchPipelineCompany(company) {
  currentCompany = company;
  try {
    localStorage.setItem('neilos_pipeline_current_company', company);
  } catch (e) {}
  document.getElementById('btnNNL').classList.toggle('active', company === 'NNL');
  document.getElementById('btnSBE').classList.toggle('active', company === 'SBE');
  document.getElementById('companyBadge').textContent = company === 'NNL' ? 'NNL Wholesale' : 'Savanna Enterprise SBE';

  const shell = document.getElementById('pipelineShell') || document.querySelector('.pipeline-shell');
  if (shell) {
    shell.classList.toggle('theme-sbe', company === 'SBE');
  }

  renderDashboard();
  renderOpportunitiesTable();
  renderSoTable();
  renderProjectsTable();
  renderReportPreview();
  showPipelineToast(`Switched view to ${company === 'NNL' ? 'NNL Network Wholesale' : 'Savanna Business Enterprise (SBE)'}`);
}

const userCanDeleteOpp = <?= json_encode($canDelete) ?>;
let activeSimulatedRole = '<?= addslashes($currentUserRole) ?>';
function canUserDelete() {
  // Authorized roles: System Admin, Management (exact DB role names)
  const authorizedRoles = ['System Admin', 'Management', 'Administrator', 'Admin'];
  return userCanDeleteOpp || authorizedRoles.includes(activeSimulatedRole);
}

function switchRoleContext(role) {
  activeSimulatedRole = role;
  renderDashboard();
  renderOpportunitiesTable();
  renderSoTable();
  showPipelineToast(`Perspective filtered for: ${role}`);
}

// Tab Switching
function showPipelineTab(tabKey, btnElement) {
  document.querySelectorAll('.pipeline-nav-btn').forEach(b => b.classList.remove('active'));
  if (btnElement) {
    btnElement.classList.add('active');
  } else {
    const btn = Array.from(document.querySelectorAll('.pipeline-nav-btn')).find(b => b.textContent.toLowerCase().includes(tabKey.replace('_', ' ')));
    if (btn) btn.classList.add('active');
  }

  document.querySelectorAll('.pipeline-tab-pane').forEach(p => p.style.display = 'none');
  const target = document.getElementById('tab-' + tabKey);
  if (target) target.style.display = 'block';

  if (tabKey === 'dashboard') renderDashboard();
  if (tabKey === 'opportunities') renderOpportunitiesTable();
  if (tabKey === 'so_management') renderSoTable();
  if (tabKey === 'projects') renderProjectsTable();
  if (tabKey === 'reports') renderReportPreview();
}

// Stage Pill Helper
function getStageBadge(stage) {
  const map = {
    'Lead': 'stage-lead',
    'Qualification': 'stage-qualification',
    'Proposal': 'stage-proposal',
    'Negotiation': 'stage-negotiation',
    'Won': 'stage-won',
    'Lost': 'stage-lost'
  };
  const cls = map[stage] || 'stage-lead';
  return `<span class="stage-pill ${cls}">${stage}</span>`;
}

// Render Dashboard
function renderDashboard() {
  const opps = pipelineOpportunities.filter(o => o.company === currentCompany);
  const total = opps.length;
  const won = opps.filter(o => o.stage === 'Won').length;
  const winRate = total > 0 ? Math.round((won / total) * 100) : 0;

  let totalMrc = 0;
  let totalTcv = 0;
  let weightedVal = 0;
  let soDeals = 0;
  let overdueCount = 0;

  const weights = { 'Lead': 0.1, 'Qualification': 0.25, 'Proposal': 0.5, 'Negotiation': 0.75, 'Won': 1.0, 'Lost': 0 };
  const today = new Date().toISOString().split('T')[0];

  opps.forEach(o => {
    if (o.stage !== 'Lost') {
      const m = parseFloat(o.mrc) || 0;
      const n = parseFloat(o.nrc) || 0;
      const t = parseInt(o.term) || 12;
      const tcv = (m * t) + n;

      totalMrc += m;
      totalTcv += tcv;
      weightedVal += (tcv * (weights[o.stage] || 0.1));

      if (o.stage === 'Won') soDeals++;
      if (o.closeDate && o.closeDate < today && o.stage !== 'Won') overdueCount++;
    }
  });

  document.getElementById('kpiTotalDeals').textContent = total;
  document.getElementById('kpiPipelineMrc').textContent = '$' + totalMrc.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('kpiPipelineMrcTzs').textContent = 'TZS ' + Math.round(totalMrc * USD_TZS_RATE).toLocaleString('en-US');
  document.getElementById('kpiPipelineTcv').textContent = '$' + Math.round(totalTcv).toLocaleString('en-US');
  document.getElementById('kpiWeightedVal').textContent = 'Weighted: $' + Math.round(weightedVal).toLocaleString('en-US');
  document.getElementById('kpiWinRate').textContent = winRate + '%';
  document.getElementById('kpiWonCount').textContent = won + ' deals won';
  document.getElementById('kpiSoDeals').textContent = soDeals;
  document.getElementById('kpiOverdueCount').textContent = overdueCount;
  document.getElementById('badgeOverdueCount').textContent = overdueCount;

  // Counts for tabs
  document.getElementById('tabOppCount').textContent = total;
  document.getElementById('tabSoCount').textContent = soDeals;
  document.getElementById('tabProjCount').textContent = opps.filter(o => o.projStage).length;

  // Render Funnel Bars
  const stages = ['Lead', 'Qualification', 'Proposal', 'Negotiation', 'Won'];
  const funnelContainer = document.getElementById('stageFunnelBars');
  if (funnelContainer) {
    let funnelHtml = '';
    stages.forEach(st => {
      const count = opps.filter(o => o.stage === st).length;
      const pct = total > 0 ? Math.round((count / total) * 100) : 0;
      const val = opps.filter(o => o.stage === st).reduce((sum, o) => sum + (parseFloat(o.mrc) || 0), 0);
      funnelHtml += `
        <div>
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
            <span><strong>${st}</strong> (${count} deals)</span>
            <span style="color:var(--text-secondary)">$${Math.round(val).toLocaleString()}/mo &bull; ${pct}%</span>
          </div>
          <div style="height:9px;background:#e2e8f0;border-radius:999px;overflow:hidden">
            <div style="height:100%;width:${Math.max(pct, 4)}%;background:var(--accent, #0F4C81);border-radius:999px"></div>
          </div>
        </div>
      `;
    });
    funnelContainer.innerHTML = funnelHtml;
  }

  // Render Product Mix
  const prodContainer = document.getElementById('productMixContainer');
  if (prodContainer) {
    const serviceCounts = {};
    opps.forEach(o => {
      const s = o.service || 'Other';
      serviceCounts[s] = (serviceCounts[s] || 0) + 1;
    });

    let mixHtml = '';
    Object.keys(serviceCounts).forEach(srv => {
      const c = serviceCounts[srv];
      const p = total > 0 ? Math.round((c / total) * 100) : 0;
      mixHtml += `
        <div>
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:4px">
            <span style="font-weight:600">${srv}</span>
            <span style="color:var(--text-secondary)">${c} (${p}%)</span>
          </div>
          <div style="height:7px;background:#e2e8f0;border-radius:999px;overflow:hidden">
            <div style="height:100%;width:${p}%;background:#3B82F6;border-radius:999px"></div>
          </div>
        </div>
      `;
    });
    prodContainer.innerHTML = mixHtml;
  }

  // Render Overdue Follow-ups table
  const overdueTbody = document.getElementById('overdueTableBody');
  if (overdueTbody) {
    const overdueList = opps.filter(o => o.closeDate && o.closeDate < today && o.stage !== 'Won' && o.stage !== 'Lost');
    if (overdueList.length === 0) {
      overdueTbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px">All pipeline follow-ups are up to date! &#127881;</td></tr>`;
    } else {
      let trs = '';
      overdueList.forEach(o => {
        trs += `
          <tr>
            <td><strong>${escapeHtml(o.customer)}</strong></td>
            <td>${getStageBadge(o.stage)}</td>
            <td style="color:#dc2626;font-weight:600">${o.closeDate}</td>
            <td>${escapeHtml(o.owner || '-')}</td>
            <td>
              <button class="btn btn-sm btn-secondary" onclick="openOppDrawer('${o.id}')">Follow Up</button>
              ${canUserDelete() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteOpportunity('${o.id}')" title="Delete Opportunity" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626;margin-left:4px">&#128465;</button>` : ''}
            </td>
          </tr>
        `;
      });
      overdueTbody.innerHTML = trs;
    }
  }
}

// Render Opportunities Table
function renderOpportunitiesTable() {
  const search = (document.getElementById('oppSearch')?.value || '').toLowerCase().trim();
  const stageFilt = document.getElementById('oppStageFilter')?.value || 'All';
  const serviceFilt = document.getElementById('oppServiceFilter')?.value || 'All';

  const tbody = document.getElementById('oppTableBody');
  if (!tbody) return;

  const now = new Date();
  const curYm = now.toISOString().slice(0, 7);
  const prevMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().slice(0, 7);

  let filtered = pipelineOpportunities.filter(o => {
    // Company match
    if (o.company !== currentCompany) return false;
    // Stage Filter
    if (stageFilt !== 'All' && o.stage !== stageFilt) return false;
    // Service Filter
    if (serviceFilt !== 'All' && !o.service.toLowerCase().includes(serviceFilt.toLowerCase())) return false;
    // Date period filter
    if (currentOppPeriod === 'thisMonth') {
      if (!o.closeDate || !o.closeDate.startsWith(curYm)) return false;
    } else if (currentOppPeriod === 'lastMonth') {
      if (!o.closeDate || !o.closeDate.startsWith(prevMonth)) return false;
    }
    // Multi-attribute search
    if (search) {
      const hay = [
        o.customer, o.partner, o.contact, o.phone, o.email, o.building, o.city, o.gps, o.ref, o.service
      ].join(' ').toLowerCase();
      if (!hay.includes(search)) return false;
    }
    return true;
  });

  document.getElementById('oppFilteredCount').textContent = filtered.length;

  let sumNrc = 0, sumMrc = 0;
  filtered.forEach(o => {
    sumNrc += (parseFloat(o.nrc) || 0);
    sumMrc += (parseFloat(o.mrc) || 0);
  });
  document.getElementById('sumFilteredNrc').textContent = '$' + sumNrc.toLocaleString('en-US', {minimumFractionDigits:2});
  document.getElementById('sumFilteredMrc').textContent = '$' + sumMrc.toLocaleString('en-US', {minimumFractionDigits:2});
  document.getElementById('sumFilteredArr').textContent = '$' + (sumMrc * 12).toLocaleString('en-US', {minimumFractionDigits:2});

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:36px;color:#94a3b8">No matching opportunities found.</td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach(o => {
    html += `
      <tr style="cursor:pointer" onclick="openOppDrawer(${o.id})">
        <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(o.ref)}</strong></td>
        <td>
          <div style="font-weight:600">${escapeHtml(o.customer)}</div>
          ${o.partner ? `<div style="font-size:0.75rem;color:#64748b">via ${escapeHtml(o.partner)}</div>` : ''}
        </td>
        <td>
          <div>${escapeHtml(o.building)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">${escapeHtml(o.city || '')} ${o.gps ? `&bull; ${escapeHtml(o.gps)}` : ''}</div>
        </td>
        <td>
          <div>${escapeHtml(o.service)}</div>
          <div style="font-size:0.72rem;color:#64748b">${escapeHtml(o.bandwidth || '')}</div>
        </td>
        <td>${getStageBadge(o.stage)}</td>
        <td style="text-align:right">$${parseFloat(o.nrc || 0).toFixed(2)}</td>
        <td style="text-align:right;font-weight:700;color:var(--accent, #0F4C81)">$${parseFloat(o.mrc || 0).toFixed(2)}</td>
        <td>${o.closeDate || '-'}</td>
        <td>${escapeHtml(o.owner || '-')}</td>
        <td style="text-align:center" onclick="event.stopPropagation()">
          <div style="display:inline-flex;align-items:center;gap:6px">
            <button class="btn btn-sm btn-secondary" onclick="openOppDrawer('${o.id}')">Details &rarr;</button>
            ${canUserDelete() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteOpportunity('${o.id}')" title="Delete Opportunity" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626">&#128465;</button>` : ''}
          </div>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

// Render SO Management Table
function renderSoTable() {
  const tbody = document.getElementById('soTableBody');
  if (!tbody) return;

  const filt = document.getElementById('soStatusFilter')?.value || 'All';
  const soOpps = pipelineOpportunities.filter(o => o.company === currentCompany && (o.stage === 'Won' || o.soRef));

  let filtered = soOpps;
  if (filt !== 'All') {
    filtered = soOpps.filter(o => (o.soStatus || 'SOF Sent') === filt);
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:36px;color:#94a3b8">No service orders in this state. Advance won opportunities to create SOs.</td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach(o => {
    const status = o.soStatus || 'SOF Sent';
    const statusCls = status === 'Closed' ? 'badge-success' : (status === 'Installation' ? 'badge-info' : 'badge-warning');
    html += `
      <tr>
        <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(o.soRef || 'SO-' + o.id)}</strong></td>
        <td>
          <div>${escapeHtml(o.customer)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">Ref: ${escapeHtml(o.ref)}</div>
        </td>
        <td>${escapeHtml(o.partner || 'Direct Enterprise')}</td>
        <td>${escapeHtml(o.service)} (${escapeHtml(o.bandwidth || 'Standard')})</td>
        <td>${o.term || 12} Months</td>
        <td style="text-align:right">$${parseFloat(o.nrc || 0).toFixed(2)}</td>
        <td style="text-align:right;font-weight:700">$${parseFloat(o.mrc || 0).toFixed(2)}</td>
        <td><span class="badge ${statusCls}">${status}</span></td>
        <td>
          ${status === 'Installation' || status === 'Closed'
            ? `<span style="color:#059669;font-weight:600">&check; Handed to Project</span>`
            : `<button class="btn btn-sm btn-primary" onclick="submitToProject(${o.id})">Hand Over to Project</button>`}
        </td>
        <td style="text-align:center" onclick="event.stopPropagation()">
          <div style="display:inline-flex;align-items:center;gap:6px">
            <button class="btn btn-sm btn-secondary" onclick="simulateSofDownload('${escapeHtml(o.customer)}', '${escapeHtml(o.soRef || o.ref)}')">Download SOF</button>
            ${canUserDelete() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteOpportunity('${o.id}')" title="Delete Order" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626">&#128465;</button>` : ''}
          </div>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function simulateSofDownload(customer, ref) {
  showPipelineToast(`Service Order Form generated for ${customer} (${ref}). Download started.`);
  // Open SOF print window simulation
  const win = window.open('', '_blank');
  if (win) {
    win.document.write(`
      <html><head><title>SOF - ${customer}</title><style>body{font-family:Arial,sans-serif;padding:30px;color:#333}h2{color:#0F4C81}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #ddd;padding:10px;text-align:left}</style></head>
      <body>
        <h2>Neilos Network &bull; Service Order Form (SOF)</h2>
        <p><strong>Reference:</strong> ${ref} &bull; <strong>Customer:</strong> ${customer} &bull; <strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
        <hr/>
        <p>This executed SOF confirms service parameters and commercial authorization for network provisioning.</p>
        <table><tr><th>Service Specification</th><th>Details</th></tr><tr><td>Status</td><td>Executed / Authorized</td></tr><tr><td>Signatory</td><td>Authorized Neilos & Customer</td></tr></table>
        <br/><button onclick="window.print()">Print / Save as PDF</button>
      </body></html>
    `);
    win.document.close();
  }
}

// Render Projects Table
function renderProjectsTable() {
  const tbody = document.getElementById('projectsTableBody');
  if (!tbody) return;

  const projOpps = pipelineOpportunities.filter(o => o.company === currentCompany && (o.projStage || o.soStatus === 'Installation' || o.stage === 'Won'));

  if (projOpps.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:36px;color:#94a3b8">No projects currently in execution.</td></tr>`;
    return;
  }

  let html = '';
  projOpps.forEach(o => {
    const stage = o.projStage || 'Installation';
    const pct = stage === 'Closed' ? 100 : (stage === 'UAT' ? 75 : (stage === 'Testing' ? 50 : 25));
    html += `
      <tr>
        <td><strong>${escapeHtml(o.soRef || 'ORD-' + o.id)}</strong></td>
        <td>
          <div style="font-weight:600">${escapeHtml(o.customer)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">${escapeHtml(o.building)}</div>
        </td>
        <td>${escapeHtml(o.service)}</td>
        <td>Hamis (Fiber Works Ltd)</td>
        <td>${o.closeDate || 'In Progress'}</td>
        <td><span class="badge badge-info">${stage}</span></td>
        <td style="min-width:120px">
          <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden">
              <div style="height:100%;width:${pct}%;background:#10B981"></div>
            </div>
            <span style="font-size:0.72rem;font-weight:600">${pct}%</span>
          </div>
        </td>
        <td style="text-align:center">
          <button class="btn btn-sm btn-secondary" onclick="advanceProjectStage(${o.id})">Update Stage</button>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

// Drawer Open / Close / Stages
function openOppDrawer(id) {
  const opp = pipelineOpportunities.find(o => o.id == id);
  if (!opp) return;

  activeDrawerOppId = id;
  document.getElementById('drwRef').textContent = opp.ref;
  document.getElementById('drwCustomer').textContent = opp.customer;
  document.getElementById('drwMrc').textContent = '$' + parseFloat(opp.mrc || 0).toFixed(2);
  document.getElementById('drwMrcTzs').textContent = 'TZS ' + Math.round((opp.mrc || 0) * USD_TZS_RATE).toLocaleString();
  document.getElementById('drwNrc').textContent = '$' + parseFloat(opp.nrc || 0).toFixed(2);
  const term = parseInt(opp.term) || 12;
  const tcv = ((opp.mrc || 0) * term) + (opp.nrc || 0);
  document.getElementById('drwTcv').textContent = '$' + Math.round(tcv).toLocaleString();
  document.getElementById('drwTerm').textContent = term + ' Mo Contract';

  document.getElementById('drwService').textContent = opp.service;
  document.getElementById('drwBandwidth').textContent = opp.bandwidth || 'Standard';
  document.getElementById('drwBuilding').textContent = opp.building;
  document.getElementById('drwCity').textContent = opp.city || 'Dar es Salaam';
  document.getElementById('drwGps').innerHTML = opp.gps 
    ? `<a href="https://maps.google.com/?q=${encodeURIComponent(opp.gps)}" target="_blank" style="color:var(--accent)">${escapeHtml(opp.gps)} &mapstoup;</a>`
    : '-';
  document.getElementById('drwPartner').textContent = opp.partner || 'Direct Enterprise Client';

  document.getElementById('drwContact').textContent = opp.contact || '-';
  document.getElementById('drwPhone').textContent = opp.phone || '-';
  document.getElementById('drwEmail').textContent = opp.email || '-';
  document.getElementById('drwOwner').textContent = opp.owner || '-';
  document.getElementById('drwCloseDate').textContent = opp.closeDate || '-';
  document.getElementById('drwStageBadge').innerHTML = getStageBadge(opp.stage);

  const delBtn = document.getElementById('drwDeleteBtn');
  if (delBtn) {
    delBtn.style.display = canUserDelete() ? 'inline-flex' : 'none';
  }

  // Stepper Visual
  const stageList = ['Lead', 'Qualification', 'Proposal', 'Negotiation', 'Won'];
  const curIdx = stageList.indexOf(opp.stage);
  const nodes = document.querySelectorAll('#drwStepper .step-node');
  nodes.forEach((n, idx) => {
    n.classList.remove('completed', 'current');
    if (idx < curIdx) n.classList.add('completed');
    else if (idx === curIdx) n.classList.add('current');
  });

  // Timeline
  const tl = document.getElementById('drwTimeline');
  if (tl) {
    let tHtml = '';
    (opp.activities || []).forEach(act => {
      tHtml += `
        <div style="border-left:2px solid var(--accent, #0F4C81);padding-left:12px;margin-bottom:8px">
          <div style="font-size:0.72rem;color:#94a3b8;font-weight:600">${act.date}</div>
          <div style="margin-top:2px;color:#334155">${escapeHtml(act.note)}</div>
        </div>
      `;
    });
    tl.innerHTML = tHtml || '<div style="color:#94a3b8">No recent log entries.</div>';
  }

  document.getElementById('oppDrawer').classList.add('active');
  document.getElementById('oppDrawerBackdrop').classList.add('active');
}

function closeOppDrawer() {
  document.getElementById('oppDrawer').classList.remove('active');
  document.getElementById('oppDrawerBackdrop').classList.remove('active');
  activeDrawerOppId = null;
}

function advanceOpportunityNextStage() {
  if (!activeDrawerOppId) return;
  const opp = pipelineOpportunities.find(o => o.id == activeDrawerOppId);
  if (!opp) return;

  const stageOrder = ['Lead', 'Qualification', 'Proposal', 'Negotiation', 'Won'];
  const idx = stageOrder.indexOf(opp.stage);
  if (idx < stageOrder.length - 1) {
    const next = stageOrder[idx + 1];
    opp.stage = next;
    opp.activities = opp.activities || [];
    opp.activities.unshift({
      date: new Date().toISOString().split('T')[0],
      note: `Advanced to ${next} stage.`
    });
    if (next === 'Won') {
      opp.soRef = (opp.company === 'SBE' ? 'SO-SBE-' : 'SO-') + opp.id;
      opp.soStatus = 'SOF Sent';
    }
    savePipelineData();
    openOppDrawer(opp.id);
    renderDashboard();
    renderOpportunitiesTable();
    showPipelineToast(`Opportunity advanced to ${next}`);
  }
}

function advanceToStage(targetStage) {
  if (!activeDrawerOppId) return;
  const opp = pipelineOpportunities.find(o => o.id == activeDrawerOppId);
  if (!opp) return;

  opp.stage = targetStage;
  opp.activities = opp.activities || [];
  opp.activities.unshift({
    date: new Date().toISOString().split('T')[0],
    note: `Moved to ${targetStage} stage.`
  });
  if (targetStage === 'Won') {
    opp.soRef = (opp.company === 'SBE' ? 'SO-SBE-' : 'SO-') + opp.id;
    opp.soStatus = 'SOF Sent';
  }
  savePipelineData();
  openOppDrawer(opp.id);
  renderDashboard();
  renderOpportunitiesTable();
  showPipelineToast(`Opportunity stage updated to ${targetStage}`);
}

function markOpportunityWon() {
  advanceToStage('Won');
  showPipelineToast('Opportunity marked as WON! Created Service Order entry.');
}

async function deleteOpportunity(id) {
  if (!canUserDelete()) {
    showPipelineToast('Permission denied: Only Admin and Management can delete opportunities.');
    return;
  }
  // Use == (loose) to handle numeric seed IDs vs string IDs from localStorage
  const opp = pipelineOpportunities.find(o => o.id == id);
  if (!opp) return;

  const msg = `Are you sure you want to permanently delete opportunity "${opp.ref} — ${opp.customer}"? This action cannot be undone.`;
  let confirmed = false;
  if (typeof neilosConfirm === 'function') {
    confirmed = await neilosConfirm(msg, 'Delete Opportunity', 'Proceed', 'btn-danger', '⚠️');
  } else {
    confirmed = window.confirm(msg);
  }

  if (confirmed) {
    pipelineOpportunities = pipelineOpportunities.filter(o => o.id != id);
    savePipelineData();
    if (activeDrawerOppId == id) {
      closeOppDrawer();
    }
    renderDashboard();
    renderOpportunitiesTable();
    renderSoTable();
    renderProjectsTable();
    renderReportPreview();
    showPipelineToast(`Opportunity ${opp.ref} deleted permanently.`);
  }
}

function deleteOpportunityFromDrawer() {
  if (activeDrawerOppId) {
    deleteOpportunity(activeDrawerOppId);
  }
}

function markOpportunityLost() {
  advanceToStage('Lost');
  showPipelineToast('Opportunity marked as Lost.');
}

function submitToProject(id) {
  const opp = pipelineOpportunities.find(o => o.id == id);
  if (!opp) return;
  opp.soStatus = 'Installation';
  opp.projStage = 'Installation';
  savePipelineData();
  renderSoTable();
  renderProjectsTable();
  showPipelineToast(`Order ${opp.soRef || opp.ref} released to Project Installation!`);
}

function advanceProjectStage(id) {
  const opp = pipelineOpportunities.find(o => o.id == id);
  if (!opp) return;
  const stages = ['Installation', 'Testing', 'UAT', 'Closed'];
  const idx = stages.indexOf(opp.projStage || 'Installation');
  if (idx < stages.length - 1) {
    opp.projStage = stages[idx + 1];
    if (opp.projStage === 'Closed') opp.soStatus = 'Closed';
    savePipelineData();
    renderProjectsTable();
    renderSoTable();
    showPipelineToast(`Project stage updated to ${opp.projStage}`);
  }
}

// Modal open/close
function openNewOpportunityModal() {
  const title = document.getElementById('newOppModalTitle');
  if (title) {
    title.textContent = `Capture New Opportunity — ${currentCompany === 'NNL' ? 'NNL Wholesale' : 'Savanna Enterprise SBE'}`;
  }
  const partnerSel = document.getElementById('inpPartner');
  if (partnerSel && currentCompany === 'SBE') {
    partnerSel.value = '';
  }
  document.getElementById('newOppBackdrop').classList.add('active');
}
function closeNewOpportunityModal() {
  document.getElementById('newOppBackdrop').classList.remove('active');
}
function closeNewOppOnBackdrop(e) {
  if (e.target === document.getElementById('newOppBackdrop')) {
    closeNewOpportunityModal();
  }
}

function applyDefaultServicePricing(serviceName) {
  const nrcInp = document.getElementById('inpNrc');
  const mrcInp = document.getElementById('inpMrc');
  if (serviceName.includes('DIA')) {
    nrcInp.value = '300.00'; mrcInp.value = '550.00';
  } else if (serviceName.includes('Layer 2')) {
    nrcInp.value = '250.00'; mrcInp.value = '400.00';
  } else if (serviceName.includes('FTTH')) {
    nrcInp.value = '60.00'; mrcInp.value = '180.00';
  } else if (serviceName.includes('Colocation')) {
    nrcInp.value = '500.00'; mrcInp.value = '800.00';
  } else {
    nrcInp.value = '150.00'; mrcInp.value = '300.00';
  }
}

function handleCreateOpportunity(e) {
  e.preventDefault();
  const nextId = Date.now();
  const isSbe = (currentCompany === 'SBE');
  const newOpp = {
    id: nextId,
    ref: (isSbe ? 'OPP-SBE-' : 'OPP-') + String(nextId).slice(-6),
    company: currentCompany || 'NNL',
    customer: document.getElementById('inpCustomer').value,
    partner: document.getElementById('inpPartner').value || (isSbe ? 'Direct Enterprise' : 'NNL Wholesale Partner'),
    contact: document.getElementById('inpContact').value,
    phone: document.getElementById('inpPhone').value,
    email: document.getElementById('inpEmail').value,
    building: document.getElementById('inpBuilding').value,
    city: document.getElementById('inpCity').value,
    gps: document.getElementById('inpGps').value,
    service: document.getElementById('inpService').value,
    bandwidth: document.getElementById('inpBandwidth').value,
    nrc: parseFloat(document.getElementById('inpNrc').value) || 0,
    mrc: parseFloat(document.getElementById('inpMrc').value) || 0,
    term: parseInt(document.getElementById('inpTerm').value) || 12,
    stage: document.getElementById('inpStage').value,
    closeDate: document.getElementById('inpCloseDate').value,
    owner: document.getElementById('inpOwner').value,
    activities: [
      { date: new Date().toISOString().split('T')[0], note: `Opportunity created under ${isSbe ? 'Savanna Enterprise (SBE)' : 'NNL Wholesale'}.` }
    ]
  };

  pipelineOpportunities.unshift(newOpp);
  savePipelineData();
  closeNewOpportunityModal();
  document.getElementById('newOppForm').reset();
  renderDashboard();
  renderOpportunitiesTable();
  showPipelineTab('opportunities');
  showPipelineToast(`New opportunity created: ${newOpp.ref} - ${newOpp.customer}`);
}

// Period Filter
function setOppPeriod(preset, btn) {
  currentOppPeriod = preset;
  document.querySelectorAll('#tab-opportunities .btn-sm').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  if (btn) {
    btn.classList.add('btn-primary');
    btn.classList.remove('btn-secondary');
  }
  renderOpportunitiesTable();
}

// Reports & CSV Export
function renderReportPreview() {
  const tbody = document.getElementById('reportPreviewBody');
  if (!tbody) return;

  const stage = document.getElementById('reportStage')?.value || 'All';
  const owner = document.getElementById('reportOwner')?.value || 'All';
  const fromYm = document.getElementById('reportFrom')?.value || '';
  const toYm = document.getElementById('reportTo')?.value || '';

  let filtered = pipelineOpportunities.filter(o => {
    if (o.company !== currentCompany) return false;
    if (stage !== 'All' && o.stage !== stage) return false;
    if (owner !== 'All' && o.owner !== owner) return false;
    if (fromYm && o.closeDate && o.closeDate.slice(0, 7) < fromYm) return false;
    if (toYm && o.closeDate && o.closeDate.slice(0, 7) > toYm) return false;
    return true;
  });

  const weights = { 'Lead': '10%', 'Qualification': '25%', 'Proposal': '50%', 'Negotiation': '75%', 'Won': '100%', 'Lost': '0%' };

  let html = '';
  filtered.forEach(o => {
    const term = parseInt(o.term) || 12;
    const tcv = ((o.mrc || 0) * term) + (o.nrc || 0);
    html += `
      <tr>
        <td><strong>${escapeHtml(o.ref)}</strong></td>
        <td>${escapeHtml(o.customer)}</td>
        <td>${escapeHtml(o.service)}</td>
        <td>${getStageBadge(o.stage)}</td>
        <td style="text-align:right">$${parseFloat(o.nrc || 0).toFixed(2)}</td>
        <td style="text-align:right;font-weight:600">$${parseFloat(o.mrc || 0).toFixed(2)}</td>
        <td style="text-align:right;font-weight:700;color:#059669">$${Math.round(tcv).toLocaleString()}</td>
        <td>${weights[o.stage] || '20%'}</td>
        <td>${o.closeDate || '-'}</td>
        <td>${escapeHtml(o.owner || '-')}</td>
      </tr>
    `;
  });
  tbody.innerHTML = html || '<tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8">No records match report parameters.</td></tr>';
}

function downloadPipelineCSV(isRevenue) {
  let csv = isRevenue 
    ? 'Reference,Customer,Service,Stage,Term Months,NRC (USD),Monthly MRC (USD),Annual ARR (USD),Total TCV (USD),Win Probability,Expected Close,Owner\n'
    : 'Reference,Customer,Partner,Contact,Phone,Email,Building,City,Service,Bandwidth,Stage,NRC,MRC,Expected Close,Owner\n';

  const opps = pipelineOpportunities.filter(o => o.company === currentCompany);
  opps.forEach(o => {
    const term = parseInt(o.term) || 12;
    const m = parseFloat(o.mrc) || 0;
    const n = parseFloat(o.nrc) || 0;
    const tcv = (m * term) + n;

    if (isRevenue) {
      csv += `"${o.ref}","${(o.customer||'').replace(/"/g, '""')}","${(o.service||'').replace(/"/g, '""')}","${o.stage}",${term},${n},${m},${m * 12},${tcv},"${o.stage === 'Won' ? '100%' : '50%'}","${o.closeDate}","${(o.owner||'').replace(/"/g, '""')}"\n`;
    } else {
      csv += `"${o.ref}","${(o.customer||'').replace(/"/g, '""')}","${(o.partner||'').replace(/"/g, '""')}","${(o.contact||'').replace(/"/g, '""')}","${o.phone}","${(o.email||'').replace(/"/g, '""')}","${(o.building||'').replace(/"/g, '""')}","${o.city || ''}","${(o.service||'').replace(/"/g, '""')}","${o.bandwidth || ''}","${o.stage}",${n},${m},"${o.closeDate}","${(o.owner||'').replace(/"/g, '""')}"\n`;
    }
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const prefix = currentCompany === 'SBE' ? 'Savanna_Enterprise_SBE' : 'NNL_Wholesale';
  a.download = isRevenue ? `${prefix}_Pipeline_Revenue_${new Date().toISOString().slice(0,10)}.csv` : `${prefix}_Pipeline_Deals_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  showPipelineToast(`${currentCompany === 'SBE' ? 'Savanna SBE' : 'NNL'} CSV report file downloaded.`);
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Initial Run
document.addEventListener('DOMContentLoaded', function() {
  const shell = document.getElementById('pipelineShell') || document.querySelector('.pipeline-shell');
  if (shell) {
    shell.classList.toggle('theme-sbe', currentCompany === 'SBE');
  }
  document.getElementById('btnNNL').classList.toggle('active', currentCompany === 'NNL');
  document.getElementById('btnSBE').classList.toggle('active', currentCompany === 'SBE');
  document.getElementById('companyBadge').textContent = currentCompany === 'NNL' ? 'NNL Wholesale' : 'Savanna Enterprise SBE';
  renderDashboard();
  renderOpportunitiesTable();
});
</script>
