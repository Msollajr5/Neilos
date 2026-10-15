<?php
  $reqCat = strtolower(trim($_GET['category'] ?? $_GET['type'] ?? ''));
  if ($action === 'create' && ($reqCat === 'enterprise' || $reqCat === 'enterprise client')) {
      $currentCategory = 'Enterprise Client';
  } else {
      $currentCategory = $partner['partner_category'] ?? (($partner['partner_type'] ?? '') === 'Enterprise' ? 'Enterprise Client' : 'Partner');
  }
  $formTitle = $action === 'create' 
    ? ($currentCategory === 'Enterprise Client' ? 'New Enterprise Client' : 'New Partner') 
    : ($currentCategory === 'Enterprise Client' ? 'Edit Enterprise Client' : 'Edit Partner');
  $formSubtitle = $action === 'create'
    ? ($currentCategory === 'Enterprise Client' ? 'Add a new corporate or business client' : 'Add a new partner organization')
    : 'Update organization details';
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><?= e($formTitle) ?></h1>
    <div class="page-subtitle"><?= e($formSubtitle) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary"><?= svgIcon('list') ?> Partners &amp; Clients</a>
  </div>
</div>
<div class="card card-max-800">
  <div class="card-header"><div class="card-title"><?= $action === 'create' ? 'Organization & Account Type' : 'Edit Organization Details' ?></div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=partners&action=<?= e($action) ?>">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <?php if ($action === 'edit' && $partner): ?>
      <input type="hidden" name="id" value="<?= $partner['id'] ?>">
      <?php endif; ?>

      <!-- 1. Account / Category Selection -->
      <div class="form-group mb-20" style="margin-bottom:24px">
        <label style="font-weight:700;font-size:0.92rem;color:var(--text-primary);margin-bottom:10px;display:block">
          Account Classification <span class="text-danger">*</span>
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:14px">
          <!-- Option 1: Wholesale Partner -->
          <label class="partner-type-card" style="border:2px solid <?= $currentCategory === 'Partner' ? 'var(--primary)' : 'var(--border)' ?>;border-radius:var(--radius-md);padding:16px;cursor:pointer;background:<?= $currentCategory === 'Partner' ? 'rgba(15,76,129,0.04)' : 'var(--surface-1)' ?>;display:flex;align-items:flex-start;gap:12px;transition:all .2s ease" id="cardPartner">
            <input type="radio" name="partner_category" value="Partner" <?= $currentCategory === 'Partner' ? 'checked' : '' ?> onchange="onCategoryChange('Partner')" style="margin-top:4px">
            <div>
              <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
                🏢 Wholesale Partner / ISP
              </div>
              <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;line-height:1.4">
                ISP, Reseller, or Telecom partner who orders connectivity on behalf of third-party retail subscribers.
              </div>
            </div>
          </label>

          <!-- Option 2: Enterprise Client -->
          <label class="partner-type-card" style="border:2px solid <?= $currentCategory === 'Enterprise Client' ? 'var(--primary)' : 'var(--border)' ?>;border-radius:var(--radius-md);padding:16px;cursor:pointer;background:<?= $currentCategory === 'Enterprise Client' ? 'rgba(15,76,129,0.04)' : 'var(--surface-1)' ?>;display:flex;align-items:flex-start;gap:12px;transition:all .2s ease" id="cardEnterprise">
            <input type="radio" name="partner_category" value="Enterprise Client" <?= $currentCategory === 'Enterprise Client' ? 'checked' : '' ?> onchange="onCategoryChange('Enterprise Client')" style="margin-top:4px">
            <div>
              <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
                🏛️ Enterprise / Business Client
              </div>
              <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;line-height:1.4">
                Direct corporate/business customer. Service orders will <strong>automatically pre-fill</strong> with this client's profile.
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Enterprise Auto-Fill Notice -->
      <div id="enterpriseHelperNotice" style="display:<?= $currentCategory === 'Enterprise Client' ? 'flex' : 'none' ?>;align-items:center;gap:10px;padding:12px 16px;background:rgba(15,76,129,0.06);border:1px solid rgba(15,76,129,0.2);border-radius:var(--radius-md);margin-bottom:20px;font-size:0.85rem;color:var(--primary)">
        <span style="font-size:1.2rem">💡</span>
        <div><strong>Enterprise Mode Active:</strong> Customer Name, Location, Contact Person, Phone, and Email entered below (or via KYC) will automatically populate whenever creating New Service Orders for this client.</div>
      </div>

      <!-- 2. Basic Information -->
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label for="name" id="nameLabel"><?= $currentCategory === 'Enterprise Client' ? 'Client / Company Name' : 'Partner Name' ?> <span class="text-danger">*</span></label>
          <input type="text" id="name" name="name" class="form-control" required
                 value="<?= e($partner['name'] ?? '') ?>" placeholder="e.g. Acme Corporation Ltd">
        </div>
        <div class="form-group">
          <label for="trading_name">Trading Name / Brand</label>
          <input type="text" id="trading_name" name="trading_name" class="form-control"
                 value="<?= e($partner['trading_name'] ?? '') ?>" placeholder="e.g. Acme Telecom">
        </div>
        <div class="form-group">
          <label for="partner_type">Industry / Partner Type <span class="text-danger">*</span></label>
          <select id="partner_type" name="partner_type" class="form-control" required>
            <option value="Enterprise" <?= ($partner['partner_type'] ?? '') === 'Enterprise' || $currentCategory === 'Enterprise Client' ? 'selected' : '' ?>>Enterprise (Corporate)</option>
            <option value="ISP" <?= ($partner['partner_type'] ?? '') === 'ISP' ? 'selected' : '' ?>>ISP (Internet Service Provider)</option>
            <option value="Reseller" <?= ($partner['partner_type'] ?? '') === 'Reseller' ? 'selected' : '' ?>>Reseller</option>
            <option value="VAR" <?= ($partner['partner_type'] ?? '') === 'VAR' ? 'selected' : '' ?>>VAR (Value-Added Reseller)</option>
            <option value="Government" <?= ($partner['partner_type'] ?? '') === 'Government' ? 'selected' : '' ?>>Government / Public Sector</option>
            <option value="Other" <?= ($partner['partner_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other Commercial</option>
          </select>
        </div>
        <div class="form-group">
          <label for="kam_id">Assigned KAM</label>
          <select id="kam_id" name="kam_id" class="form-control">
            <option value="">— Select KAM —</option>
            <?php 
              $defaultKamId = $partner['kam_id'] ?? (isKamUser() ? (currentUser()['id'] ?? 0) : 0);
              foreach ($kamList as $k): 
            ?>
            <option value="<?= $k['id'] ?>" <?= ((int)$defaultKamId === (int)$k['id']) ? 'selected' : '' ?>><?= e($k['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="status">Account Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="form-control" required>
            <option value="Active" <?= ($partner['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= ($partner['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="Suspended" <?= ($partner['status'] ?? '') === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
        <div class="form-group">
          <label for="registration_number">Registration / BRELA Number</label>
          <input type="text" id="registration_number" name="registration_number" class="form-control"
                 value="<?= e($partner['registration_number'] ?? '') ?>" placeholder="e.g. 123456789">
        </div>
        <div class="form-group">
          <label for="tin">TIN Number</label>
          <input type="text" id="tin" name="tin" class="form-control"
                 value="<?= e($partner['tin'] ?? '') ?>" placeholder="e.g. 100-200-300">
        </div>
        <div class="form-group">
          <label for="city_region">City / Region</label>
          <input type="text" id="city_region" name="city_region" class="form-control"
                 value="<?= e($partner['city_region'] ?? '') ?>" placeholder="e.g. Dar es Salaam">
        </div>
        <div class="form-group">
          <label for="country">Country</label>
          <input type="text" id="country" name="country" class="form-control"
                 value="<?= e($partner['country'] ?? 'Tanzania') ?>">
        </div>
      </div>

      <!-- 3. Primary Contact & Location Details -->
      <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);margin:20px 0 12px 0;padding-top:16px;border-top:1px solid var(--border)">
        👤 Primary Contact Person &amp; Headquarters
      </div>

      <div class="form-grid form-grid-3" style="margin-bottom:14px">
        <div class="form-group">
          <label for="contact_person_name">Contact Person Name</label>
          <input type="text" id="contact_person_name" name="contact_person_name" class="form-control"
                 value="<?= e($partner['contact_person_name'] ?? '') ?>" placeholder="Full Name">
        </div>
        <div class="form-group">
          <label for="contact_phone">Contact Phone Number</label>
          <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                 value="<?= e($partner['contact_phone'] ?? '') ?>" placeholder="+255 7xx xxx xxx">
        </div>
        <div class="form-group">
          <label for="contact_email">Contact Email Address</label>
          <input type="email" id="contact_email" name="contact_email" class="form-control"
                 value="<?= e($partner['contact_email'] ?? '') ?>" placeholder="contact@example.com">
        </div>
      </div>

      <div class="form-group" style="margin-top:6px">
        <label for="address">Physical Address / Headquarters Location</label>
        <textarea id="address" name="address" class="form-control" rows="2" placeholder="Street address, building, floor..."><?= e($partner['address'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:10px;margin-top:24px">
        <button type="submit" class="btn btn-primary"><?= svgIcon($action === 'create' ? 'plus' : 'edit') ?> <?= $action === 'create' ? 'Create Organization' : 'Save Changes' ?></button>
        <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function onCategoryChange(cat) {
  const cardPartner = document.getElementById('cardPartner');
  const cardEnterprise = document.getElementById('cardEnterprise');
  const notice = document.getElementById('enterpriseHelperNotice');
  const nameLabel = document.getElementById('nameLabel');
  const typeSelect = document.getElementById('partner_type');

  if (cat === 'Enterprise Client') {
    cardEnterprise.style.borderColor = 'var(--primary)';
    cardEnterprise.style.background = 'rgba(15,76,129,0.06)';
    cardPartner.style.borderColor = 'var(--border)';
    cardPartner.style.background = 'var(--surface-1)';
    if (notice) notice.style.display = 'flex';
    if (nameLabel) nameLabel.innerHTML = 'Client / Company Name <span class="text-danger">*</span>';
    if (typeSelect && typeSelect.value === 'ISP') typeSelect.value = 'Enterprise';
  } else {
    cardPartner.style.borderColor = 'var(--primary)';
    cardPartner.style.background = 'rgba(15,76,129,0.06)';
    cardEnterprise.style.borderColor = 'var(--border)';
    cardEnterprise.style.background = 'var(--surface-1)';
    if (notice) notice.style.display = 'none';
    if (nameLabel) nameLabel.innerHTML = 'Partner Name <span class="text-danger">*</span>';
    if (typeSelect && typeSelect.value === 'Enterprise') typeSelect.value = 'ISP';
  }
}
</script>
