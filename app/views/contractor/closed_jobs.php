<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Closed Installation Jobs</div>
    <div class="page-subtitle">Historical records of successfully completed and closed installation orders.</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-secondary">
      <?= svgIcon('arrow-left') ?> Back to Active Jobs
    </a>
  </div>
</div>

<!-- Navigation Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
  <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-ghost btn-sm" style="font-weight:600">
    ⚡ Active &amp; Ongoing Jobs
  </a>
  <a href="<?= APP_URL ?>/?page=contractor&action=closed_jobs" class="btn btn-primary btn-sm" style="font-weight:600">
    <?= svgIcon('check') ?> Closed Jobs (<?= count($closedJobs) ?>)
  </a>
  <a href="<?= APP_URL ?>/?page=contractor&action=teams" class="btn btn-ghost btn-sm" style="font-weight:600">
    👥 Teams &amp; Technicians
  </a>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:16px;border:1px solid var(--border)">
  <div class="card-body" style="padding:12px 16px">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="page" value="contractor">
      <input type="hidden" name="action" value="closed_jobs">
      <div style="flex:1;min-width:240px;position:relative">
        <input type="text" name="q" class="form-control" placeholder="Search by Order Number or Field Team..." value="<?= e($_GET['q'] ?? '') ?>" style="padding-left:36px">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-secondary);font-size:0.9rem">🔍</span>
      </div>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if (!empty($_GET['q'])): ?>
        <a href="<?= APP_URL ?>/?page=contractor&action=closed_jobs" class="btn btn-ghost btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Privacy-Safe Closed Jobs Table -->
<div class="card" style="border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:60px;text-align:center">S/N</th>
          <th>Order Number</th>
          <?php if (!isContractorUser()): ?>
            <th>Contractor Company</th>
          <?php endif; ?>
          <th>Date Assigned</th>
          <th>Date Closed</th>
          <th>Team that Closed It</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($closedJobs)): ?>
        <tr>
          <td colspan="<?= !isContractorUser() ? 6 : 5 ?>" style="text-align:center;padding:40px;color:var(--text-muted)">
            <div style="font-size:2rem;margin-bottom:8px">📁</div>
            <div style="font-weight:600">No closed jobs found</div>
            <div class="font-sm text-secondary">Completed and closed orders will appear here once finalized.</div>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($closedJobs as $idx => $job): ?>
        <tr>
          <td style="text-align:center;font-weight:600;color:var(--text-secondary)"><?= $idx + 1 ?></td>
          <td class="font-600">
            <span style="font-family:monospace;font-size:0.92rem;color:var(--primary);letter-spacing:0.02em">
              <?= e($job['order_number']) ?>
            </span>
          </td>
          <?php if (!isContractorUser()): ?>
            <td><?= e($job['contractor_name']) ?></td>
          <?php endif; ?>
          <td class="font-sm text-secondary">
            <?= !empty($job['assigned_at']) ? fmtDate($job['assigned_at']) : '—' ?>
          </td>
          <td class="font-sm font-600" style="color:var(--success)">
            <?= !empty($job['closed_date']) ? fmtDate($job['closed_date']) : (!empty($job['completed_at']) ? fmtDate($job['completed_at']) : fmtDate($job['updated_at'])) ?>
          </td>
          <td>
            <?php if (!empty($job['contractor_team_name'])): ?>
              <span class="badge badge-secondary" style="font-weight:600;padding:4px 8px">
                👷 <?= e($job['contractor_team_name']) ?>
              </span>
            <?php else: ?>
              <span class="text-muted font-sm" style="font-style:italic">Direct Field Assignment</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
