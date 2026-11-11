<?php
// ============================================================
// Neilos Portal — Native Ticket Management View
// Integrated with Neilos Design System, Database & Theme
// Perfectly matching the rich appearance of Pipeline Management
// ============================================================
$currentUserName = $user['full_name'] ?? 'User';
$currentUserRole = $user['role'] ?? 'NOC Support';
// isAdmin() checks for 'System Admin'; hasRole also includes System Admin by default
$canDelete = (isAdmin() || hasRole('Management'));
?>

<style>
/* Scoped Ticket Management Styles — Exact Visual Parity with Pipeline Management */
.ticket-shell {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.ticket-topbar {
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
.ticket-topbar-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.ticket-title {
  font-family: 'Sora', sans-serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-primary, #09090B);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.ticket-title .brand-badge {
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
.ticket-subtitle {
  font-size: 0.82rem;
  color: var(--text-secondary, #52525B);
}
.ticket-controls {
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
.ticket-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 2px;
  border-bottom: 1px solid var(--border, #e2e8f0);
}
.ticket-nav-btn {
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
.ticket-nav-btn:hover {
  background: rgba(0,0,0,0.03);
  color: var(--accent, #0F4C81);
}
.ticket-nav-btn.active {
  background: rgba(15,76,129,0.08);
  color: var(--accent, #0F4C81);
  border-color: rgba(15,76,129,0.22);
}
.ticket-nav-badge {
  font-size: 0.72rem;
  padding: 1px 7px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #475569;
  font-weight: 700;
}
.ticket-nav-btn.active .ticket-nav-badge {
  background: var(--accent, #0F4C81);
  color: #fff;
}

/* KPI Card Grid — Identical to Pipeline Management */
.ticket-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 16px;
}
.ticket-kpi-card {
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
.ticket-kpi-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent, #0F4C81);
}
.ticket-kpi-card.critical::before { background: #DC2626; }
.ticket-kpi-card.warning::before  { background: #F59E0B; }
.ticket-kpi-card.success::before  { background: #10B981; }
.ticket-kpi-card.info::before     { background: #3B82F6; }

.t-kpi-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary, #64748b);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.t-kpi-val {
  font-size: 1.6rem;
  font-weight: 700;
  font-family: 'Sora', sans-serif;
  color: var(--text-primary, #09090B);
  margin-top: 6px;
}
.t-kpi-sub {
  font-size: 0.75rem;
  color: var(--text-muted, #94a3b8);
  margin-top: 3px;
}

/* Charts & Widgets Grid */
.charts-grid {
  display: grid;
  grid-template-columns: 1.18fr 0.82fr;
  gap: 18px;
}
@media (max-width: 980px) {
  .charts-grid { grid-template-columns: 1fr; }
}

/* Priority & Status Badges */
.prio-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 0.73rem;
  font-weight: 700;
  white-space: nowrap;
}
.prio-p1 { background: rgba(220,38,38,0.14); color: #b91c1c; border: 1px solid rgba(220,38,38,0.25); }
.prio-p2 { background: rgba(217,119,6,0.14); color: #92400e; border: 1px solid rgba(217,119,6,0.25); }
.prio-p3 { background: rgba(37,99,235,0.12); color: #1d4ed8; border: 1px solid rgba(37,99,235,0.2); }
.prio-p4 { background: rgba(100,116,139,0.1); color: #475569; border: 1px solid rgba(100,116,139,0.2); }

.tier-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #334155;
}
.tier-l1   { background: #e0f2fe; color: #0369a1; }
.tier-l2   { background: #ede9fe; color: #6d28d9; }
.tier-tech { background: #fee2e2; color: #b91c1c; font-weight: 800; }
.tier-mgmt { background: #fef3c7; color: #92400e; font-weight: 800; }

/* Pulsing Breach Indicator */
.pulse-dot {
  width: 8px; height: 8px; border-radius: 50%; display: inline-block;
  background: #ef4444; box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
  animation: pulseDot 1.8s infinite;
  vertical-align: middle;
  margin-right: 4px;
}
@keyframes pulseDot {
  0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
  70% { box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); }
  100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Slide-over Drawer */
.ticket-drawer-overlay {
  position: fixed; inset: 0;
  background: rgba(15,23,42,0.45);
  backdrop-filter: blur(2px);
  z-index: 1040;
  opacity: 0;
  visibility: hidden;
  transition: all 0.25s ease;
}
.ticket-drawer-overlay.active {
  opacity: 1;
  visibility: visible;
}
.ticket-drawer {
  position: fixed;
  top: 0; right: -650px;
  width: 620px;
  max-width: 95vw;
  height: 100vh;
  background: var(--surface, #fff);
  box-shadow: -8px 0 32px rgba(0,0,0,0.18);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  transition: right 0.28s cubic-bezier(0.2, 0.9, 0.3, 1);
}
.ticket-drawer.active {
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

/* Tier Stepper Visual — Connected Line with Nodes */
.tier-stepper-visual {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 16px 0 22px;
  position: relative;
}
.tier-stepper-visual::before {
  content: '';
  position: absolute;
  top: 14px; left: 24px; right: 24px;
  height: 2px;
  background: #e2e8f0;
  z-index: 1;
}
.tier-node {
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
.tier-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.74rem;
  color: #64748b;
  transition: all 0.2s ease;
}
.tier-node.done .tier-dot {
  background: #10B981;
  border-color: #10B981;
  color: #fff;
}
.tier-node.active .tier-dot {
  background: var(--accent, #0F4C81);
  border-color: var(--accent, #0F4C81);
  color: #fff;
  box-shadow: 0 0 0 4px rgba(15,76,129,0.15);
}
.tier-node.active span {
  color: var(--accent, #0F4C81);
  font-weight: 700;
}

/* Modal Windows */
.ticket-modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15,23,42,0.5);
  backdrop-filter: blur(2px);
  z-index: 1060;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.ticket-modal-backdrop.active {
  display: flex;
}
.ticket-modal {
  background: var(--surface, #fff);
  border-radius: 14px;
  width: 100%;
  max-width: 660px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
}

/* Table Hover */
.table-hover tbody tr {
  transition: background-color 0.15s ease;
}
.table-hover tbody tr:hover {
  background-color: rgba(15,76,129,0.02) !important;
}

/* Toast Notification */
.ticket-toast {
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
.ticket-toast.show {
  opacity: 1;
  transform: translateY(0);
}

/* ─── SAVANNA (SBE) ORANGE THEME PALETTE (from NNL_SBE_Lead_and_Ticket_Management_Portal.html) ─── */
.ticket-shell.theme-sbe {
  --accent: #F58220 !important;
  --accent-hover: #b95606 !important;
  --accent-light: #fff3e8 !important;
  --accent-border: #ffd1a8 !important;
  --accent-shadow: rgba(245, 130, 32, 0.28) !important;
}

/* SBE / NNL Segment Switcher Buttons */
.ticket-shell .biz-switch button#tBtnSBE.active {
  background: #F58220 !important;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(245, 130, 32, 0.38) !important;
}
.ticket-shell .biz-switch button#tBtnNNL.active {
  background: #0F4C81 !important;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(15, 76, 129, 0.32) !important;
}

/* Brand Badge when SBE */
.ticket-shell.theme-sbe .brand-badge {
  background: #fff3e8 !important;
  color: #F58220 !important;
  border-color: #ffd1a8 !important;
  box-shadow: 0 1px 3px rgba(245, 130, 32, 0.12) !important;
}

/* Navigation Tabs when SBE */
.ticket-shell.theme-sbe .ticket-nav-btn:hover {
  color: #F58220 !important;
  background: #fff7ef !important;
}
.ticket-shell.theme-sbe .ticket-nav-btn.active {
  background: #fff3e8 !important;
  color: #F58220 !important;
  border-color: #ffd1a8 !important;
}
.ticket-shell.theme-sbe .ticket-nav-btn.active .ticket-nav-badge {
  background: #F58220 !important;
  color: #fff !important;
}

/* Primary Action Buttons & Preset Pills */
.ticket-shell.theme-sbe .btn-primary,
.ticket-shell.theme-sbe button.btn-primary {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
  box-shadow: 0 3px 10px rgba(245, 130, 32, 0.28) !important;
}
.ticket-shell.theme-sbe .btn-primary:hover,
.ticket-shell.theme-sbe button.btn-primary:hover {
  background: #b95606 !important;
  border-color: #b95606 !important;
  box-shadow: 0 4px 14px rgba(185, 86, 6, 0.32) !important;
}

/* KPI Cards top border & accents */
.ticket-shell.theme-sbe .kpi-card::before {
  background: #F58220 !important;
}
.ticket-shell.theme-sbe .kpi-card:hover {
  border-color: #ffd1a8 !important;
}
.ticket-shell.theme-sbe .ticket-title svg {
  color: #F58220 !important;
}

/* Stepper Visual Nodes in Drawer */
.ticket-shell.theme-sbe .tier-node.done,
.ticket-shell.theme-sbe .tier-node.active {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
}
.ticket-shell.theme-sbe .tier-node.done .tier-dot,
.ticket-shell.theme-sbe .tier-node.active .tier-dot {
  background: #F58220 !important;
  border-color: #F58220 !important;
  color: #fff !important;
}

/* Drawer Timeline */
.ticket-shell.theme-sbe .drawer-timeline-item {
  border-left-color: #F58220 !important;
}

/* Input Focus */
.ticket-shell.theme-sbe input:focus,
.ticket-shell.theme-sbe select:focus,
.ticket-shell.theme-sbe textarea:focus {
  border-color: #F58220 !important;
  outline-color: #F58220 !important;
  box-shadow: 0 0 0 3px rgba(245, 130, 32, 0.16) !important;
}

/* Modal and Drawer Headers */
.ticket-shell.theme-sbe .modal-header h3,
.ticket-shell.theme-sbe #ticketDrawer .drawer-header h2 {
  color: #F58220 !important;
}
</style>

<div class="ticket-shell" id="ticketShell">
  <!-- Topbar Header -->
  <div class="ticket-topbar">
    <div class="ticket-topbar-left">
      <h1 class="ticket-title">
        <?= svgIcon('clock', 22) ?>
        Ticket Management
        <span class="brand-badge" id="ticketBrandBadge">NNL NOC</span>
      </h1>
      <div class="ticket-subtitle">
        Customer incident support, SLA breach monitoring, automated tier escalation &amp; resolution
      </div>
    </div>

    <div class="ticket-controls">
      <!-- Business Unit Switcher -->
      <div class="biz-switch">
        <button type="button" class="active" id="tBtnNNL" onclick="switchTicketBiz('NNL')">NNL Network</button>
        <button type="button" id="tBtnSBE" onclick="switchTicketBiz('SBE')">Savanna (SBE)</button>
      </div>

      <!-- Support Tier Quick Selector -->
      <select class="form-control" id="tierSelector" style="height:36px;font-size:0.8rem;padding:4px 10px;width:auto" onchange="handleTierChange(this.value)">
        <option value="All">All Support Tiers</option>
        <option value="L1">Level 1 (NOC / Helpdesk)</option>
        <option value="L2">Level 2 (Systems &amp; Routing)</option>
        <option value="Technical">Technical (Field / Optical NOC)</option>
        <option value="Management">Management (Escalations)</option>
      </select>

      <!-- CSV Export Button -->
      <button class="btn btn-secondary btn-sm" onclick="downloadTicketsCSV(false)" style="display:inline-flex;align-items:center;gap:6px">
        <?= svgIcon('download', 14) ?> Export CSV
      </button>

      <!-- Create Ticket Button -->
      <button class="btn btn-primary btn-sm" onclick="openCreateTicketModal()" style="display:inline-flex;align-items:center;gap:6px">
        <?= svgIcon('plus', 15) ?> Log Incident Ticket
      </button>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <div class="ticket-nav">
    <button class="ticket-nav-btn active" onclick="showTicketTab('overview', this)">
      <?= svgIcon('dashboard', 16) ?> Ticket Overview
    </button>
    <button class="ticket-nav-btn" onclick="showTicketTab('active_tickets', this)">
      <?= svgIcon('list', 16) ?> Active Incidents
      <span class="ticket-nav-badge" id="tabActiveCount">0</span>
    </button>
    <button class="ticket-nav-btn" onclick="showTicketTab('services', this)">
      <?= svgIcon('server', 16) ?> Services &amp; Circuits
      <span class="ticket-nav-badge" id="tabServicesCount"><?= count($activeServices) ?></span>
    </button>
    <button class="ticket-nav-btn" onclick="showTicketTab('escalations', this)">
      <?= svgIcon('alert', 16) ?> SLA &amp; Escalations
      <span class="ticket-nav-badge" style="background:#dc2626;color:#fff" id="tabEscalationCount">0</span>
    </button>
    <button class="ticket-nav-btn" onclick="showTicketTab('notifications', this)">
      <?= svgIcon('bell', 16) ?> Notifications History
    </button>
    <button class="ticket-nav-btn" onclick="showTicketTab('reports', this)">
      <?= svgIcon('chart', 16) ?> Reports &amp; SLA Export
    </button>
  </div>

  <!-- ── 1. TICKET OVERVIEW TAB ── -->
  <div id="ttab-overview" class="ticket-tab-pane">
    <!-- Premium KPI Cards Grid -->
    <div class="ticket-kpi-grid mb-20" style="margin-bottom:20px">
      <div class="ticket-kpi-card info">
        <div class="t-kpi-label">Active Incidents <?= svgIcon('list', 16) ?></div>
        <div class="t-kpi-val" id="kpiTotalTickets">0</div>
        <div class="t-kpi-sub" id="kpiSubBreakdown">In queue: 0 &bull; Escalated: 0</div>
      </div>
      <div class="ticket-kpi-card warning">
        <div class="t-kpi-label">Open / In Progress <?= svgIcon('clock', 16) ?></div>
        <div class="t-kpi-val" id="kpiOpenTickets">0</div>
        <div class="t-kpi-sub" style="color:#d97706">Active investigation queue</div>
      </div>
      <div class="ticket-kpi-card critical">
        <div class="t-kpi-label">Escalated (L2 / Tech) <?= svgIcon('alert', 16) ?></div>
        <div class="t-kpi-val" id="kpiEscalatedTickets">0</div>
        <div class="t-kpi-sub" style="color:#dc2626">Requires specialized engineer</div>
      </div>
      <div class="ticket-kpi-card">
        <div class="t-kpi-label">On Hold (Clock Paused) <?= svgIcon('clock', 16) ?></div>
        <div class="t-kpi-val" id="kpiHoldTickets">0</div>
        <div class="t-kpi-sub">Awaiting premise access / 3rd party</div>
      </div>
      <div class="ticket-kpi-card success">
        <div class="t-kpi-label">SLA Compliance Rate <?= svgIcon('check', 16) ?></div>
        <div class="t-kpi-val" id="kpiSlaRate">96.8%</div>
        <div class="t-kpi-sub" style="color:#059669">Target: &gt; 95.0% compliance</div>
      </div>
      <div class="ticket-kpi-card success">
        <div class="t-kpi-label">MTTR (Mean Time) <?= svgIcon('activity', 16) ?></div>
        <div class="t-kpi-val" id="kpiMttrVal">0.0h</div>
        <div class="t-kpi-sub" style="color:#059669">Target: &lt; 4.0 hrs average</div>
      </div>
    </div>

    <!-- Active Incident Alerts & Priority Matrix -->
    <div class="charts-grid mb-20" style="margin-bottom:20px">
      <!-- High Urgency Tickets (Left 1.18fr) -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
            <?= svgIcon('alert', 16) ?> Priority SLA Alerts &bull; Breach Watch
          </div>
          <span class="badge badge-danger" id="badgeCriticalAlerts">0 Active</span>
        </div>
        <div class="card-body" style="padding:0">
          <div class="table-responsive">
            <table class="table table-hover" style="margin:0;font-size:0.83rem">
              <thead>
                <tr style="background:#f8fafc">
                  <th>Ticket ID</th>
                  <th>Customer &amp; Circuit</th>
                  <th>Priority</th>
                  <th>Tier</th>
                  <th>Time Left</th>
                  <th style="text-align:center">Action</th>
                </tr>
              </thead>
              <tbody id="overviewAlertsTbody">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Severity Distribution & Target SLA Metrics (Right 0.82fr) -->
      <div class="card">
        <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);display:flex;align-items:center;gap:8px">
            <?= svgIcon('chart', 16) ?> Severity &amp; SLA Target Matrix
          </div>
          <span style="font-size:0.75rem;color:#64748b">Incident Queue</span>
        </div>
        <div class="card-body" style="padding:18px;display:flex;flex-direction:column;gap:14px">
          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:4px">
              <span style="font-weight:700;color:#b91c1c">&#128308; P1 &bull; Critical Outage (4h Target)</span>
              <strong id="distP1">0</strong>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
              <div id="barP1" style="height:100%;width:0%;background:#DC2626;transition:width 0.4s ease"></div>
            </div>
          </div>

          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:4px">
              <span style="font-weight:700;color:#92400e">&#128992; P2 &bull; Major Degradation (8h Target)</span>
              <strong id="distP2">0</strong>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
              <div id="barP2" style="height:100%;width:0%;background:#D97706;transition:width 0.4s ease"></div>
            </div>
          </div>

          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:4px">
              <span style="font-weight:700;color:#1d4ed8">&#128309; P3 &bull; Minor Issue (24h Target)</span>
              <strong id="distP3">0</strong>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
              <div id="barP3" style="height:100%;width:0%;background:#2563EB;transition:width 0.4s ease"></div>
            </div>
          </div>

          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:4px">
              <span style="font-weight:700;color:#475569">&#9898; P4 &bull; Service Request / Inquiry (48h)</span>
              <strong id="distP4">0</strong>
            </div>
            <div style="height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden">
              <div id="barP4" style="height:100%;width:0%;background:#94a3b8;transition:width 0.4s ease"></div>
            </div>
          </div>

          <!-- Mini Tier Queue Distribution -->
          <div style="border-top:1px solid #f1f5f9;padding-top:12px;margin-top:2px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.75rem">
            <div style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0">
              <span style="color:#64748b">L1 NOC Queue:</span> <strong id="tierCountL1" style="color:#0369a1">0</strong>
            </div>
            <div style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0">
              <span style="color:#64748b">L2 Routing:</span> <strong id="tierCountL2" style="color:#6d28d9">0</strong>
            </div>
            <div style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0">
              <span style="color:#64748b">Field Technical:</span> <strong id="tierCountTech" style="color:#b91c1c">0</strong>
            </div>
            <div style="background:#f8fafc;padding:8px 10px;border-radius:6px;border:1px solid #e2e8f0">
              <span style="color:#64748b">Management:</span> <strong id="tierCountMgmt" style="color:#92400e">0</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 2. ACTIVE TICKETS TAB ── -->
  <div id="ttab-active_tickets" class="ticket-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:14px 18px">
        <!-- Top Stats & Preset Buttons Bar (Direct Parity with Pipeline Management) -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #f1f5f9">
          <div style="font-size:0.8rem;color:#64748b">
            Active Queue: <strong id="tFilteredCount">0</strong> &bull;
            P1 Outages: <strong id="sumFilteredP1" style="color:#dc2626">0</strong> &bull;
            Breached SLA: <strong id="sumFilteredBreached" style="color:#ea580c">0</strong> &bull;
            On Hold: <strong id="sumFilteredHold" style="color:#d97706">0</strong>
          </div>
          <div style="display:flex;align-items:center;gap:6px">
            <button class="btn btn-sm btn-primary preset-btn" onclick="setTicketFilterPreset('all', this)">All Incidents</button>
            <button class="btn btn-sm btn-secondary preset-btn" onclick="setTicketFilterPreset('open', this)">Active Work</button>
            <button class="btn btn-sm btn-secondary preset-btn" onclick="setTicketFilterPreset('critical', this)">P1 Critical</button>
            <button class="btn btn-sm btn-secondary preset-btn" onclick="setTicketFilterPreset('breached', this)">Breached SLA</button>
            <button class="btn btn-sm btn-secondary preset-btn" onclick="setTicketFilterPreset('hold', this)">On Hold</button>
            <button class="btn btn-sm btn-secondary" onclick="downloadTicketsCSV(false)" style="margin-left:8px;display:inline-flex;align-items:center;gap:4px">
              <?= svgIcon('download', 14) ?> CSV
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <!-- Search input -->
          <div style="position:relative;flex:1;min-width:260px">
            <input type="text" id="ticketSearch" class="form-control" placeholder="Search ticket #, customer, circuit CKT-..., fault keyword..." oninput="renderTicketsTable()" style="padding-left:34px;height:38px;font-size:0.85rem">
            <span style="position:absolute;left:11px;top:10px;color:#94a3b8"><?= svgIcon('search', 16) ?></span>
          </div>

          <!-- Filters -->
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <select id="ticketStatusFilter" class="form-control" style="width:auto;height:38px;font-size:0.82rem" onchange="renderTicketsTable()">
              <option value="All">All Statuses</option>
              <option value="Open">Open</option>
              <option value="In Progress">In Progress</option>
              <option value="Escalated">Escalated</option>
              <option value="On Hold">On Hold</option>
              <option value="Closed">Closed</option>
            </select>

            <select id="ticketPriorityFilter" class="form-control" style="width:auto;height:38px;font-size:0.82rem" onchange="renderTicketsTable()">
              <option value="All">All Priorities</option>
              <option value="P1">P1 &bull; Critical</option>
              <option value="P2">P2 &bull; Major</option>
              <option value="P3">P3 &bull; Minor</option>
              <option value="P4">P4 &bull; Inquiry</option>
            </select>

            <select id="ticketTierFilter" class="form-control" style="width:auto;height:38px;font-size:0.82rem" onchange="handleTierChange(this.value)">
              <option value="All">All Tiers</option>
              <option value="L1">Tier: L1 Support</option>
              <option value="L2">Tier: L2 Systems</option>
              <option value="Technical">Tier: Technical / Field</option>
              <option value="Management">Tier: Management</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Tickets Table Card -->
    <div class="card">
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Ticket ID</th>
                <th>Customer / Building</th>
                <th>Circuit / Service</th>
                <th>Fault Category</th>
                <th>Priority</th>
                <th>Assigned Tier</th>
                <th>Elapsed / Remaining SLA</th>
                <th>Status</th>
                <th>Assigned Lead</th>
                <th style="text-align:center">Action</th>
              </tr>
            </thead>
            <tbody id="ticketsTableBody">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 3. SERVICES & CIRCUITS INVENTORY TAB ── -->
  <div id="ttab-services" class="ticket-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:16px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 4px;color:var(--text-primary)">
              Active Services &amp; Circuit Inventory
            </h3>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin:0">
              Live provisioned circuits in Neilos network. Select any customer circuit to immediately log an incident ticket.
            </p>
          </div>
          <!-- Quick Search -->
          <div style="position:relative;width:280px">
            <input type="text" id="svcSearch" class="form-control" placeholder="Search customer, circuit ID..." oninput="filterServicesInventory()" style="padding-left:32px;height:36px;font-size:0.82rem">
            <span style="position:absolute;left:10px;top:9px;color:#94a3b8"><?= svgIcon('search', 14) ?></span>
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
                <th>Service ID</th>
                <th>Customer Name</th>
                <th>Building &amp; Location</th>
                <th>Circuit Reference</th>
                <th>Service Type</th>
                <th>Capacity</th>
                <th>Partner / Client</th>
                <th>Circuit Status</th>
                <th>Previous Tickets</th>
                <th style="text-align:center">Action</th>
              </tr>
            </thead>
            <tbody id="servicesTableBody">
              <?php if (empty($activeServices)): ?>
              <tr><td colspan="10" style="text-align:center;padding:36px;color:#94a3b8">No provisioned services found.</td></tr>
              <?php else: ?>
              <?php foreach ($activeServices as $idx => $as): 
                $cktKey = $as['circuit_id'] ?: 'CKT-FR-' . $as['order_id'];
                $bizUnit = (!empty($as['partner_name']) && $as['partner_name'] !== 'Direct Enterprise') ? 'NNL' : 'SBE';
              ?>
              <tr class="service-row" data-biz="<?= $bizUnit ?>" style="cursor:pointer" onclick="quickCreateTicketByIndex(<?= $idx ?>)">
                <td><strong style="color:var(--accent, #0F4C81)"><?= e($as['service_id'] ?: 'SVC-' . $as['order_id']) ?></strong></td>
                <td>
                  <div style="font-weight:600"><?= e($as['customer_name']) ?></div>
                  <div style="font-size:0.72rem;color:#94a3b8"><?= e($as['customer_contact_phone'] ?? '') ?></div>
                </td>
                <td>
                  <div><?= e($as['building_name'] ?? 'Main Premise') ?></div>
                  <div style="font-size:0.72rem;color:#94a3b8"><?= e($as['location'] ?? 'Dar es Salaam') ?></div>
                </td>
                <td><code><?= e($cktKey) ?></code></td>
                <td><?= e($as['service_type']) ?></td>
                <td><?= e($as['bandwidth_capacity'] ?: 'Standard') ?></td>
                <td><?= e($as['partner_name'] ?? 'Direct Enterprise') ?></td>
                <td><span class="badge badge-success"><?= e($as['status'] ?? 'Active') ?></span></td>
                <td><span class="badge badge-secondary" id="svcTicketCount_<?= e($cktKey) ?>">0</span></td>
                <td style="text-align:center" onclick="event.stopPropagation()">
                  <button class="btn btn-sm btn-primary" onclick="quickCreateTicketByIndex(<?= $idx ?>)">
                    + Log Ticket
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 4. SLA & ESCALATIONS TAB ── -->
  <div id="ttab-escalations" class="ticket-tab-pane" style="display:none">
    <!-- Simulator Box -->
    <div class="card mb-20" style="margin-bottom:16px;background:linear-gradient(135deg, rgba(15,76,129,0.04) 0%, rgba(37,99,235,0.06) 100%);border:1px solid rgba(15,76,129,0.2)">
      <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
        <div>
          <div style="font-weight:700;font-size:0.95rem;color:var(--accent, #0F4C81);display:flex;align-items:center;gap:8px">
            <?= svgIcon('refresh', 16) ?> SLA Escalation Engine Simulator
          </div>
          <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px">
            Simulate elapsed time to verify automated progression: <strong>L1 &rarr; L2 &rarr; Technical &rarr; 6-Hour Breach</strong>.
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px">
          <button class="btn btn-sm btn-secondary" onclick="simulateTimeAdvance(2)">+ Advance 2h (Trigger L2)</button>
          <button class="btn btn-sm btn-secondary" onclick="simulateTimeAdvance(4)">+ Advance 4h (Trigger Tech)</button>
          <button class="btn btn-sm btn-danger" onclick="simulateTimeAdvance(6)">+ Advance 6h (Breach SLA)</button>
          <button class="btn btn-sm btn-secondary" onclick="resetSimulatedTime()">Reset Baseline</button>
        </div>
      </div>
    </div>

    <!-- Hierarchy & Overdue Lists -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
      <!-- Tier SLA Breakdown -->
      <div class="card">
        <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            Automated Escalation Thresholds &amp; Protocols
          </div>
        </div>
        <div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:12px;font-size:0.82rem">
          <div style="padding:12px;background:#f8fafc;border-radius:8px;border-left:4px solid #0369a1">
            <strong>Level 1 (L1 &bull; NOC Helpdesk):</strong> Assigned on ticket creation. Threshold: <strong>2 Hours</strong> without resolution &rarr; auto-escalates to L2.
          </div>
          <div style="padding:12px;background:#f8fafc;border-radius:8px;border-left:4px solid #6d28d9">
            <strong>Level 2 (L2 &bull; Systems &amp; Core Routing):</strong> Threshold: <strong>4 Hours</strong> without resolution &rarr; auto-escalates to Technical / Optical Lead.
          </div>
          <div style="padding:12px;background:#f8fafc;border-radius:8px;border-left:4px solid #b91c1c">
            <strong>Technical (Field &amp; Optical NOC):</strong> Dispatched for physical cable cuts, ODF patching, and SFP transceivers.
          </div>
          <div style="padding:12px;background:#f8fafc;border-radius:8px;border-left:4px solid #92400e">
            <strong>6-Hour Overdue Alert:</strong> Generates automated escalation to Executive Management and registers SLA penalty risk.
          </div>
        </div>
      </div>

      <!-- Overdue Escalation Tracker -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
            Overdue / Escalated Incidents Queue
          </div>
          <span class="badge badge-danger" id="slaEscalatedBadge">0 Breached</span>
        </div>
        <div class="card-body" style="padding:0">
          <div class="table-responsive">
            <table class="table table-hover" style="margin:0;font-size:0.82rem">
              <thead>
                <tr style="background:#f8fafc">
                  <th>Ticket</th>
                  <th>Customer</th>
                  <th>Current Tier</th>
                  <th>Elapsed / Target</th>
                  <th style="text-align:center">Action</th>
                </tr>
              </thead>
              <tbody id="slaEscalatedTbody">
                <!-- Rendered dynamically -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 5. NOTIFICATIONS HISTORY TAB ── -->
  <div id="ttab-notifications" class="ticket-tab-pane" style="display:none">
    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)">
        <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary)">
          <?= svgIcon('bell', 16) ?> Automated Notification Broadcast Log
        </div>
        <span style="font-size:0.75rem;color:var(--text-muted)">Multi-channel dispatch (Email, SMS Broadcast, NOC Alarm)</span>
      </div>
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Timestamp</th>
                <th>Ticket ID</th>
                <th>Recipient / Channel</th>
                <th>Subject / Event</th>
                <th>Message Snippet</th>
                <th>Delivery Status</th>
              </tr>
            </thead>
            <tbody id="notificationLogTbody">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ── 6. REPORTS & SLA EXPORT TAB (Parity with Pipeline Reports) ── -->
  <div id="ttab-reports" class="ticket-tab-pane" style="display:none">
    <div class="card mb-20" style="margin-bottom:16px">
      <div class="card-body" style="padding:16px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <h3 style="font-size:1.05rem;font-weight:700;margin:0 0 4px;color:var(--text-primary)">
              Incident SLA &amp; NOC Operational Reports
            </h3>
            <p style="font-size:0.8rem;color:var(--text-secondary);margin:0">
              Filter incident metrics by priority, engineering tier, and timeframe. Download compliance audit CSV.
            </p>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <button class="btn btn-primary btn-sm" onclick="downloadTicketsCSV(true)" style="display:inline-flex;align-items:center;gap:6px">
              <?= svgIcon('download', 14) ?> Download SLA Audit Report
            </button>
            <button class="btn btn-secondary btn-sm" onclick="downloadTicketsCSV(false)" style="display:inline-flex;align-items:center;gap:6px">
              <?= svgIcon('download', 14) ?> Download Full Incident Log
            </button>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Severity / Priority</label>
            <select id="reportPrio" class="form-control" style="margin-top:4px" onchange="renderTicketReportPreview()">
              <option value="All">All Severity Levels</option>
              <option value="P1">P1 &bull; Critical (4h Target)</option>
              <option value="P2">P2 &bull; Major (8h Target)</option>
              <option value="P3">P3 &bull; Minor (24h Target)</option>
              <option value="P4">P4 &bull; Service Request (48h Target)</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Support Tier</label>
            <select id="reportTier" class="form-control" style="margin-top:4px" onchange="renderTicketReportPreview()">
              <option value="All">All Support Tiers</option>
              <option value="L1">L1 NOC Helpdesk</option>
              <option value="L2">L2 Core Systems</option>
              <option value="Technical">Technical Field Team</option>
              <option value="Management">Executive Escalations</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Resolution Status</label>
            <select id="reportStatus" class="form-control" style="margin-top:4px" onchange="renderTicketReportPreview()">
              <option value="All">All Statuses</option>
              <option value="Closed">Closed &amp; Resolved</option>
              <option value="In Progress">In Progress / Escalated</option>
              <option value="On Hold">Clock Paused / On Hold</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Preview Table -->
    <div class="card">
      <div class="card-header" style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <strong style="font-size:0.9rem">Live Report Preview (<span id="ticketReportFilteredCount">0</span> records)</strong>
        <span style="font-size:0.75rem;color:#64748b">Instant dynamic export preview</span>
      </div>
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="table table-hover" style="margin:0;font-size:0.83rem">
            <thead>
              <tr style="background:#f8fafc">
                <th>Ticket ID</th>
                <th>Customer</th>
                <th>Circuit ID</th>
                <th>Fault Category</th>
                <th>Priority</th>
                <th>Tier</th>
                <th>Elapsed (Hrs)</th>
                <th>Target SLA</th>
                <th>Compliance</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="ticketReportPreviewBody">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── CREATE TICKET MODAL ── -->
<div id="createTicketBackdrop" class="ticket-modal-backdrop" onclick="closeTicketModalOnBackdrop(event)">
  <div class="ticket-modal">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--text-primary)" id="createTicketModalTitle">
        Log New Support Ticket / Network Incident
      </h3>
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeCreateTicketModal()">&times;</button>
    </div>

    <form id="createTicketForm" onsubmit="handleCreateTicket(event)">
      <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
        <!-- Service Selector -->
        <div>
          <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Target Service / Circuit *</label>
          <select id="tInpServiceSelect" class="form-control" required style="margin-top:4px" onchange="fillServiceDetails(this.value)">
            <?php foreach ($activeServices as $as): 
              $bizUnit = (!empty($as['partner_name']) && $as['partner_name'] !== 'Direct Enterprise') ? 'NNL' : 'SBE';
            ?>
            <option value="<?= e($as['circuit_id'] ?: $as['order_id']) ?>"
                    data-biz="<?= $bizUnit ?>"
                    data-customer="<?= htmlspecialchars($as['customer_name'], ENT_QUOTES) ?>"
                    data-building="<?= htmlspecialchars($as['building_name'] ?? 'Main Premise', ENT_QUOTES) ?>"
                    data-location="<?= htmlspecialchars($as['location'] ?? 'Dar es Salaam', ENT_QUOTES) ?>"
                    data-service="<?= htmlspecialchars($as['service_type'], ENT_QUOTES) ?>"
                    data-circuit="<?= htmlspecialchars($as['circuit_id'] ?: 'CKT-FR-' . $as['order_id'], ENT_QUOTES) ?>">
              <?= e($as['circuit_id'] ?: 'CKT-FR-' . $as['order_id']) ?> &bull; <?= e($as['customer_name']) ?> (<?= e($as['service_type']) ?>)
            </option>
            <?php endforeach; ?>
            <option value="CUSTOM_CIRCUIT">+ Custom / Unlisted Enterprise Premise</option>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Customer Name *</label>
            <input type="text" id="tInpCustomer" class="form-control" required placeholder="e.g. Magari Mtaaani" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Circuit Reference</label>
            <input type="text" id="tInpCircuit" class="form-control" placeholder="e.g. CKT-FR-260824-001" style="margin-top:4px">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Premise / Building</label>
            <input type="text" id="tInpBuilding" class="form-control" placeholder="e.g. Mwanga Tower" style="margin-top:4px">
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Fault Category *</label>
            <select id="tInpCategory" class="form-control" required style="margin-top:4px">
              <option value="Fiber Cut / Optical Loss">Fiber Cut / Optical Loss (High dBm)</option>
              <option value="Equipment Failure / ONT">Equipment Failure / ONT Dead</option>
              <option value="High Latency / Packet Loss">High Latency / Packet Loss</option>
              <option value="Configuration / BGP Routing">Configuration / BGP Routing Issue</option>
              <option value="Power Outage / Site Access">Power Outage / Site Access Down</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Incident Priority *</label>
            <select id="tInpPriority" class="form-control" required style="margin-top:4px">
              <option value="P1" selected>P1 &bull; Critical Outage (4h Target SLA)</option>
              <option value="P2">P2 &bull; Major Degradation (8h Target SLA)</option>
              <option value="P3">P3 &bull; Minor Issue (24h Target SLA)</option>
              <option value="P4">P4 &bull; Inquiry / Request (48h Target SLA)</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Initial Assignment Tier</label>
            <select id="tInpTier" class="form-control" style="margin-top:4px">
              <option value="L1">Level 1 (NOC / Helpdesk)</option>
              <option value="L2">Level 2 (Routing &amp; Systems)</option>
              <option value="Technical">Technical (Field / Optical NOC)</option>
            </select>
          </div>
        </div>

        <div>
          <label style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase">Incident Description &amp; Diagnostic Notes *</label>
          <textarea id="tInpDescription" class="form-control" rows="3" required placeholder="Describe optical readings, error logs, customer symptoms..." style="margin-top:4px"></textarea>
        </div>
      </div>

      <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#f8fafc">
        <button type="button" class="btn btn-secondary" onclick="closeCreateTicketModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Ticket &amp; Notify NOC</button>
      </div>
    </form>
  </div>
</div>

<!-- ── SLIDE-OVER TICKET DETAIL & RESOLUTION DRAWER ── -->
<div id="ticketDrawerBackdrop" class="ticket-drawer-overlay" onclick="closeTicketDrawer()"></div>
<div id="ticketDrawer" class="ticket-drawer">
  <div class="drawer-header">
    <div>
      <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600" id="tdrwId">TKT-XXXX</div>
      <h2 style="margin:2px 0 0;font-size:1.15rem;font-weight:700" id="tdrwCustomer">Customer Name</h2>
    </div>
    <button type="button" class="btn btn-sm btn-secondary" onclick="closeTicketDrawer()">&times;</button>
  </div>

  <div class="drawer-body">
    <!-- Live SLA Countdown Clock -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:18px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div>
          <div style="font-size:0.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Live SLA Clock</div>
          <div style="font-size:1.4rem;font-weight:700;font-family:'Sora',sans-serif" id="tdrwSlaTimer">02:45:00 Remaining</div>
        </div>
        <div id="tdrwPriorityBadge">
          <span class="prio-pill prio-p1">P1 Critical</span>
        </div>
      </div>
      <div style="height:7px;background:#e2e8f0;border-radius:999px;overflow:hidden">
        <div id="tdrwSlaProgressBar" style="height:100%;width:65%;background:#10B981"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:#94a3b8;margin-top:4px">
        <span id="tdrwSlaElapsed">Elapsed: 1.25 hrs</span>
        <span id="tdrwSlaTotal">Total Target: 4.0 hrs</span>
      </div>
    </div>

    <!-- Tier Stepper Visual -->
    <div style="margin-bottom:18px">
      <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px">Escalation Tier Progression</div>
      <div class="tier-stepper-visual">
        <div class="tier-node" id="tstep-L1">
          <div class="tier-dot">1</div>
          <span>L1 NOC</span>
        </div>
        <div class="tier-node" id="tstep-L2">
          <div class="tier-dot">2</div>
          <span>L2 Core</span>
        </div>
        <div class="tier-node" id="tstep-Technical">
          <div class="tier-dot">3</div>
          <span>Tech Field</span>
        </div>
        <div class="tier-node" id="tstep-Management">
          <div class="tier-dot">4</div>
          <span>Exec Mgmt</span>
        </div>
      </div>
    </div>

    <!-- On Hold Warning Banner (if held) -->
    <div id="tdrwHoldBanner" style="display:none;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-bottom:16px;color:#92400e;font-size:0.8rem">
      <strong>&#9208; SLA Clock Paused:</strong> <span id="tdrwHoldReason">Awaiting site access</span>
    </div>

    <!-- Circuit & Incident Details -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div>
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 8px;color:#334155">Circuit &amp; Customer Information</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.82rem">
          <div><span style="color:#64748b">Circuit ID:</span> <strong id="tdrwCircuit">-</strong></div>
          <div><span style="color:#64748b">Service Type:</span> <strong id="tdrwService">-</strong></div>
          <div><span style="color:#64748b">Building:</span> <strong id="tdrwBuilding">-</strong></div>
          <div><span style="color:#64748b">Fault Category:</span> <strong id="tdrwCategory">-</strong></div>
          <div><span style="color:#64748b">Current Status:</span> <span id="tdrwStatusBadge" class="badge badge-warning">In Progress</span></div>
          <div><span style="color:#64748b">Current Assignee:</span> <strong id="tdrwAssignee">-</strong></div>
        </div>
      </div>

      <div style="border-top:1px solid #f1f5f9;padding-top:14px">
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 8px;color:#334155">Issue Description &amp; Technical Scope</h4>
        <div id="tdrwDescription" style="font-size:0.82rem;background:#f8fafc;padding:10px;border-radius:6px;color:#334155">
          -
        </div>
      </div>

      <!-- Action Timeline -->
      <div style="border-top:1px solid #f1f5f9;padding-top:14px">
        <h4 style="font-size:0.85rem;font-weight:700;margin:0 0 10px;color:#334155">Incident Activity &amp; Escalation Timeline</h4>
        <div id="tdrwTimeline" style="display:flex;flex-direction:column;gap:10px;font-size:0.8rem">
          <!-- Rendered dynamically -->
        </div>
      </div>
    </div>
  </div>

  <div class="drawer-footer">
    <div style="display:flex;align-items:center;gap:6px">
      <button type="button" class="btn btn-sm btn-outline-danger" id="tdrwDeleteBtn" onclick="deleteIncidentTicketFromDrawer()" style="display:none;color:#dc2626;border-color:#fca5a5" title="Permanently Delete Incident Ticket">&#128465; Delete</button>
      <button type="button" class="btn btn-sm btn-secondary" id="btnToggleHold" onclick="promptHoldTicket()">Pause / Hold</button>
      <button type="button" class="btn btn-sm btn-secondary" onclick="escalateCurrentTicket()">Escalate Tier &rarr;</button>
    </div>
    <div style="display:flex;align-items:center;gap:6px">
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeTicketDrawer()">Close</button>
      <button type="button" class="btn btn-sm btn-primary" onclick="resolveCurrentTicket()">&check; Resolve &amp; Close</button>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div id="ticketToast" class="ticket-toast">
  <span style="color:#10B981">&#10003;</span>
  <span id="ticketToastMsg">Notification</span>
</div>

<script>
// Live provisioned services from Neilos DB
const liveActiveServices = <?= json_encode($activeServices ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// ============================================================
// Clean Fresh Ticket Data Store (All mock records wiped)
// ============================================================
const STORAGE_KEY_TICKETS = 'neilos_tickets_fresh_v1';
const STORAGE_KEY_NOTIFS = 'neilos_tickets_notifs_fresh_v1';

// Wipe any legacy storage keys to guarantee a completely fresh state
try {
  localStorage.removeItem('neilos_tickets_list');
  localStorage.removeItem('neilos_tickets_list_v2');
  localStorage.removeItem('neilos_tickets_list_v3');
  localStorage.removeItem('neilos_notifications_list');
} catch (e) {}

const DEFAULT_TICKETS = [];

let ticketsList = [];
try {
  const saved = localStorage.getItem(STORAGE_KEY_TICKETS);
  if (saved) {
    ticketsList = JSON.parse(saved);
  } else {
    ticketsList = [];
    localStorage.setItem(STORAGE_KEY_TICKETS, JSON.stringify(ticketsList));
  }
} catch (e) {
  ticketsList = [];
}

function saveTicketsData() {
  try {
    localStorage.setItem(STORAGE_KEY_TICKETS, JSON.stringify(ticketsList));
  } catch (e) {}
}

let activeTicketId = null;
let currentTicketBiz = 'NNL';
try {
  currentTicketBiz = localStorage.getItem('neilos_tickets_current_biz') || 'NNL';
} catch (e) {
  currentTicketBiz = 'NNL';
}
let currentTicketPreset = 'all';

const userCanDeleteTicket = <?= json_encode($canDelete) ?>;
let activeTicketTier = 'All';
let activeUserRole = '<?= addslashes($currentUserRole) ?>';

function canUserDeleteTicket() {
  // Authorized roles: System Admin and Management (exact DB role names)
  const authorizedRoles = ['System Admin', 'Management', 'Administrator', 'Admin'];
  return userCanDeleteTicket || authorizedRoles.includes(activeUserRole);
}

function switchTicketBiz(biz) {
  currentTicketBiz = biz;
  try {
    localStorage.setItem('neilos_tickets_current_biz', biz);
  } catch (e) {}
  document.getElementById('tBtnNNL').classList.toggle('active', biz === 'NNL');
  document.getElementById('tBtnSBE').classList.toggle('active', biz === 'SBE');
  document.getElementById('ticketBrandBadge').textContent = biz === 'NNL' ? 'NNL NOC' : 'Savanna SBE';

  const shell = document.getElementById('ticketShell') || document.querySelector('.ticket-shell');
  if (shell) {
    shell.classList.toggle('theme-sbe', biz === 'SBE');
  }

  renderOverview();
  renderTicketsTable();
  renderEscalations();
  renderNotifications();
  filterServicesByBiz();
  updateServiceTicketCounts();
  renderTicketReportPreview();
  showTicketToast(`Switched ticket desk to ${biz === 'NNL' ? 'NNL Network Wholesale' : 'Savanna Enterprise SBE'}`);
}

function filterServicesByBiz() {
  const query = (document.getElementById('svcSearch')?.value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('.service-row');
  let matchCount = 0;
  rows.forEach(r => {
    const biz = r.dataset.biz || 'NNL';
    if (biz !== currentTicketBiz) {
      r.style.display = 'none';
      return;
    }
    if (query) {
      const text = r.textContent.toLowerCase();
      if (text.includes(query)) {
        r.style.display = 'table-row';
        matchCount++;
      } else {
        r.style.display = 'none';
      }
    } else {
      r.style.display = 'table-row';
      matchCount++;
    }
  });

  const badge = document.getElementById('tabServicesCount');
  if (badge) badge.textContent = matchCount;

  // Filter modal dropdown options
  const sel = document.getElementById('tInpServiceSelect');
  if (sel) {
    for (let i = 0; i < sel.options.length; i++) {
      const opt = sel.options[i];
      if (opt.value === '' || opt.value === 'CUSTOM_CIRCUIT') {
        opt.style.display = '';
        continue;
      }
      const optBiz = opt.dataset.biz || 'NNL';
      opt.style.display = (optBiz === currentTicketBiz) ? '' : 'none';
    }
  }
}

function filterServicesInventory() {
  filterServicesByBiz();
}

function showTicketTab(tabKey, btnElement) {
  document.querySelectorAll('.ticket-nav-btn').forEach(b => b.classList.remove('active'));
  if (btnElement) btnElement.classList.add('active');

  document.querySelectorAll('.ticket-tab-pane').forEach(p => p.style.display = 'none');
  const target = document.getElementById('ttab-' + tabKey);
  if (target) target.style.display = 'block';

  if (tabKey === 'overview') renderOverview();
  if (tabKey === 'active_tickets') renderTicketsTable();
  if (tabKey === 'services') filterServicesByBiz();
  if (tabKey === 'escalations') renderEscalations();
  if (tabKey === 'notifications') renderNotifications();
  if (tabKey === 'reports') renderTicketReportPreview();
}

// Render Overview Dashboard
function renderOverview() {
  const bizTickets = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz);
  const total = bizTickets.length;
  const openCount = bizTickets.filter(t => t.status === 'Open' || t.status === 'In Progress').length;
  const escCount = bizTickets.filter(t => t.status === 'Escalated' || t.tier === 'L2' || t.tier === 'Technical' || t.tier === 'Management').length;
  const holdCount = bizTickets.filter(t => t.status === 'On Hold' || t.isOnHold).length;

  document.getElementById('kpiTotalTickets').textContent = total;
  document.getElementById('kpiOpenTickets').textContent = openCount;
  document.getElementById('kpiEscalatedTickets').textContent = escCount;
  document.getElementById('kpiHoldTickets').textContent = holdCount;
  document.getElementById('kpiSubBreakdown').textContent = `In queue: ${openCount} • Escalated: ${escCount}`;

  document.getElementById('tabActiveCount').textContent = openCount + escCount;
  document.getElementById('tabEscalationCount').textContent = escCount;

  // Priority Breakdown
  const p1 = bizTickets.filter(t => t.priority === 'P1').length;
  const p2 = bizTickets.filter(t => t.priority === 'P2').length;
  const p3 = bizTickets.filter(t => t.priority === 'P3').length;
  const p4 = bizTickets.filter(t => t.priority === 'P4').length;

  document.getElementById('distP1').textContent = p1;
  document.getElementById('distP2').textContent = p2;
  document.getElementById('distP3').textContent = p3;
  document.getElementById('distP4').textContent = p4;

  const maxP = Math.max(1, total);
  document.getElementById('barP1').style.width = Math.round((p1 / maxP) * 100) + '%';
  document.getElementById('barP2').style.width = Math.round((p2 / maxP) * 100) + '%';
  document.getElementById('barP3').style.width = Math.round((p3 / maxP) * 100) + '%';
  document.getElementById('barP4').style.width = Math.round((p4 / maxP) * 100) + '%';

  // Dynamic SLA compliance and MTTR calculation
  const breachedCount = bizTickets.filter(t => t.elapsedHours >= t.slaTargetHours).length;
  const slaRate = total > 0 ? (((total - breachedCount) / total) * 100).toFixed(1) + '%' : '100.0%';
  const elSlaRate = document.getElementById('kpiSlaRate');
  if (elSlaRate) elSlaRate.textContent = slaRate;

  // MTTR
  const closedTickets = bizTickets.filter(t => t.status === 'Closed');
  let avgMttr = '0.0h';
  if (closedTickets.length > 0) {
    const sumHours = closedTickets.reduce((sum, t) => sum + (parseFloat(t.elapsedHours) || 0), 0);
    avgMttr = (sumHours / closedTickets.length).toFixed(1) + 'h';
  }
  const elMttr = document.getElementById('kpiMttrVal');
  if (elMttr) elMttr.textContent = avgMttr;

  // Support Tier Counts
  const l1Count = bizTickets.filter(t => t.tier === 'L1').length;
  const l2Count = bizTickets.filter(t => t.tier === 'L2').length;
  const techCount = bizTickets.filter(t => t.tier === 'Technical').length;
  const mgmtCount = bizTickets.filter(t => t.tier === 'Management').length;

  const elL1 = document.getElementById('tierCountL1'); if (elL1) elL1.textContent = l1Count;
  const elL2 = document.getElementById('tierCountL2'); if (elL2) elL2.textContent = l2Count;
  const elTech = document.getElementById('tierCountTech'); if (elTech) elTech.textContent = techCount;
  const elMgmt = document.getElementById('tierCountMgmt'); if (elMgmt) elMgmt.textContent = mgmtCount;

  // Alerts table (Critical / P1 / Nearing SLA)
  const alertsTbody = document.getElementById('overviewAlertsTbody');
  if (alertsTbody) {
    const alerts = bizTickets.filter(t => t.priority === 'P1' || t.status === 'Escalated' || (t.elapsedHours >= t.slaTargetHours));
    document.getElementById('badgeCriticalAlerts').textContent = alerts.length + ' Active';

    if (alerts.length === 0) {
      alertsTbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:26px;color:#94a3b8">No critical or breached SLA incidents in this queue! &#127881;</td></tr>`;
    } else {
      let html = '';
      alerts.forEach(t => {
        const remaining = Math.max(0, t.slaTargetHours - t.elapsedHours);
        const isBreach = (remaining === 0);
        const remText = isBreach 
          ? '<span style="color:#dc2626;font-weight:700"><span class="pulse-dot"></span>BREACHED</span>' 
          : `<span style="color:${remaining < 1 ? '#d97706' : '#059669'};font-weight:600">${remaining.toFixed(1)}h left</span>`;
        html += `
          <tr style="cursor:pointer" onclick="openTicketDrawer('${t.id}')">
            <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(t.id)}</strong></td>
            <td>
              <div style="font-weight:600">${escapeHtml(t.customer)}</div>
              <div style="font-size:0.72rem;color:#94a3b8"><code>${escapeHtml(t.circuit)}</code> &bull; ${escapeHtml(t.service)}</div>
            </td>
            <td><span class="prio-pill prio-${t.priority.toLowerCase()}">${t.priority}</span></td>
            <td><span class="tier-badge tier-${t.tier.toLowerCase()}">${t.tier}</span></td>
            <td>${remText}</td>
            <td style="text-align:center" onclick="event.stopPropagation()">
              <button class="btn btn-sm btn-secondary" onclick="openTicketDrawer('${t.id}')">Manage &rarr;</button>
              ${canUserDeleteTicket() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteIncidentTicket('${t.id}')" title="Delete Incident Ticket" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626;margin-left:4px">&#128465;</button>` : ''}
            </td>
          </tr>
        `;
      });
      alertsTbody.innerHTML = html;
    }
  }
}

// Preset Filter Buttons
function setTicketFilterPreset(preset, btn) {
  currentTicketPreset = preset;
  document.querySelectorAll('#ttab-active_tickets .preset-btn').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  if (btn) {
    btn.classList.add('btn-primary');
    btn.classList.remove('btn-secondary');
  }

  const sf = document.getElementById('ticketStatusFilter');
  const pf = document.getElementById('ticketPriorityFilter');
  if (preset === 'all') {
    if (sf) sf.value = 'All';
    if (pf) pf.value = 'All';
  } else if (preset === 'open') {
    if (sf) sf.value = 'In Progress';
    if (pf) pf.value = 'All';
  } else if (preset === 'critical') {
    if (sf) sf.value = 'All';
    if (pf) pf.value = 'P1';
  } else if (preset === 'hold') {
    if (sf) sf.value = 'On Hold';
    if (pf) pf.value = 'All';
  }
  renderTicketsTable();
}

// Render Active Tickets Table
function renderTicketsTable() {
  const search = (document.getElementById('ticketSearch')?.value || '').toLowerCase().trim();
  const statusFilt = document.getElementById('ticketStatusFilter')?.value || 'All';
  const prioFilt = document.getElementById('ticketPriorityFilter')?.value || 'All';
  const tierFilt = document.getElementById('ticketTierFilter')?.value || 'All';

  const tbody = document.getElementById('ticketsTableBody');
  if (!tbody) return;

  const totalInBiz = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz);
  const p1Count = totalInBiz.filter(t => t.priority === 'P1').length;
  const breachedCount = totalInBiz.filter(t => t.status !== 'Closed' && t.elapsedHours >= t.slaTargetHours).length;
  const holdCount = totalInBiz.filter(t => t.isOnHold || t.status === 'On Hold').length;

  const filtered = totalInBiz.filter(t => {
    if (currentTicketPreset === 'breached') {
      if (t.status === 'Closed' || t.elapsedHours < t.slaTargetHours) return false;
    }
    if (statusFilt !== 'All' && t.status !== statusFilt) return false;
    if (prioFilt !== 'All' && t.priority !== prioFilt) return false;
    if (tierFilt !== 'All' && t.tier !== tierFilt) return false;
    if (search) {
      const hay = [t.id, t.customer, t.building, t.circuit, t.service, t.category, t.description].join(' ').toLowerCase();
      if (!hay.includes(search)) return false;
    }
    return true;
  });

  const filteredEl = document.getElementById('tFilteredCount'); if (filteredEl) filteredEl.textContent = filtered.length;
  const p1El = document.getElementById('sumFilteredP1'); if (p1El) p1El.textContent = p1Count;
  const brEl = document.getElementById('sumFilteredBreached'); if (brEl) brEl.textContent = breachedCount;
  const hldEl = document.getElementById('sumFilteredHold'); if (hldEl) hldEl.textContent = holdCount;

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align:center;padding:48px 20px;color:#94a3b8">
          <div style="font-size:1.1rem;font-weight:700;color:#64748b;margin-bottom:6px">No incident tickets logged yet</div>
          <div style="font-size:0.83rem;margin-bottom:14px;color:#94a3b8">Your ticket queue for ${currentTicketBiz === 'NNL' ? 'NNL Wholesale' : 'Savanna Enterprise SBE'} is completely clean.</div>
          <button class="btn btn-sm btn-primary" onclick="openCreateTicketModal()">+ Log First Incident Ticket</button>
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  filtered.forEach(t => {
    const remaining = Math.max(0, t.slaTargetHours - t.elapsedHours);
    const remBadge = remaining === 0 
      ? `<span class="badge badge-danger"><span class="pulse-dot"></span>Breached (${t.elapsedHours.toFixed(1)}h)</span>` 
      : `<span class="badge badge-success">${remaining.toFixed(1)}h left</span>`;

    const statusBadge = t.status === 'Closed' 
      ? '<span class="badge badge-success">Closed</span>' 
      : (t.status === 'On Hold' ? '<span class="badge badge-warning">On Hold</span>' : (t.status === 'Escalated' ? '<span class="badge badge-danger">Escalated</span>' : '<span class="badge badge-info">In Progress</span>'));

    html += `
      <tr style="cursor:pointer" onclick="openTicketDrawer('${t.id}')">
        <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(t.id)}</strong></td>
        <td>
          <div style="font-weight:600">${escapeHtml(t.customer)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">${escapeHtml(t.building)}</div>
        </td>
        <td>
          <div><code>${escapeHtml(t.circuit)}</code></div>
          <div style="font-size:0.72rem;color:#64748b">${escapeHtml(t.service)}</div>
        </td>
        <td>${escapeHtml(t.category)}</td>
        <td><span class="prio-pill prio-${t.priority.toLowerCase()}">${t.priority}</span></td>
        <td><span class="tier-badge tier-${t.tier.toLowerCase()}">${t.tier}</span></td>
        <td>${remBadge}</td>
        <td>${statusBadge}</td>
        <td>${escapeHtml(t.assignee || 'NOC Lead')}</td>
        <td style="text-align:center" onclick="event.stopPropagation()">
          <button class="btn btn-sm btn-secondary" onclick="openTicketDrawer('${t.id}')">Details &rarr;</button>
          ${canUserDeleteTicket() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteIncidentTicket('${t.id}')" title="Delete Incident Ticket" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626;margin-left:4px">&#128465;</button>` : ''}
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

// Drawer Open / Close / Actions
function openTicketDrawer(id) {
  const t = ticketsList.find(x => x.id == id);
  if (!t) return;

  activeTicketId = id;
  document.getElementById('tdrwId').textContent = t.id;
  document.getElementById('tdrwCustomer').textContent = t.customer;
  document.getElementById('tdrwCircuit').textContent = t.circuit;
  document.getElementById('tdrwService').textContent = t.service;
  document.getElementById('tdrwBuilding').textContent = t.building;
  document.getElementById('tdrwCategory').textContent = t.category;
  document.getElementById('tdrwAssignee').textContent = t.assignee || 'NOC Lead';
  document.getElementById('tdrwDescription').textContent = t.description;

  // Priority Pill
  document.getElementById('tdrwPriorityBadge').innerHTML = `<span class="prio-pill prio-${t.priority.toLowerCase()}">${t.priority} ${t.priority === 'P1' ? 'Critical Outage' : 'Priority'}</span>`;

  // Status Badge
  document.getElementById('tdrwStatusBadge').innerHTML = t.status === 'Closed'
    ? '<span class="badge badge-success">Closed</span>'
    : (t.status === 'On Hold' ? '<span class="badge badge-warning">On Hold</span>' : (t.status === 'Escalated' ? '<span class="badge badge-danger">Escalated</span>' : '<span class="badge badge-info">In Progress</span>'));

  // SLA Calculation
  const remaining = Math.max(0, t.slaTargetHours - t.elapsedHours);
  const pct = Math.min(100, Math.round((t.elapsedHours / t.slaTargetHours) * 100));

  document.getElementById('tdrwSlaTimer').textContent = remaining === 0 ? 'SLA BREACHED' : `${remaining.toFixed(1)} Hours Remaining`;
  document.getElementById('tdrwSlaTimer').style.color = remaining === 0 ? '#dc2626' : (remaining < 1 ? '#d97706' : '#059669');
  document.getElementById('tdrwSlaProgressBar').style.width = pct + '%';
  document.getElementById('tdrwSlaProgressBar').style.background = remaining === 0 ? '#dc2626' : (remaining < 1 ? '#d97706' : '#10B981');
  document.getElementById('tdrwSlaElapsed').textContent = `Elapsed: ${t.elapsedHours.toFixed(1)} hrs`;
  document.getElementById('tdrwSlaTotal').textContent = `Target SLA: ${t.slaTargetHours} hrs`;

  // Hold Banner
  const holdBanner = document.getElementById('tdrwHoldBanner');
  if (t.isOnHold) {
    holdBanner.style.display = 'block';
    document.getElementById('tdrwHoldReason').textContent = t.holdReason || 'Clock Paused';
    document.getElementById('btnToggleHold').textContent = 'Resume Clock';
  } else {
    holdBanner.style.display = 'none';
    document.getElementById('btnToggleHold').textContent = 'Pause / Hold';
  }

  // Tier Stepper Visual Nodes
  const tiers = ['L1', 'L2', 'Technical', 'Management'];
  const curIdx = tiers.indexOf(t.tier);
  tiers.forEach((tierName, idx) => {
    const el = document.getElementById('tstep-' + tierName);
    if (el) {
      el.classList.remove('active', 'done');
      if (idx < curIdx) el.classList.add('done');
      else if (idx === curIdx) el.classList.add('active');
    }
  });

  // Timeline
  const tl = document.getElementById('tdrwTimeline');
  if (tl) {
    let tHtml = '';
    (t.history || []).forEach(h => {
      tHtml += `
        <div style="border-left:2px solid var(--accent, #0F4C81);padding-left:12px;margin-bottom:8px">
          <div style="font-size:0.72rem;color:#94a3b8;font-weight:600">${h.time}</div>
          <div style="margin-top:2px;color:#334155">${escapeHtml(h.note)}</div>
        </div>
      `;
    });
    tl.innerHTML = tHtml || '<div style="color:#94a3b8">No history recorded.</div>';
  }

  const delBtn = document.getElementById('tdrwDeleteBtn');
  if (delBtn) {
    delBtn.style.display = canUserDeleteTicket() ? 'inline-flex' : 'none';
  }

  document.getElementById('ticketDrawer').classList.add('active');
  document.getElementById('ticketDrawerBackdrop').classList.add('active');
}

function closeTicketDrawer() {
  document.getElementById('ticketDrawer').classList.remove('active');
  document.getElementById('ticketDrawerBackdrop').classList.remove('active');
  activeTicketId = null;
}

function escalateCurrentTicket() {
  if (!activeTicketId) return;
  const t = ticketsList.find(x => x.id == activeTicketId);
  if (!t) return;

  const tiers = ['L1', 'L2', 'Technical', 'Management'];
  const curIdx = tiers.indexOf(t.tier);
  if (curIdx < tiers.length - 1) {
    const nextTier = tiers[curIdx + 1];
    t.tier = nextTier;
    t.status = 'Escalated';
    t.history = t.history || [];
    t.history.unshift({
      time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
      note: `Escalated to ${nextTier} Tier. Automated broadcast sent to NOC Supervisor.`
    });

    notificationsList.unshift({
      time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}),
      id: t.id,
      company: t.company,
      channel: 'Email & NOC Broadcast',
      subject: `Automated Tier Escalation (${nextTier})`,
      text: `Incident ${t.id} on ${t.circuit} escalated to ${nextTier} Engineering.`,
      status: 'Sent'
    });

    saveTicketsData();
    openTicketDrawer(t.id);
    renderOverview();
    renderTicketsTable();
    renderEscalations();
    renderNotifications();
    showTicketToast(`Ticket ${t.id} successfully escalated to ${nextTier}`);
  } else {
    showTicketToast(`Ticket is already at highest Executive Management tier.`);
  }
}

async function promptHoldTicket() {
  if (!activeTicketId) return;
  const t = ticketsList.find(x => x.id == activeTicketId);
  if (!t) return;

  if (t.isOnHold) {
    t.isOnHold = false;
    t.holdReason = '';
    t.status = 'In Progress';
    t.history.unshift({
      time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
      note: 'Clock resumed. Work continued by assigned engineering team.'
    });
    showTicketToast('SLA clock resumed.');
  } else {
    const reason = typeof neilosPrompt === 'function'
      ? await neilosPrompt('Please specify operational reason for pausing SLA clock (e.g. Awaiting client access, public holiday, third-party power grid):', 'Awaiting site security clearance', 'Pause SLA Clock')
      : window.prompt('Please specify operational reason for pausing SLA clock (e.g. Awaiting client access, public holiday, third-party power grid):', 'Awaiting site security clearance');
    if (!reason) return;
    t.isOnHold = true;
    t.holdReason = reason;
    t.status = 'On Hold';
    t.history.unshift({
      time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
      note: `SLA Clock paused (On Hold): ${reason}`
    });
    showTicketToast('SLA clock paused. Ticket placed On Hold.');
  }

  saveTicketsData();
  openTicketDrawer(t.id);
  renderOverview();
  renderTicketsTable();
}

async function resolveCurrentTicket() {
  if (!activeTicketId) return;
  const t = ticketsList.find(x => x.id == activeTicketId);
  if (!t) return;

  const resolution = typeof neilosPrompt === 'function'
    ? await neilosPrompt('Enter technical resolution summary:', 'Optical power calibrated and continuity verified. Ping tests passed at 0% loss.', 'Resolve Incident Ticket')
    : window.prompt('Enter technical resolution summary:', 'Optical power calibrated and continuity verified. Ping tests passed at 0% loss.');
  if (!resolution) return;

  t.status = 'Closed';
  t.isOnHold = false;
  t.history.unshift({
    time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}),
    note: `Resolved and Closed: ${resolution}`
  });

  notificationsList.unshift({
    time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}),
    id: t.id,
    company: t.company,
    channel: 'SMS & Email Alert',
    subject: 'Incident Resolved & Closed',
    text: `Resolution confirmed on ${t.circuit}: ${resolution}`,
    status: 'Delivered'
  });

  saveTicketsData();
  closeTicketDrawer();
  renderOverview();
  renderTicketsTable();
  renderEscalations();
  renderNotifications();
  updateServiceTicketCounts();
  renderTicketReportPreview();
  showTicketToast(`Ticket ${t.id} has been resolved and closed successfully.`);
}

async function deleteIncidentTicket(id) {
  if (!canUserDeleteTicket()) {
    showTicketToast('Permission denied: Only Admin and Management can delete incident tickets.');
    return;
  }
  // Use == (loose) to handle numeric or string ticket IDs safely
  const ticket = ticketsList.find(t => t.id == id);
  if (!ticket) return;

  const msg = `Are you sure you want to permanently delete incident ticket "${ticket.id} — ${ticket.customer}"? This action cannot be undone.`;
  let confirmed = false;
  if (typeof neilosConfirm === 'function') {
    confirmed = await neilosConfirm(msg, 'Delete Incident Ticket', 'Proceed', 'btn-danger', '⚠️');
  } else {
    confirmed = window.confirm(msg);
  }

  if (confirmed) {
    ticketsList = ticketsList.filter(t => t.id != id);
    saveTicketsData();
    if (activeTicketId == id) {
      closeTicketDrawer();
    }
    renderOverview();
    renderTicketsTable();
    renderEscalations();
    renderNotifications();
    updateServiceTicketCounts();
    renderTicketReportPreview();
    showTicketToast(`Incident ticket ${ticket.id} deleted permanently.`);
  }
}

function deleteIncidentTicketFromDrawer() {
  if (activeTicketId) {
    deleteIncidentTicket(activeTicketId);
  }
}

// Quick Create by Index from Service Inventory
function quickCreateTicketByIndex(idx) {
  if (!liveActiveServices || !liveActiveServices[idx]) return;
  quickCreateTicketForService(liveActiveServices[idx]);
}

function quickCreateTicketForService(serviceObj) {
  if (!serviceObj) return;
  const serviceBiz = (serviceObj.partner_name && serviceObj.partner_name !== 'Direct Enterprise') ? 'NNL' : 'SBE';
  if (serviceBiz !== currentTicketBiz) {
    switchTicketBiz(serviceBiz);
  }
  openCreateTicketModal();
  const ckt = serviceObj.circuit_id || ('CKT-FR-' + serviceObj.order_id);
  document.getElementById('tInpCustomer').value = serviceObj.customer_name || '';
  document.getElementById('tInpCircuit').value = ckt;
  document.getElementById('tInpBuilding').value = serviceObj.building_name || 'Main Premise';

  const sel = document.getElementById('tInpServiceSelect');
  if (sel) {
    let matched = false;
    for (let i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value === ckt || sel.options[i].value === serviceObj.circuit_id || sel.options[i].value === String(serviceObj.order_id)) {
        sel.selectedIndex = i;
        matched = true;
        break;
      }
    }
    if (!matched) sel.value = 'CUSTOM_CIRCUIT';
  }
}

function fillServiceDetails(circuitVal) {
  const sel = document.getElementById('tInpServiceSelect');
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  if (opt && opt.dataset.customer) {
    document.getElementById('tInpCustomer').value = opt.dataset.customer;
    document.getElementById('tInpCircuit').value = opt.dataset.circuit;
    document.getElementById('tInpBuilding').value = opt.dataset.building;
  }
}

// Modal open/close
function openCreateTicketModal() {
  const title = document.getElementById('createTicketModalTitle');
  if (title) {
    title.textContent = `Log Incident Ticket — ${currentTicketBiz === 'NNL' ? 'NNL Wholesale NOC' : 'Savanna Enterprise SBE'}`;
  }
  document.getElementById('createTicketBackdrop').classList.add('active');
}
function closeCreateTicketModal() {
  document.getElementById('createTicketBackdrop').classList.remove('active');
}
function closeTicketModalOnBackdrop(e) {
  if (e.target === document.getElementById('createTicketBackdrop')) {
    closeCreateTicketModal();
  }
}

function handleCreateTicket(e) {
  e.preventDefault();
  const nextNum = String(Date.now()).slice(-4);
  const prio = document.getElementById('tInpPriority').value;
  const targetMap = { 'P1': 4, 'P2': 8, 'P3': 24, 'P4': 48 };

  const sel = document.getElementById('tInpServiceSelect');
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  const detectedService = (opt && opt.dataset.service) ? opt.dataset.service : 'Enterprise Fiber';

  const newTicket = {
    id: `TKT-${currentTicketBiz === 'SBE' ? 'SBE-' : ''}${new Date().toISOString().slice(2,10).replace(/-/g,'')}-${nextNum}`,
    company: currentTicketBiz,
    customer: document.getElementById('tInpCustomer').value,
    circuit: document.getElementById('tInpCircuit').value || 'CKT-NEW',
    building: document.getElementById('tInpBuilding').value || 'Dar es Salaam',
    location: 'Dar es Salaam',
    service: detectedService,
    category: document.getElementById('tInpCategory').value,
    priority: prio,
    tier: document.getElementById('tInpTier').value,
    status: 'In Progress',
    assignee: 'NOC Support Lead',
    slaTargetHours: targetMap[prio] || 8,
    elapsedHours: 0.1,
    isOnHold: false,
    holdReason: '',
    description: document.getElementById('tInpDescription').value,
    history: [
      { time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}), note: `Ticket created for ${currentTicketBiz}. Auto-dispatched alert to NOC & KAM.` }
    ]
  };

  ticketsList.unshift(newTicket);

  notificationsList.unshift({
    time: new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}),
    id: newTicket.id,
    company: currentTicketBiz,
    channel: 'Email & SMS Broadcast',
    subject: `New ${newTicket.priority} Incident (${newTicket.category})`,
    text: `Dispatched to NOC for ${newTicket.customer} (${newTicket.circuit})`,
    status: 'Sent'
  });

  saveTicketsData();
  closeCreateTicketModal();
  document.getElementById('createTicketForm').reset();
  renderOverview();
  renderTicketsTable();
  renderEscalations();
  renderNotifications();
  filterServicesByBiz();
  updateServiceTicketCounts();
  renderTicketReportPreview();
  showTicketTab('active_tickets');
  showTicketToast(`New incident ticket logged for ${currentTicketBiz === 'NNL' ? 'NNL Wholesale' : 'Savanna SBE'}: ${newTicket.id}`);
}

// Support Tier Filter Synchronizer
function handleTierChange(val) {
  activeTicketTier = val;
  const ts = document.getElementById('tierSelector');
  const tf = document.getElementById('ticketTierFilter');
  if (ts) ts.value = val;
  if (tf) tf.value = val;
  renderOverview();
  renderTicketsTable();
  renderEscalations();
}

// Escalations View
function renderEscalations() {
  const tbody = document.getElementById('slaEscalatedTbody');
  if (!tbody) return;

  const escalated = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz && (t.status === 'Escalated' || t.tier !== 'L1' || t.elapsedHours >= t.slaTargetHours));
  document.getElementById('slaEscalatedBadge').textContent = escalated.length + ' Active';

  if (escalated.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8">No escalated or breached incidents for ${currentTicketBiz === 'NNL' ? 'NNL Wholesale' : 'Savanna SBE'}.</td></tr>`;
    return;
  }

  let html = '';
  escalated.forEach(t => {
    html += `
      <tr style="cursor:pointer" onclick="openTicketDrawer('${t.id}')">
        <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(t.id)}</strong></td>
        <td>
          <div style="font-weight:600">${escapeHtml(t.customer)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">${escapeHtml(t.circuit)}</div>
        </td>
        <td><span class="tier-badge tier-${t.tier.toLowerCase()}">${t.tier}</span></td>
        <td style="font-weight:600;color:${t.elapsedHours >= t.slaTargetHours ? '#dc2626' : '#334155'}">${t.elapsedHours.toFixed(1)} / ${t.slaTargetHours}h</td>
        <td style="text-align:center" onclick="event.stopPropagation()">
          <button class="btn btn-sm btn-secondary" onclick="openTicketDrawer('${t.id}')">Manage &rarr;</button>
          ${canUserDeleteTicket() ? `<button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteIncidentTicket('${t.id}')" title="Delete Incident Ticket" style="padding:4px 8px;font-size:0.75rem;border-color:#fca5a5;color:#dc2626;margin-left:4px">&#128465;</button>` : ''}
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

// Simulator: Advance Time
function simulateTimeAdvance(hours) {
  ticketsList.forEach(t => {
    if ((t.company || 'NNL') === currentTicketBiz && t.status !== 'Closed' && !t.isOnHold) {
      t.elapsedHours += hours;
      // Auto-escalation logic
      if (t.elapsedHours >= 6 && t.tier !== 'Management') {
        t.tier = 'Management';
        t.status = 'Escalated';
        t.history.unshift({ time: 'Simulation', note: `Elapsed ${t.elapsedHours.toFixed(1)}h exceeded 6h threshold. Escalated to Executive Management.` });
      } else if (t.elapsedHours >= 4 && t.tier === 'L1') {
        t.tier = 'Technical';
        t.status = 'Escalated';
        t.history.unshift({ time: 'Simulation', note: `Elapsed ${t.elapsedHours.toFixed(1)}h exceeded 4h threshold. Escalated to Technical Lead.` });
      } else if (t.elapsedHours >= 2 && t.tier === 'L1') {
        t.tier = 'L2';
        t.status = 'Escalated';
        t.history.unshift({ time: 'Simulation', note: `Elapsed ${t.elapsedHours.toFixed(1)}h exceeded 2h threshold. Escalated to L2 Specialist.` });
      }
    }
  });
  saveTicketsData();
  renderOverview();
  renderTicketsTable();
  renderEscalations();
  renderTicketReportPreview();
  showTicketToast(`Simulated +${hours} hours elapsed across active ${currentTicketBiz} SLA clocks.`);
}

function resetSimulatedTime() {
  ticketsList = JSON.parse(JSON.stringify(DEFAULT_TICKETS));
  saveTicketsData();
  renderOverview();
  renderTicketsTable();
  renderEscalations();
  filterServicesByBiz();
  updateServiceTicketCounts();
  renderTicketReportPreview();
  showTicketToast('SLA clocks and tier states reset to baseline.');
}

// Toast Feedback Helper
function showTicketToast(msg) {
  const t = document.getElementById('ticketToast');
  const m = document.getElementById('ticketToastMsg');
  if (t && m) {
    m.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
  }
}

// Update Service Inventory Previous Ticket Counters
function updateServiceTicketCounts() {
  if (!liveActiveServices || !Array.isArray(liveActiveServices)) return;
  liveActiveServices.forEach(as => {
    const ckt = as.circuit_id || ('CKT-FR-' + as.order_id);
    const count = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz && t.circuit === ckt).length;
    const el = document.getElementById('svcTicketCount_' + ckt);
    if (el) {
      el.textContent = count;
      if (count > 0) {
        el.className = 'badge badge-warning';
      } else {
        el.className = 'badge badge-secondary';
      }
    }
  });
}

// Reports & Export View
function renderTicketReportPreview() {
  const tbody = document.getElementById('ticketReportPreviewBody');
  if (!tbody) return;

  const prio = document.getElementById('reportPrio')?.value || 'All';
  const tier = document.getElementById('reportTier')?.value || 'All';
  const status = document.getElementById('reportStatus')?.value || 'All';

  const bizTickets = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz);
  const filtered = bizTickets.filter(t => {
    if (prio !== 'All' && t.priority !== prio) return false;
    if (tier !== 'All' && t.tier !== tier) return false;
    if (status !== 'All') {
      if (status === 'Closed' && t.status !== 'Closed') return false;
      if (status === 'In Progress' && (t.status === 'Closed' || t.status === 'On Hold')) return false;
      if (status === 'On Hold' && !t.isOnHold && t.status !== 'On Hold') return false;
    }
    return true;
  });

  const countBadge = document.getElementById('ticketReportFilteredCount');
  if (countBadge) countBadge.textContent = filtered.length;

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:26px;color:#94a3b8">No incident records match the selected report criteria.</td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach(t => {
    const isBreached = (t.elapsedHours >= t.slaTargetHours);
    const compBadge = isBreached 
      ? '<span class="badge badge-danger">Breached</span>'
      : '<span class="badge badge-success">Met Target</span>';

    html += `
      <tr>
        <td><strong style="color:var(--accent, #0F4C81)">${escapeHtml(t.id)}</strong></td>
        <td>
          <div style="font-weight:600">${escapeHtml(t.customer)}</div>
          <div style="font-size:0.72rem;color:#94a3b8">${escapeHtml(t.building)}</div>
        </td>
        <td><code>${escapeHtml(t.circuit)}</code></td>
        <td>${escapeHtml(t.category)}</td>
        <td><span class="prio-pill prio-${t.priority.toLowerCase()}">${t.priority}</span></td>
        <td><span class="tier-badge tier-${t.tier.toLowerCase()}">${t.tier}</span></td>
        <td style="font-weight:600">${t.elapsedHours.toFixed(1)} hrs</td>
        <td>${t.slaTargetHours} hrs</td>
        <td>${compBadge}</td>
        <td><span class="badge ${t.status === 'Closed' ? 'badge-success' : (t.status === 'On Hold' ? 'badge-warning' : 'badge-info')}">${t.status}</span></td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function downloadTicketsCSV(isAuditOnly) {
  let csv = isAuditOnly
    ? 'Ticket ID,Company,Customer,Circuit ID,Service Type,Fault Category,Priority,Target SLA (Hours),Elapsed Time (Hours),SLA Compliance,Assigned Tier,Final Status,Lead Engineer\n'
    : 'Ticket ID,Company,Customer,Building,Location,Circuit ID,Service Type,Fault Category,Priority,Assigned Tier,Elapsed (Hrs),Target (Hrs),Status,Assignee,Description\n';

  const bizTickets = ticketsList.filter(t => (t.company || 'NNL') === currentTicketBiz);
  bizTickets.forEach(t => {
    const isBreached = (t.elapsedHours >= t.slaTargetHours);
    const comp = isBreached ? 'BREACHED' : 'COMPLIANT';

    if (isAuditOnly) {
      csv += `"${t.id}","${t.company}","${(t.customer||'').replace(/"/g, '""')}","${t.circuit}","${(t.service||'').replace(/"/g, '""')}","${(t.category||'').replace(/"/g, '""')}","${t.priority}",${t.slaTargetHours},${t.elapsedHours.toFixed(1)},"${comp}","${t.tier}","${t.status}","${(t.assignee||'').replace(/"/g, '""')}"\n`;
    } else {
      csv += `"${t.id}","${t.company}","${(t.customer||'').replace(/"/g, '""')}","${(t.building||'').replace(/"/g, '""')}","${(t.location||'').replace(/"/g, '""')}","${t.circuit}","${(t.service||'').replace(/"/g, '""')}","${(t.category||'').replace(/"/g, '""')}","${t.priority}","${t.tier}",${t.elapsedHours.toFixed(1)},${t.slaTargetHours},"${t.status}","${(t.assignee||'').replace(/"/g, '""')}","${(t.description||'').replace(/"/g, '""')}"\n`;
    }
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const prefix = currentTicketBiz === 'SBE' ? 'Savanna_Enterprise_SBE' : 'NNL_Wholesale';
  a.download = isAuditOnly 
    ? `${prefix}_SLA_Audit_Report_${new Date().toISOString().slice(0,10)}.csv` 
    : `${prefix}_Incident_Log_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  showTicketToast(`${currentTicketBiz === 'SBE' ? 'Savanna SBE' : 'NNL Wholesale'} ticket report downloaded.`);
}

// Notifications History
const DEFAULT_NOTIFICATIONS = [];
let notificationsList = [];
try {
  const saved = localStorage.getItem(STORAGE_KEY_NOTIFS);
  if (saved) {
    notificationsList = JSON.parse(saved);
  } else {
    notificationsList = [];
    localStorage.setItem(STORAGE_KEY_NOTIFS, JSON.stringify(notificationsList));
  }
} catch (e) {
  notificationsList = [];
}

function saveNotificationsData() {
  try {
    localStorage.setItem(STORAGE_KEY_NOTIFS, JSON.stringify(notificationsList));
  } catch (e) {}
}

function renderNotifications() {
  const tbody = document.getElementById('notificationLogTbody');
  if (!tbody) return;

  const filteredLogs = notificationsList.filter(l => !l.company || l.company === currentTicketBiz);

  if (filteredLogs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8">No notifications recorded for ${currentTicketBiz === 'NNL' ? 'NNL Wholesale' : 'Savanna SBE'}.</td></tr>`;
    return;
  }

  let html = '';
  filteredLogs.forEach(l => {
    html += `
      <tr>
        <td style="color:#64748b;white-space:nowrap">${l.time}</td>
        <td><strong>${escapeHtml(l.id)}</strong></td>
        <td>${escapeHtml(l.channel)}</td>
        <td>${escapeHtml(l.subject)}</td>
        <td style="color:#475569">${escapeHtml(l.text)}</td>
        <td><span class="badge badge-success">${l.status}</span></td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function() {
  const shell = document.getElementById('ticketShell') || document.querySelector('.ticket-shell');
  if (shell) {
    shell.classList.toggle('theme-sbe', currentTicketBiz === 'SBE');
  }
  document.getElementById('tBtnNNL').classList.toggle('active', currentTicketBiz === 'NNL');
  document.getElementById('tBtnSBE').classList.toggle('active', currentTicketBiz === 'SBE');
  document.getElementById('ticketBrandBadge').textContent = currentTicketBiz === 'NNL' ? 'NNL NOC' : 'Savanna SBE';
  renderOverview();
  renderTicketsTable();
  renderEscalations();
  renderNotifications();
  filterServicesByBiz();
  updateServiceTicketCounts();
  renderTicketReportPreview();
});
</script>
