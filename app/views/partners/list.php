<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <?php if ($typeFilter === 'enterprise'): ?>
        🏛️ Enterprise Clients
      <?php elseif ($typeFilter === 'partner'): ?>
        🏢 Wholesale Partners
      <?php else: ?>
        🏢 Partners &amp; Clients
      <?php endif; ?>
    </h1>
    <div class="page-subtitle">
      <?php if ($typeFilter === 'enterprise'): ?>
        Direct corporate &amp; business client accounts (<?= $total ?> client<?= $total === 1 ? '' : 's' ?> <?= isKamUser() ? 'in your portfolio' : 'found' ?>)
      <?php elseif ($typeFilter === 'partner'): ?>
        Telecom partners, ISPs, and resellers (<?= $total ?> partner<?= $total === 1 ? '' : 's' ?> <?= isKamUser() ? 'in your portfolio' : 'found' ?>)
      <?php else: ?>
        <?= $countPartners ?> wholesale partner<?= $countPartners === 1 ? '' : 's' ?> &middot; <?= $countEnterprise ?> enterprise client<?= $countEnterprise === 1 ? '' : 's' ?> <?= isKamUser() ? 'managed in your portfolio' : 'registered' ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="page-header-actions" style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if (hasPermission('partners.manage') || isAdmin() || hasRole('Management', 'KAM')): ?>
      <?php if ($typeFilter === 'enterprise'): ?>
        <a href="<?= APP_URL ?>/?page=partners&action=create&category=enterprise" class="btn btn-primary"><?= svgIcon('plus') ?> New Enterprise Client</a>
      <?php elseif ($typeFilter === 'partner'): ?>
        <a href="<?= APP_URL ?>/?page=partners&action=create&category=partner" class="btn btn-primary"><?= svgIcon('plus') ?> New Partner</a>
      <?php else: ?>
        <a href="<?= APP_URL ?>/?page=partners&action=create" class="btn btn-primary"><?= svgIcon('plus') ?> New Organization</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Category Tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:10px;flex-wrap:wrap">
  <a href="<?= APP_URL ?>/?page=partners" 
     class="btn <?= $typeFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
     style="font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;border-radius:var(--radius-full);padding:6px 14px">
    All Accounts <span style="background:<?= $typeFilter === 'all' ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)' ?>;padding:1px 7px;border-radius:10px;font-size:0.75rem"><?= $countAll ?></span>
  </a>
  <a href="<?= APP_URL ?>/?page=partners&type=partner" 
     class="btn <?= $typeFilter === 'partner' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
     style="font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;border-radius:var(--radius-full);padding:6px 14px">
    🏢 Wholesale Partners <span style="background:<?= $typeFilter === 'partner' ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)' ?>;padding:1px 7px;border-radius:10px;font-size:0.75rem"><?= $countPartners ?></span>
  </a>
  <a href="<?= APP_URL ?>/?page=partners&type=enterprise" 
     class="btn <?= $typeFilter === 'enterprise' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
     style="font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;border-radius:var(--radius-full);padding:6px 14px">
    🏛️ Enterprise Clients <span style="background:<?= $typeFilter === 'enterprise' ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)' ?>;padding:1px 7px;border-radius:10px;font-size:0.75rem"><?= $countEnterprise ?></span>
  </a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Organization / Partner Name</th>
          <th>Account Type</th>
          <th>Industry</th>
          <th>Assigned KAM</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($partners)): ?>
        <tr><td colspan="7"><div class="empty-state"><?= svgIcon('building', 32) ?><div class="empty-state-title">No partners or clients found</div><div class="empty-state-text"><?= isKamUser() ? 'You have not onboarded any partners or enterprise clients yet. Click "New Partner" to add your first organization.' : 'Click "New Partner" to add the first organization.' ?></div></div></td></tr>
        <?php else: ?>
        <?php foreach ($partners as $p): ?>
        <?php
          $isEnterprise = (($p['partner_category'] ?? '') === 'Enterprise Client' || ($p['partner_type'] ?? '') === 'Enterprise');
        ?>
        <tr>
          <td>
            <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $p['id'] ?>" class="font-600" style="color:var(--primary)">
              <?= e($p['name']) ?>
            </a>
            <?php if (!empty($p['trading_name'])): ?>
              <div class="text-muted font-sm" style="font-size:0.75rem"><?= e($p['trading_name']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($isEnterprise): ?>
              <span class="badge badge-info" style="font-size:0.75rem;display:inline-flex;align-items:center;gap:4px">
                🏛️ Enterprise Client
              </span>
            <?php else: ?>
              <span class="badge badge-secondary" style="font-size:0.75rem;display:inline-flex;align-items:center;gap:4px">
                🏢 Wholesale Partner
              </span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-primary"><?= e($p['partner_type'] ?: 'ISP') ?></span></td>
          <td><?= e($p['assigned_kam_name'] ?: '—') ?></td>
          <td>
            <?php if ($p['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($p['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </td>
          <td class="text-muted font-sm"><?= fmtDate($p['created_at']) ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="View Partner" style="display:inline-flex;align-items:center;gap:4px;font-size:0.78rem;padding:4px 10px">
                <?= svgIcon('eye', 13) ?> View
              </a>
              <a href="<?= APP_URL ?>/?page=partners&action=edit&id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit"><?= svgIcon('edit', 13) ?></a>
              <?php 
                $canDeletePartner = (isAdmin() || hasRole('Management') || (isKamUser() && ((int)($p['kam_id'] ?? 0) === (int)($user['id'] ?? 0) || ($p['assigned_kam_name'] ?? '') === ($user['full_name'] ?? ''))));
                if ($canDeletePartner): 
              ?>
              <form method="POST" action="<?= APP_URL ?>/?page=partners&action=delete" style="display:inline" data-confirm="Permanently delete partner <?= e($p['name']) ?>?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Partner"><?= svgIcon('trash', 13) ?></button>
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
