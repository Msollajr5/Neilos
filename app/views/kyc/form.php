<?php
// ============================================================
// Neilos Partner Portal — One-Page KYC Application & Review Form
// ============================================================

$canCreateOrEdit = (isAdmin() || hasRole('Management', 'Project Manager', 'KAM', 'Partner', 'Partner User', 'Contractor', 'Contractor User') || hasPermission('kyc.create') || isPartnerUser() || isContractorUser());
$canApproveOrReject = (isFinance() || isAdmin() || isManagement() || hasRole('Management', 'Billing', 'Finance') || hasPermission('kyc.approve'));
$isReviewer = $canApproveOrReject;
$isPM = isProjectManager();
$isAdminOrMgmt = (isAdmin() || hasRole('Management', 'Project Manager', 'KAM'));
$status = $app['status'] ?? 'Draft';
$kycType = $app['kyc_type'] ?? ($_GET['type'] ?? ($isPM ? 'Contractor' : 'Partner'));
$selectedPartnerId = $app['partner_id'] ?? ($_GET['partner_id'] ?? (isPartnerUser() || isContractorUser() ? ($user['partner_id'] ?? 0) : 0));
$appId = (int)($app['id'] ?? 0);

$isReadonly = ($status === 'Approved') || (in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted']) && !$canCreateOrEdit);

$customFields = json_decode($app['custom_fields'] ?? '[]', true) ?: [];
$selectedRegions = array_map('trim', explode(',', $app['service_regions'] ?? ''));

$availableRegions = ['DAR ES SALAAM', 'DODOMA', 'ARUSHA', 'MBEYA', 'MWANZA', 'TANGA', 'ZANZIBAR', 'MOROGORO', 'SHINYANGA', 'KIGOMA', 'KILIMANJARO', 'PEMBA'];
$paymentTermsOptions = ['7 Days', '15 Days', '30 Days', '45 Days', '60 Days'];

// Helper to render one of the compliance document upload rows with status badges & actions
function renderComplianceDocUploadRow($docKey, $docLabel, $filePath, $appId, $isReadonly, $extraHtml = '', $isOptional = false) {
    $hasFile = !empty($filePath);
    $fileName = basename($filePath);
    $downloadUrl = APP_URL . "/?page=download&table=partner_kyc_applications&column={$docKey}&id={$appId}";
    
    $fileExistsOnDisk = false;
    if ($hasFile) {
        $rel = ltrim(str_replace('\\', '/', $filePath), '/');
        $checkPath = PUBLIC_DIR . '/' . $rel;
        if (!is_file($checkPath)) {
            $checkPath = dirname(APP_DIR) . '/public/' . $rel;
        }
        if (!is_file($checkPath)) {
            $checkPath = dirname(APP_DIR) . '/' . $rel;
        }
        $fileExistsOnDisk = is_file($checkPath);
    }

    ob_start();
    ?>
    <div class="kyc-doc-upload-row" data-doc-key="<?= e($docKey) ?>" data-is-optional="<?= $isOptional ? '1' : '0' ?>" data-has-file="<?= $hasFile ? '1' : '0' ?>" data-file-on-disk="<?= $fileExistsOnDisk ? '1' : '0' ?>" style="padding:13px 16px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <strong style="font-size:0.91rem;color:var(--text-primary)">
            <?= e($docLabel) ?>
            <?php if ($isOptional): ?>
              <span style="font-weight:400;color:var(--text-muted);font-size:0.8rem">(Optional)</span>
            <?php else: ?>
              <span class="text-danger">*</span>
            <?php endif; ?>
          </strong>
          <?php if ($hasFile && $fileExistsOnDisk): ?>
            <span class="badge-uploaded" style="display:inline-flex;align-items:center;gap:4px">✓ Uploaded</span>
          <?php elseif ($hasFile && !$fileExistsOnDisk): ?>
            <span class="badge badge-warning" style="font-size:0.75rem;padding:3px 8px" title="Recorded in database but physical file is missing from server storage">⚠️ File Missing on Server</span>
          <?php elseif ($isOptional): ?>
            <span class="badge" style="font-size:0.75rem;padding:3px 8px;background:rgba(107,114,128,0.12);color:var(--text-muted);border:1px solid var(--border)">Optional</span>
          <?php else: ?>
            <span class="badge badge-danger" style="font-size:0.75rem;padding:3px 8px">Required</span>
          <?php endif; ?>
        </div>

        <?php if ($hasFile): ?>
          <div style="display:flex;align-items:center;gap:6px">
            <button type="button" class="btn-file-action btn-file-view" onclick="viewSystemFile('<?= e($filePath) ?>', '<?= e($fileName) ?>', '<?= e($downloadUrl) ?>')">
              <?= svgIcon('eye', 13) ?> View
            </button>
            <a href="<?= e($downloadUrl) ?>" class="btn-file-action btn-file-download" style="text-decoration:none">
              <?= svgIcon('download', 13) ?> Download
            </a>
            <?php if (!$isReadonly): ?>
              <label class="btn-file-action btn-file-replace" style="margin:0;cursor:pointer">
                <?= svgIcon('edit', 13) ?> Replace <input type="file" name="<?= e($docKey) ?>" style="display:none" onchange="this.form.submit()">
              </label>
              <button type="button" class="btn-file-action btn-file-delete" onclick="deleteKycDoc('<?= e($docKey) ?>')">
                <?= svgIcon('trash', 13) ?> Delete
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!$hasFile && !$isReadonly): ?>
        <div style="margin-top:8px">
          <input type="file" name="<?= e($docKey) ?>" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" onchange="updateKycSectionBadges()">
        </div>
      <?php elseif ($hasFile && !$fileExistsOnDisk && !$isReadonly): ?>
        <div style="margin-top:8px;font-size:0.82rem;color:#b45309;background:rgba(245,158,11,0.1);padding:8px 12px;border-radius:6px;border:1px dashed rgba(245,158,11,0.4);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <span>Physical file is missing from server storage. Please upload the file:</span>
          <label class="btn btn-warning btn-sm" style="margin:0;cursor:pointer;padding:3px 9px;font-size:0.78rem">
            Upload File <input type="file" name="<?= e($docKey) ?>" style="display:none" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" onchange="this.form.submit()">
          </label>
        </div>
      <?php endif; ?>

      <?php if ($extraHtml): ?>
        <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border)">
          <?= $extraHtml ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<style>
/* KYC Accordion & Layout Styles */
.kyc-accordion-card {
  margin-bottom: 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface-1);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.kyc-accordion-card:hover {
  border-color: var(--accent);
}
.kyc-accordion-card.kyc-card-expanded {
  box-shadow: var(--shadow-sm);
  border-color: var(--accent);
}
.kyc-accordion-header {
  padding: 13px 18px;
  background: var(--surface-2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  user-select: none;
  transition: background 0.15s ease;
  gap: 12px;
}
.kyc-accordion-header:hover {
  background: var(--accent-pale);
}
.kyc-accordion-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 0.98rem;
  color: var(--text-primary);
  flex: 1;
}
.kyc-chevron {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--surface-1);
  border: 1px solid var(--border);
  font-size: 0.7rem;
  color: var(--text-muted);
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), background 0.2s;
  flex-shrink: 0;
}
.kyc-accordion-body {
  padding: 20px 22px;
  border-top: 1px solid var(--border);
  display: none; /* Folded / collapsed by default */
  background: var(--surface-1);
}

/* Dynamic Section Status Badges */
.kyc-sec-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 9999px;
  letter-spacing: 0.02em;
  white-space: nowrap;
  transition: all 0.2s ease;
}
.kyc-badge-complete {
  background: rgba(16, 185, 129, 0.14);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.35);
}
.kyc-badge-progress {
  background: rgba(245, 158, 11, 0.14);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.35);
}
.kyc-badge-empty {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Grids & Input Sizing */
.kyc-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 14px;
}
.kyc-grid-3 {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 14px;
}
.kyc-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 14px;
}
@media (max-width: 960px) {
  .kyc-grid-3, .kyc-grid-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 640px) {
  .kyc-grid-2, .kyc-grid-3, .kyc-grid-4 {
    grid-template-columns: 1fr;
  }
}

.kyc-field-group {
  display: flex;
  flex-direction: column;
}
.kyc-field-group label {
  font-weight: 600;
  font-size: 0.84rem;
  color: var(--text-primary);
  margin-bottom: 5px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.kyc-field-group .form-control {
  height: 38px;
  font-size: 0.88rem;
  padding: 7px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  background: var(--surface-1);
  color: var(--text-primary);
}
.kyc-field-group textarea.form-control {
  height: auto;
  min-height: 56px;
}
.kyc-field-group .form-control:focus {
  border-color: var(--accent);
  outline: none;
  box-shadow: 0 0 0 3px rgba(15, 76, 129, 0.15);
}

/* Contact Cards */
.kyc-contact-box {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 14px 16px 8px;
  margin-bottom: 16px;
}
.kyc-contact-header {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--accent);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <?= $isReviewer ? 'Review KYC Application' : ($app ? 'Edit KYC Application' : 'New KYC Application') ?>
    </h1>
    <div class="page-subtitle">
      <?= e($app['registered_name'] ?? $app['partner_name'] ?? 'Select Partner or Contractor to begin') ?>
    </div>
  </div>
  <div class="page-header-actions">
    <?php if ($selectedPartnerId > 0 && ($kycType === 'Partner' || empty($kycType))): ?>
    <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= (int)$selectedPartnerId ?>&tab=kyc" class="btn btn-secondary">
      <?= svgIcon('building') ?> View Partner Details
    </a>
    <?php endif; ?>
    <?php if ($isAdminOrMgmt || $canApproveOrReject): ?>
    <a href="<?= APP_URL ?>/?page=kyc&action=admin_list&tab=<?= $status === 'Approved' ? 'approved' : 'ongoing' ?>" class="btn btn-secondary"><?= svgIcon('list') ?> All KYC Applications</a>
    <?php endif; ?>
  </div>
</div>

<!-- Rejection Reason Highlight Banner -->
<?php if ($status === 'Rejected' && !empty($app['rejection_reason'])): ?>
<div style="margin-bottom:20px;padding:16px 20px;background:#FEF2F2;border:1px solid #FCA5A5;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm)">
  <div style="display:flex;align-items:center;gap:10px;color:#991B1B;font-weight:700;font-size:1.02rem;margin-bottom:6px">
    <span>⚠️</span> KYC Application Rejected
  </div>
  <div style="font-size:0.9rem;color:#7F1D1D;line-height:1.5">
    <strong>Rejection Reason:</strong> <?= e($app['rejection_reason']) ?>
  </div>
  <?php if ($canCreateOrEdit): ?>
  <div style="margin-top:10px;font-size:0.82rem;color:#991B1B">
    Please update the missing or corrected information below, upload required files, and click <strong>[ Resubmit for Approval ]</strong>.
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Top KYC Accordion Controls & Live Progress Overview Bar -->
<div class="kyc-control-bar" style="margin-bottom:20px;padding:12px 18px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;box-shadow:var(--shadow-sm)">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:8px">
      <span style="font-size:0.84rem;color:var(--text-muted);font-weight:600">Completion Status:</span>
      <span id="kycOverallSummaryBadge" style="font-weight:700;font-size:0.9rem;color:var(--text-primary)">Evaluating...</span>
    </div>
    <div style="width:140px;height:8px;background:var(--surface-2);border-radius:999px;overflow:hidden;border:1px solid var(--border)">
      <div id="kycOverallProgressBar" style="width:0%;height:100%;background:var(--accent);transition:width 0.3s ease"></div>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <button type="button" class="btn btn-secondary btn-sm" onclick="expandAllKycSections()">
      <?= svgIcon('chevron-down', 13) ?> Expand All
    </button>
    <button type="button" class="btn btn-secondary btn-sm" onclick="collapseAllKycSections()">
      <?= svgIcon('chevron-up', 13) ?> Fold All
    </button>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/?page=kyc" enctype="multipart/form-data" id="kycOnePageForm">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="_action" id="kycActionInput" value="<?= $status === 'Rejected' ? 'resubmit' : 'submit' ?>">
  <input type="hidden" name="kyc_id" id="kycIdInput" value="<?= $appId ?>">
  <input type="hidden" name="partner_id" id="hiddenPartnerId" value="<?= (int)$selectedPartnerId ?>">
  <input type="hidden" name="kyc_type" id="hiddenKycType" value="<?= e($kycType) ?>">

  <!-- 1. KYC INFORMATION & ENTITY SELECTOR -->
  <div class="card kyc-accordion-card" id="kycSection1">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection1')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span><?= svgIcon('document', 16) ?> 1. KYC Information &amp; Type</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-1"></span>
        <span class="badge <?= $status === 'Approved' ? 'badge-success' : ($status === 'Rejected' ? 'badge-danger' : ($status === 'Pending Approval' || $status === 'Submitted' ? 'badge-warning' : 'badge-secondary')) ?>" style="font-size:0.8rem;padding:4px 10px">
          Status: <?= e($status) ?>
        </span>
      </div>
    </div>
    <div class="kyc-accordion-body">
      <div class="kyc-grid-2">
        <div class="kyc-field-group">
          <label>KYC Type <span class="text-danger">*</span></label>
          <select name="kyc_type" id="kycTypeSelect" class="form-control" style="font-weight:600" <?= ($isReadonly || $isPM || isPartnerUser() || isContractorUser()) ? 'disabled' : '' ?> onchange="onKycTypeChange()">
            <option value="Partner" <?= $kycType === 'Partner' ? 'selected' : '' ?>>Partner (ISP / Telco / Enterprise Client)</option>
            <option value="Contractor" <?= $kycType === 'Contractor' ? 'selected' : '' ?>>Contractor (Field Installation / Support Vendor)</option>
          </select>
          <?php if ($isReadonly || $isPM || isPartnerUser() || isContractorUser()): ?>
          <input type="hidden" name="kyc_type" id="hiddenKycType" value="<?= e($kycType) ?>">
          <?php endif; ?>
        </div>

        <div class="kyc-field-group">
          <label id="entitySelectLabel">
            <?= $kycType === 'Contractor' ? 'Contractor' : 'Partner' ?> <span class="text-danger">*</span>
          </label>
          <select name="partner_id" id="entityPartnerSelect" class="form-control" style="font-weight:600" <?= ($isReadonly || isPartnerUser() || isContractorUser()) ? 'disabled' : '' ?> onchange="onEntitySelectChange()">
            <option value="">-- Select <?= $kycType === 'Contractor' ? 'Contractor' : 'Partner' ?> --</option>
            <?php
            $currentList = ($kycType === 'Contractor') ? $contractorsList : $partnersList;
            foreach ($currentList as $ent):
              $stLabel = !empty($ent['kyc_status']) ? ' — KYC ' . $ent['kyc_status'] : ' — No KYC Application';
            ?>
            <option value="<?= $ent['id'] ?>" data-kyc-id="<?= (int)($ent['kyc_app_id'] ?? 0) ?>" data-kyc-status="<?= e($ent['kyc_status'] ?? '') ?>" <?= (int)$selectedPartnerId === (int)$ent['id'] ? 'selected' : '' ?>>
              <?= e($ent['name']) ?><?= e($stLabel) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php if ($isReadonly || isPartnerUser() || isContractorUser() || true): ?>
          <input type="hidden" name="partner_id" id="hiddenPartnerId" value="<?= (int)$selectedPartnerId ?>">
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. COMPANY DETAILS -->
  <div class="card kyc-accordion-card" id="kycSection2">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection2')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span><?= svgIcon('building', 16) ?> 2. COMPANY DETAILS</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-2"></span>
      </div>
    </div>
    <div class="kyc-accordion-body">
      <div class="kyc-grid-3">
        <div class="kyc-field-group">
          <label>Partner / Contractor Name <span class="text-danger">*</span></label>
          <input type="text" name="registered_name" id="registered_name" class="form-control" value="<?= e($app['registered_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div class="kyc-field-group">
          <label>City / Region</label>
          <input type="text" name="city_region" id="city_region" class="form-control" value="<?= e($app['city_region'] ?? 'Dar es Salaam') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div class="kyc-field-group">
          <label>Country</label>
          <input type="text" name="country" id="country" class="form-control" value="<?= e($app['country'] ?? 'Tanzania') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
      </div>

      <div class="kyc-field-group" style="margin-bottom:14px">
        <label>Physical Office Address <span class="text-danger">*</span></label>
        <textarea name="office_address" id="office_address" class="form-control" rows="2" required <?= $isReadonly ? 'readonly' : '' ?>><?= e($app['office_address'] ?? '') ?></textarea>
      </div>

      <!-- Business License File Upload & Actions (Only for Contractors) -->
      <div id="bizLicenseSection" style="<?= $kycType === 'Partner' ? 'display:none' : '' ?>">
        <?= renderComplianceDocUploadRow('business_license', 'Business License Document', $app['business_license'] ?? '', $appId, $isReadonly) ?>
      </div>

      <!-- Dynamic Custom Fields ([ + Add Field ]) -->
      <div style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <label style="font-weight:700;font-size:0.9rem;margin:0">Custom Company Fields</label>
          <?php if (!$isReadonly): ?>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addCustomFieldRow()">
            <?= svgIcon('plus', 13) ?> Add Field
          </button>
          <?php endif; ?>
        </div>

        <div id="customFieldsContainer" style="display:flex;flex-direction:column;gap:10px">
          <?php if (!empty($customFields)): ?>
            <?php foreach ($customFields as $idx => $cf): ?>
            <div class="custom-field-row kyc-grid-4" style="gap:10px;align-items:center;background:var(--surface-2);padding:8px 12px;border-radius:6px;margin-bottom:4px">
              <input type="text" name="custom_fields[<?= $idx ?>][name]" class="form-control" placeholder="Field Name" value="<?= e($cf['name'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
              <select name="custom_fields[<?= $idx ?>][type]" class="form-control" <?= $isReadonly ? 'disabled' : '' ?>>
                <option value="text" <?= ($cf['type'] ?? '') === 'text' ? 'selected' : '' ?>>Text</option>
                <option value="number" <?= ($cf['type'] ?? '') === 'number' ? 'selected' : '' ?>>Number</option>
                <option value="date" <?= ($cf['type'] ?? '') === 'date' ? 'selected' : '' ?>>Date</option>
              </select>
              <input type="text" name="custom_fields[<?= $idx ?>][value]" class="form-control" placeholder="Field Value" value="<?= e($cf['value'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
              <?php if (!$isReadonly): ?>
              <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.custom-field-row').remove()">&times;</button>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div id="emptyCustomFieldsMsg" style="font-size:0.84rem;color:var(--text-muted);font-style:italic">No custom fields added yet. Click [ + Add Field ] to add dynamic company fields.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. CONTACTS -->
  <div class="card kyc-accordion-card" id="kycSection3">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection3')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span><?= svgIcon('users', 16) ?> 3. CONTACTS</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-3"></span>
      </div>
    </div>
    <div class="kyc-accordion-body">
      <!-- SIGNATORY 1 -->
      <div class="kyc-contact-box">
        <div class="kyc-contact-header">
          <span>👤 Signatory 1 (Primary Authorized Signatory)</span>
        </div>
        <div class="kyc-grid-4">
          <div class="kyc-field-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="auth_signatory_name" id="auth_signatory_name" class="form-control" value="<?= e($app['auth_signatory_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Title / Designation</label>
            <input type="text" name="auth_signatory_title" id="auth_signatory_title" class="form-control" placeholder="e.g. Managing Director" value="<?= e($app['auth_signatory_title'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" name="auth_signatory_email" id="auth_signatory_email" class="form-control" value="<?= e($app['auth_signatory_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="auth_signatory_mobile" id="auth_signatory_mobile" class="form-control" value="<?= e($app['auth_signatory_mobile'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- SIGNATORY 2 -->
      <div class="kyc-contact-box">
        <div class="kyc-contact-header">
          <span>👤 Signatory 2 / Operations Contact</span>
        </div>
        <div class="kyc-grid-4">
          <div class="kyc-field-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="signatory2_name" id="signatory2_name" class="form-control" value="<?= e($app['signatory2_name'] ?? $app['ops_contact_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Title / Designation</label>
            <input type="text" name="signatory2_title" id="signatory2_title" class="form-control" placeholder="e.g. Operations Director" value="<?= e($app['signatory2_title'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" name="signatory2_email" id="signatory2_email" class="form-control" value="<?= e($app['signatory2_email'] ?? $app['ops_contact_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="signatory2_phone" id="signatory2_phone" class="form-control" value="<?= e($app['signatory2_phone'] ?? $app['ops_contact_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- TECHNICAL CONTACT -->
      <div class="kyc-contact-box">
        <div class="kyc-contact-header">
          <span>🔧 Technical Supervisor / Operations Lead</span>
        </div>
        <div class="kyc-grid-4">
          <div class="kyc-field-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="tech_supervisor_name" id="tech_supervisor_name" class="form-control" value="<?= e($app['tech_supervisor_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Title / Designation</label>
            <input type="text" name="tech_supervisor_title" id="tech_supervisor_title" class="form-control" placeholder="e.g. Lead Network Engineer" value="<?= e($app['tech_supervisor_title'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" name="tech_supervisor_email" id="tech_supervisor_email" class="form-control" value="<?= e($app['tech_supervisor_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="tech_supervisor_phone" id="tech_supervisor_phone" class="form-control" value="<?= e($app['tech_supervisor_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- BILLING CONTACT -->
      <div class="kyc-contact-box" style="margin-bottom:0">
        <div class="kyc-contact-header">
          <span>💳 Billing / Escalation Contact</span>
        </div>
        <div class="kyc-grid-4">
          <div class="kyc-field-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="billing_contact_name" id="billing_contact_name" class="form-control" value="<?= e($app['billing_contact_name'] ?? $app['escalation_contact_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Title / Designation</label>
            <input type="text" name="billing_contact_title" id="billing_contact_title" class="form-control" placeholder="e.g. Finance Manager" value="<?= e($app['billing_contact_title'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" name="billing_contact_email" id="billing_contact_email" class="form-control" value="<?= e($app['billing_contact_email'] ?? $app['escalation_contact_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div class="kyc-field-group">
            <label>Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="billing_contact_phone" id="billing_contact_phone" class="form-control" value="<?= e($app['billing_contact_phone'] ?? $app['escalation_contact_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. OTHER DOCUMENTS & COMPLIANCE -->
  <div class="card kyc-accordion-card" id="kycSection4">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection4')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span id="docsCardTitle">
          <?= $kycType === 'Contractor'
            ? '4. OTHER DOCUMENTS &amp; COMPLIANCE (7 MANDATORY UPLOADS)'
            : '4. OTHER DOCUMENTS &amp; COMPLIANCE (3 MANDATORY UPLOADS FOR PARTNER, 3 OPTIONAL: TIN, VRN &amp; TCRA)' ?>
        </span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-4"></span>
      </div>
    </div>
    <div class="kyc-accordion-body">

      <!-- ═══════ PARTNER DOCUMENTS ═══════════════════════════════════ -->
      <div id="partnerDocsSection" style="<?= $kycType === 'Contractor' ? 'display:none' : '' ?>">
        <?= renderComplianceDocUploadRow('partner_tin_certificate',    '1. TIN Certificate',              $app['partner_tin_certificate']   ?? '', $appId, $isReadonly, '', true) ?>
        <?= renderComplianceDocUploadRow('partner_vrn_certificate',    '2. VRN Certificate',              $app['partner_vrn_certificate']   ?? '', $appId, $isReadonly, '', true) ?>
        <?= renderComplianceDocUploadRow('partner_incorporation_cert', '3. Certificate of Incorporation', $app['partner_incorporation_cert'] ?? '', $appId, $isReadonly) ?>
        <?= renderComplianceDocUploadRow('partner_tcra_certificate',   '4. TCRA Certificate',            $app['partner_tcra_certificate']  ?? '', $appId, $isReadonly, '', true) ?>
        <?= renderComplianceDocUploadRow('partner_tax_clearance',      '5. Tax Clearance Certificate',   $app['partner_tax_clearance']     ?? '', $appId, $isReadonly) ?>
        <?= renderComplianceDocUploadRow('partner_business_licence',   '6. Business Licence Certificate',$app['partner_business_licence']  ?? '', $appId, $isReadonly) ?>
      </div>

      <!-- ═══════ CONTRACTOR DOCUMENTS ════════════════════════════════ -->
      <div id="contractorDocsSection" style="<?= $kycType === 'Partner' ? 'display:none' : '' ?>">

        <?= renderComplianceDocUploadRow('contractor_agreement', '1. Contractor Agreement', $app['contractor_agreement'] ?? '', $appId, $isReadonly) ?>

        <?php
        $slaExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="sla_accepted" value="1" ' . (!empty($app['sla_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . ' onchange="updateKycSectionBadges()"> <span><strong>Confirm SLA Policy Acceptance</strong> — Agrees to Neilos Open-Access Infrastructure Service Level Agreement</span></label>';
        echo renderComplianceDocUploadRow('signed_sla_file', '2. SLA Acceptance / Signed SLA', $app['signed_sla_file'] ?? '', $appId, $isReadonly, $slaExtra);
        ?>

        <?php
        $esgExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="safety_esg_accepted" value="1" ' . (!empty($app['safety_esg_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . ' onchange="updateKycSectionBadges()"> <span><strong>Confirm Safety / ESG Policy Acceptance</strong> — Agrees to Environmental, Social &amp; Safety Governance policies</span></label>';
        echo renderComplianceDocUploadRow('safety_esg_file', '3. Safety / ESG Policy Acceptance', $app['safety_esg_file'] ?? '', $appId, $isReadonly, $esgExtra);
        ?>

        <?php
        $ndaExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="confidentiality_accepted" value="1" ' . (!empty($app['confidentiality_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . ' onchange="updateKycSectionBadges()"> <span><strong>Confirm Confidentiality Clause Acceptance</strong> — Agrees to Non-Disclosure &amp; Commercial Confidentiality clauses</span></label>';
        echo renderComplianceDocUploadRow('confidentiality_nda_file', '4. Confidentiality Clause / NDA Acceptance', $app['confidentiality_nda_file'] ?? '', $appId, $isReadonly, $ndaExtra);
        ?>

        <?php
        ob_start();
        ?>
        <div style="margin-top:6px">
          <label style="font-weight:700;font-size:0.88rem;margin-bottom:6px;display:block">Select Authorized Service Regions <span class="text-danger">*</span></label>
          <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:10px;background:var(--surface-2);padding:10px;border-radius:6px">
            <?php foreach ($availableRegions as $reg): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer">
              <input type="checkbox" name="service_regions[]" value="<?= e($reg) ?>" <?= in_array($reg, $selectedRegions) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> onchange="updateKycSectionBadges()">
              <span><?= e($reg) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php
        $regExtra = ob_get_clean();
        echo renderComplianceDocUploadRow('service_regions_file', '5. Service Regions Supporting Document', $app['service_regions_file'] ?? '', $appId, $isReadonly, $regExtra);
        ?>

        <?= renderComplianceDocUploadRow('hse_certificate', '6. HSE Certificate', $app['hse_certificate'] ?? '', $appId, $isReadonly) ?>

        <?= renderComplianceDocUploadRow('trca_certificate', '7. TRCA Installation License', $app['trca_certificate'] ?? '', $appId, $isReadonly) ?>

      </div><!-- /contractorDocsSection -->

    </div>
  </div>

  <!-- 5. BANK DETAILS -->
  <div class="card kyc-accordion-card" id="kycSection5">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection5')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span><?= svgIcon('bank', 16) ?> 5. BANK DETAILS</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-5"></span>
      </div>
    </div>
    <div class="kyc-accordion-body">
      <div class="kyc-grid-3">
        <div class="kyc-field-group">
          <label>Bank Name <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span></label>
          <input type="text" name="bank_name" id="bank_name" class="form-control" value="<?= e($app['bank_name'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div class="kyc-field-group">
          <label>Branch <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span></label>
          <input type="text" name="bank_branch" id="bank_branch" class="form-control" value="<?= e($app['bank_branch'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div class="kyc-field-group">
          <label>Payment Terms <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span></label>
          <select name="bank_payment_terms" id="bank_payment_terms" class="form-control" <?= $isReadonly ? 'disabled' : '' ?>>
            <?php foreach ($paymentTermsOptions as $pt): ?>
            <option value="<?= e($pt) ?>" <?= ($app['bank_payment_terms'] ?? '30 Days') === $pt ? 'selected' : '' ?>><?= e($pt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="kyc-grid-2">
        <div class="kyc-field-group">
          <label>Account Name <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span></label>
          <input type="text" name="bank_account_name" id="bank_account_name" class="form-control" value="<?= e($app['bank_account_name'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div class="kyc-field-group">
          <label>Account Number <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span></label>
          <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" value="<?= e($app['bank_account_number'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
      </div>
    </div>
  </div>

  <!-- 6. CAPABILITIES (Contractor only) -->
  <div class="card kyc-accordion-card" id="kycSection6" style="<?= $kycType === 'Partner' ? 'display:none' : '' ?>">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection6')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span><?= svgIcon('check-circle', 16) ?> 6. CAPABILITIES</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span id="badge-section-6"></span>
      </div>
    </div>
    <div class="kyc-accordion-body" id="capabilitiesCard">
      <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:14px">
        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_ftth_install" value="1" <?= !empty($app['cap_ftth_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>FTTH Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_sme_install" value="1" <?= !empty($app['cap_sme_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>SME Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_enterprise_install" value="1" <?= !empty($app['cap_enterprise_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>Enterprise Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_site_survey" value="1" <?= !empty($app['cap_site_survey']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>Site Survey</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_maintenance" value="1" <?= !empty($app['cap_maintenance']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>Maintenance Support Visit</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_remote_support" value="1" <?= !empty($app['cap_remote_support']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px" onchange="updateKycSectionBadges()">
          <span>Remote Laptop Support</span>
        </label>
      </div>
    </div>
  </div>

  <!-- 7. AUDIT TRAIL / HISTORY -->
  <?php if (!empty($history)): ?>
  <div class="card kyc-accordion-card" id="kycSection7">
    <div class="kyc-accordion-header" onclick="toggleKycSection('kycSection7')">
      <div class="kyc-accordion-title">
        <span class="kyc-chevron">▶</span>
        <span>📜 7. KYC HISTORY &amp; AUDIT TRAIL</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="badge badge-secondary" style="font-size:0.8rem;padding:4px 10px"><?= count($history) ?> Events Recorded</span>
      </div>
    </div>
    <div class="kyc-accordion-body" style="padding:0">
      <div class="table-responsive">
        <table class="data-table" style="font-size:0.85rem">
          <thead>
            <tr>
              <th>Date / Time</th>
              <th>Action / Event</th>
              <th>Actor</th>
              <th>Role</th>
              <th>Status Transition</th>
              <th>Details / Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td class="font-mono text-muted"><?= fmtDateTime($h['created_at']) ?></td>
              <td class="font-600"><?= e($h['action_title']) ?></td>
              <td><?= e($h['actor_name'] ?: 'System') ?></td>
              <td><span class="badge badge-secondary"><?= e($h['action_role']) ?></span></td>
              <td>
                <span class="text-muted"><?= e($h['from_status'] ?: 'Initial') ?></span> &rarr; 
                <span class="badge <?= $h['to_status'] === 'Approved' ? 'badge-success' : ($h['to_status'] === 'Rejected' ? 'badge-danger' : 'badge-warning') ?>">
                  <?= e($h['to_status']) ?>
                </span>
              </td>
              <td style="max-width:280px;white-space:normal"><?= e($h['details'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- BOTTOM ACTION FOOTER -->
  <div class="card" style="margin-bottom:40px;padding:16px 20px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <span style="font-weight:700;color:var(--text-primary)">Current Status:</span>
      <span class="badge <?= $status === 'Approved' ? 'badge-success' : ($status === 'Rejected' ? 'badge-danger' : ($status === 'Pending Approval' || $status === 'Submitted' ? 'badge-warning' : 'badge-secondary')) ?>" style="margin-left:6px;padding:6px 12px">
        <?= e($status) ?>
      </span>
    </div>

    <div style="display:flex;align-items:center;gap:12px">
      <?php if ($canCreateOrEdit && $status !== 'Approved' && !in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
        <button type="button" onclick="kycFormSubmit('save_draft')" class="btn btn-secondary">
          <?= svgIcon('edit') ?> Save Draft
        </button>

        <?php if ($status === 'Rejected'): ?>
        <button type="button" onclick="kycFormSubmit('resubmit')" class="btn btn-success" style="color:#ffffff !important">
          <?= svgIcon('check') ?> Resubmit for Approval
        </button>
        <?php else: ?>
        <button type="button" onclick="kycFormSubmit('submit')" class="btn btn-primary" style="color:#ffffff !important">
          <?= svgIcon('check') ?> Submit for Approval
        </button>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($canApproveOrReject && in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
        <button type="button" class="btn btn-danger" onclick="openRejectKycModal()" style="color:#ffffff !important">
          <?= svgIcon('x') ?> Reject Application
        </button>
        <button type="button" class="btn btn-success" onclick="openApproveKycModal()" style="color:#ffffff !important">
          <?= svgIcon('check') ?> Approve KYC
        </button>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- Delete Document Form -->
<form id="deleteDocForm" method="POST" action="<?= APP_URL ?>/?page=kyc&action=delete_doc" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="kyc_id" value="<?= $appId ?>">
  <input type="hidden" name="doc_key" id="deleteDocKeyInput">
</form>

<!-- Custom Approval Modal (Rendered when reviewing pending applications) -->
<?php if ($canApproveOrReject && in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
<div class="modal-backdrop" id="kycApproveModal">
  <div class="modal" style="max-width:520px">
    <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=approve">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" value="<?= $appId ?>">
      
      <div class="modal-header" style="background:#ECFDF5;border-bottom:1px solid #A7F3D0;padding:14px 20px">
        <div class="modal-title" style="color:#065F46;font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <?= svgIcon('check', 18) ?> Approve KYC Application
        </div>
        <button type="button" class="modal-close" onclick="closeApproveKycModal()">&times;</button>
      </div>
      
      <div class="modal-body" style="padding:20px">
        <div style="margin-bottom:16px;padding:12px 14px;background:var(--surface-2);border-radius:6px;font-size:0.9rem;display:flex;flex-direction:column;gap:6px">
          <div><strong style="color:var(--text-muted)">Organization:</strong> <span style="font-weight:700;color:var(--text-primary)"><?= e($app['registered_name'] ?: ($app['partner_name'] ?? 'N/A')) ?></span></div>
          <div><strong style="color:var(--text-muted)">KYC Type:</strong> <span class="badge badge-info"><?= e($kycType) ?></span></div>
        </div>

        <div class="form-group mb-16">
          <label class="form-label" style="font-weight:700;color:var(--text-primary);margin-bottom:6px;display:block">
            Approval Remarks / Notes <span class="text-muted" style="font-weight:400;font-size:0.8rem">(Optional)</span>
          </label>
          <textarea name="remarks" class="form-control" rows="3" placeholder="Enter optional approval notes or compliance remarks..."></textarea>
        </div>
        <div style="font-size:0.82rem;color:var(--text-secondary);background:rgba(16,185,129,0.08);padding:10px 12px;border-radius:6px;border:1px solid rgba(16,185,129,0.2)">
          Approving this KYC will verify compliance documents, set status to <strong>Approved</strong>, and activate the partner in the system.
        </div>
      </div>

      <div class="modal-footer" style="padding:14px 20px;background:var(--surface-2);display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeApproveKycModal()">Cancel</button>
        <button type="submit" class="btn btn-success" style="color:#ffffff !important">
          <?= svgIcon('check') ?> Confirm Approval
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Custom Rejection Modal (Rendered when reviewing pending applications) -->
<div class="modal-backdrop" id="kycRejectModal">
  <div class="modal" style="max-width:520px">
    <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=reject">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" value="<?= $appId ?>">
      
      <div class="modal-header" style="background:#FEF2F2;border-bottom:1px solid #FCA5A5;padding:14px 20px">
        <div class="modal-title" style="color:#991B1B;font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <?= svgIcon('x', 18) ?> Reject KYC Application
        </div>
        <button type="button" class="modal-close" onclick="closeRejectKycModal()">&times;</button>
      </div>
      
      <div class="modal-body" style="padding:20px">
        <div style="margin-bottom:16px;padding:12px 14px;background:var(--surface-2);border-radius:6px;font-size:0.9rem;display:flex;flex-direction:column;gap:6px">
          <div><strong style="color:var(--text-muted)">Application:</strong> <span style="font-weight:700;color:var(--text-primary)"><?= e($app['registered_name'] ?: ($app['partner_name'] ?? 'N/A')) ?></span></div>
          <div><strong style="color:var(--text-muted)">KYC Type:</strong> <span class="badge badge-info"><?= e($kycType) ?></span></div>
        </div>

        <label style="font-weight:700;color:var(--text-primary);margin-bottom:6px;display:block">
          Reason for Rejection <span class="text-danger">*</span>
        </label>
        <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Enter the reason why this KYC application is being rejected..." required></textarea>
      </div>

      <div class="modal-footer" style="padding:14px 20px;background:var(--surface-2);display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeRejectKycModal()">Cancel</button>
        <button type="submit" class="btn btn-danger" style="color:#ffffff !important">Reject KYC Application</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// ============================================================
// Accordion Folding & Toggling Logic
// ============================================================
function toggleKycSection(secId) {
  const card = document.getElementById(secId);
  if (!card) return;
  const body = card.querySelector('.kyc-accordion-body');
  const chevron = card.querySelector('.kyc-chevron');
  if (!body) return;

  const isCurrentlyOpen = (body.style.display === 'block');
  if (isCurrentlyOpen) {
    body.style.display = 'none';
    card.classList.remove('kyc-card-expanded');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  } else {
    body.style.display = 'block';
    card.classList.add('kyc-card-expanded');
    if (chevron) chevron.style.transform = 'rotate(90deg)';
  }
}

function expandAllKycSections() {
  document.querySelectorAll('.kyc-accordion-card').forEach(card => {
    // Only expand visible sections
    if (card.style.display !== 'none') {
      const body = card.querySelector('.kyc-accordion-body');
      const chevron = card.querySelector('.kyc-chevron');
      if (body) body.style.display = 'block';
      card.classList.add('kyc-card-expanded');
      if (chevron) chevron.style.transform = 'rotate(90deg)';
    }
  });
}

function collapseAllKycSections() {
  document.querySelectorAll('.kyc-accordion-card').forEach(card => {
    const body = card.querySelector('.kyc-accordion-body');
    const chevron = card.querySelector('.kyc-chevron');
    if (body) body.style.display = 'none';
    card.classList.remove('kyc-card-expanded');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  });
}

// ============================================================
// Real-time Section Completion Evaluator & Badges
// ============================================================
function getBadgeHtml(filled, total) {
  if (total <= 0) return '';
  if (filled >= total) {
    return `<span class="kyc-sec-badge kyc-badge-complete"><span style="font-size:0.85rem">✓</span> <span>Completed</span> <span style="opacity:0.85">(${filled}/${total})</span></span>`;
  } else if (filled > 0) {
    return `<span class="kyc-sec-badge kyc-badge-progress"><span style="font-size:0.85rem">⏳</span> <span>In Progress</span> <span style="opacity:0.85">(${filled}/${total})</span></span>`;
  } else {
    return `<span class="kyc-sec-badge kyc-badge-empty"><span style="font-size:0.85rem">✕</span> <span>Incomplete</span> <span style="opacity:0.85">(0/${total})</span></span>`;
  }
}

function updateKycSectionBadges() {
  const kycTypeSelect = document.getElementById('kycTypeSelect');
  const hiddenKycType = document.getElementById('hiddenKycType');
  const kycType = (kycTypeSelect ? kycTypeSelect.value : (hiddenKycType ? hiddenKycType.value : 'Partner'));
  const isContractor = (kycType === 'Contractor');

  const isFilled = (id) => {
    const el = document.getElementById(id);
    if (!el) return false;
    if (el.type === 'checkbox') return el.checked;
    return String(el.value || '').trim().length > 0;
  };

  let completedSectionsCount = 0;
  let totalSectionsEvaluated = 5 + (isContractor ? 1 : 0);

  // 1. KYC Information & Type (2 required: type & entity)
  let s1Total = 2;
  let s1Filled = 0;
  if (isFilled('kycTypeSelect') || isFilled('hiddenKycType')) s1Filled++;
  if (isFilled('entityPartnerSelect') || isFilled('hiddenPartnerId')) s1Filled++;
  const b1 = document.getElementById('badge-section-1');
  if (b1) b1.innerHTML = getBadgeHtml(s1Filled, s1Total);
  if (s1Filled >= s1Total) completedSectionsCount++;

  // 2. Company Details (2 required: Name, Address; +1 Biz License for Contractor)
  const compFields = ['registered_name', 'office_address'];
  let s2Total = compFields.length + (isContractor ? 1 : 0);
  let s2Filled = 0;
  compFields.forEach(f => { if (isFilled(f)) s2Filled++; });
  if (isContractor) {
    const bizRow = document.querySelector('[data-doc-key="business_license"]');
    if (bizRow) {
      const hasFile = bizRow.getAttribute('data-has-file') === '1';
      const fileInput = bizRow.querySelector('input[type="file"]');
      const hasSelected = fileInput && fileInput.files && fileInput.files.length > 0;
      if (hasFile || hasSelected) s2Filled++;
    }
  }
  const b2 = document.getElementById('badge-section-2');
  if (b2) b2.innerHTML = getBadgeHtml(s2Filled, s2Total);
  if (s2Filled >= s2Total) completedSectionsCount++;

  // 3. Contacts (4 contacts x 3 required fields = 12 required fields)
  const contactFields = [
    'auth_signatory_name', 'auth_signatory_email', 'auth_signatory_mobile',
    'signatory2_name', 'signatory2_email', 'signatory2_phone',
    'tech_supervisor_name', 'tech_supervisor_email', 'tech_supervisor_phone',
    'billing_contact_name', 'billing_contact_email', 'billing_contact_phone'
  ];
  let s3Total = contactFields.length;
  let s3Filled = 0;
  contactFields.forEach(f => { if (isFilled(f)) s3Filled++; });
  const b3 = document.getElementById('badge-section-3');
  if (b3) b3.innerHTML = getBadgeHtml(s3Filled, s3Total);
  if (s3Filled >= s3Total) completedSectionsCount++;

  // 4. Other Documents & Compliance
  let s4Total = 0;
  let s4Filled = 0;
  if (!isContractor) {
    // Partner: 3 mandatory uploads (TIN, VRN, and TCRA Certificate are optional)
    const partnerMandatoryDocKeys = [
      'partner_incorporation_cert', 'partner_tax_clearance', 'partner_business_licence'
    ];
    s4Total = partnerMandatoryDocKeys.length;
    partnerMandatoryDocKeys.forEach(k => {
      const row = document.querySelector(`[data-doc-key="${k}"]`);
      if (row) {
        const hasFile = row.getAttribute('data-has-file') === '1';
        const fileInput = row.querySelector('input[type="file"]');
        const hasSelected = fileInput && fileInput.files && fileInput.files.length > 0;
        if (hasFile || hasSelected) s4Filled++;
      }
    });
  } else {
    // Contractor: 7 mandatory items
    s4Total = 7;
    // Agreement
    const caRow = document.querySelector('[data-doc-key="contractor_agreement"]');
    if (caRow && (caRow.getAttribute('data-has-file') === '1' || caRow.querySelector('input[type="file"]')?.files?.length > 0)) s4Filled++;
    
    // SLA (file or checkbox)
    const slaRow = document.querySelector('[data-doc-key="signed_sla_file"]');
    const slaChecked = document.querySelector('input[name="sla_accepted"]')?.checked;
    if ((slaRow && (slaRow.getAttribute('data-has-file') === '1' || slaRow.querySelector('input[type="file"]')?.files?.length > 0)) || slaChecked) s4Filled++;

    // ESG (file or checkbox)
    const esgRow = document.querySelector('[data-doc-key="safety_esg_file"]');
    const esgChecked = document.querySelector('input[name="safety_esg_accepted"]')?.checked;
    if ((esgRow && (esgRow.getAttribute('data-has-file') === '1' || esgRow.querySelector('input[type="file"]')?.files?.length > 0)) || esgChecked) s4Filled++;

    // NDA (file or checkbox)
    const ndaRow = document.querySelector('[data-doc-key="confidentiality_nda_file"]');
    const ndaChecked = document.querySelector('input[name="confidentiality_accepted"]')?.checked;
    if ((ndaRow && (ndaRow.getAttribute('data-has-file') === '1' || ndaRow.querySelector('input[type="file"]')?.files?.length > 0)) || ndaChecked) s4Filled++;

    // Service Regions (file or at least 1 region checked)
    const regRow = document.querySelector('[data-doc-key="service_regions_file"]');
    const regChecked = document.querySelectorAll('input[name="service_regions[]"]:checked').length > 0;
    if ((regRow && (regRow.getAttribute('data-has-file') === '1' || regRow.querySelector('input[type="file"]')?.files?.length > 0)) || regChecked) s4Filled++;

    // HSE
    const hseRow = document.querySelector('[data-doc-key="hse_certificate"]');
    if (hseRow && (hseRow.getAttribute('data-has-file') === '1' || hseRow.querySelector('input[type="file"]')?.files?.length > 0)) s4Filled++;

    // TRCA
    const trcaRow = document.querySelector('[data-doc-key="trca_certificate"]');
    if (trcaRow && (trcaRow.getAttribute('data-has-file') === '1' || trcaRow.querySelector('input[type="file"]')?.files?.length > 0)) s4Filled++;
  }
  const b4 = document.getElementById('badge-section-4');
  if (b4) b4.innerHTML = getBadgeHtml(s4Filled, s4Total);
  if (s4Filled >= s4Total) completedSectionsCount++;

  // 5. Bank Details (Optional)
  const bankFields = ['bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number'];
  let s5Filled = 0;
  bankFields.forEach(f => { if (isFilled(f)) s5Filled++; });
  const b5 = document.getElementById('badge-section-5');
  if (b5) {
    if (s5Filled > 0) {
      b5.innerHTML = `<span class="kyc-sec-badge kyc-badge-complete"><span style="font-size:0.85rem">✓</span> <span>Provided</span> <span style="opacity:0.85">(${s5Filled}/4)</span></span>`;
    } else {
      b5.innerHTML = `<span class="kyc-sec-badge" style="background:rgba(107,114,128,0.12);color:var(--text-muted);border:1px solid var(--border)"><span>Optional</span></span>`;
    }
  }
  // Optional section counts as completed towards progress
  completedSectionsCount++;

  // 6. Capabilities (Contractor only)
  if (isContractor) {
    const capChecked = document.querySelectorAll('#capabilitiesCard input[type="checkbox"]:checked').length;
    const b6 = document.getElementById('badge-section-6');
    if (b6) {
      if (capChecked > 0) {
        b6.innerHTML = `<span class="kyc-sec-badge kyc-badge-complete"><span style="font-size:0.85rem">✓</span> <span>Selected</span> <span style="opacity:0.85">(${capChecked})</span></span>`;
        completedSectionsCount++;
      } else {
        b6.innerHTML = `<span class="kyc-sec-badge kyc-badge-empty"><span style="font-size:0.85rem">✕</span> <span>None Selected</span></span>`;
      }
    }
  }

  // Update Overall Summary Bar
  const overallSummaryBadge = document.getElementById('kycOverallSummaryBadge');
  const overallProgressBar = document.getElementById('kycOverallProgressBar');
  const percent = Math.round((completedSectionsCount / totalSectionsEvaluated) * 100);

  if (overallSummaryBadge) {
    if (completedSectionsCount === totalSectionsEvaluated) {
      overallSummaryBadge.innerHTML = `<span style="color:#059669">✓ All Sections Completed (${completedSectionsCount}/${totalSectionsEvaluated})</span>`;
    } else {
      overallSummaryBadge.innerHTML = `<span style="color:var(--accent)">${completedSectionsCount} of ${totalSectionsEvaluated} Sections Completed (${percent}%)</span>`;
    }
  }
  if (overallProgressBar) {
    overallProgressBar.style.width = percent + '%';
    overallProgressBar.style.background = (percent === 100 ? '#10b981' : 'var(--accent)');
  }
}

// Attach live input & change listeners safely
function initKycFormEvaluator() {
  updateKycSectionBadges();
  const form = document.getElementById('kycOnePageForm');
  if (form) {
    form.addEventListener('input', updateKycSectionBadges);
    form.addEventListener('change', updateKycSectionBadges);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initKycFormEvaluator);
} else {
  initKycFormEvaluator();
}

// ============================================================
// Actions & Handlers
// ============================================================
function deleteKycDoc(docKey) {
  neilosConfirm('Are you sure you want to delete this document?', 'Delete KYC Document', 'Delete', 'btn-danger').then(confirmed => {
    if (confirmed) {
      document.getElementById('deleteDocKeyInput').value = docKey;
      document.getElementById('deleteDocForm').submit();
    }
  });
}

function kycFormSubmit(action) {
  const actionInput = document.getElementById('kycActionInput');
  if (actionInput) actionInput.value = action;
  const form = document.getElementById('kycOnePageForm');
  if (!form) return;

  if (action === 'save_draft') {
    // Save draft allows saving partial entries without required field blocking
    const requiredEls = form.querySelectorAll('[required]');
    requiredEls.forEach(el => el.removeAttribute('required'));
    form.submit();
    return;
  }

  // When submitting or resubmitting for approval, ensure required fields are filled
  if (action === 'submit' || action === 'resubmit') {
    const requiredInputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let firstInvalid = null;

    for (const el of requiredInputs) {
      // Check if element is inside a visible section
      const card = el.closest('.kyc-accordion-card');
      if (card && card.style.display === 'none') continue;
      if (el.disabled) continue;

      if (!el.value || !String(el.value).trim()) {
        firstInvalid = el;
        // Automatically unfold this section so the user sees the missing field
        if (card) {
          const body = card.querySelector('.kyc-accordion-body');
          const chevron = card.querySelector('.kyc-chevron');
          if (body) body.style.display = 'block';
          card.classList.add('kyc-card-expanded');
          if (chevron) chevron.style.transform = 'rotate(90deg)';
        }
        break;
      }
    }

    if (firstInvalid) {
      firstInvalid.focus();
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (typeof neilosToast === 'function') {
        neilosToast('Please fill in all required fields before submitting for approval.', 'warning');
      } else {
        alert('Please fill in all required fields before submitting for approval.');
      }
      return;
    }
  }

  form.submit();
}

const partnerEntities = <?= json_encode(array_map(function($p) {
  return [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'kyc_status' => $p['kyc_status'] ?? '',
    'kyc_app_id' => (int)($p['kyc_app_id'] ?? 0),
    'label' => $p['name'] . (!empty($p['kyc_status']) ? ' — KYC ' . $p['kyc_status'] : ' — No KYC Application')
  ];
}, $partnersList)) ?>;

const contractorEntities = <?= json_encode(array_map(function($c) {
  return [
    'id' => (int)$c['id'],
    'name' => $c['name'],
    'kyc_status' => $c['kyc_status'] ?? '',
    'kyc_app_id' => (int)($c['kyc_app_id'] ?? 0),
    'label' => $c['name'] . (!empty($c['kyc_status']) ? ' — KYC ' . $c['kyc_status'] : ' — No KYC Application')
  ];
}, $contractorsList)) ?>;

function onKycTypeChange() {
  const typeSelect = document.getElementById('kycTypeSelect');
  const type = typeSelect ? typeSelect.value : 'Partner';
  const label = document.getElementById('entitySelectLabel');
  const hiddenType = document.getElementById('hiddenKycType');
  const partnerDocs = document.getElementById('partnerDocsSection');
  const contractorDocs = document.getElementById('contractorDocsSection');
  const docsTitle = document.getElementById('docsCardTitle');
  const capCard = document.getElementById('kycSection6');
  const bizLicense = document.getElementById('bizLicenseSection');
  const select = document.getElementById('entityPartnerSelect');

  if (label) label.textContent = (type === 'Contractor' ? 'Contractor *' : 'Partner *');
  if (hiddenType) hiddenType.value = type;

  // Toggle capabilities card for Partner vs Contractor
  if (capCard) capCard.style.display = (type === 'Contractor' ? '' : 'none');

  // Toggle business license in company info (for contractor)
  if (bizLicense) bizLicense.style.display = (type === 'Contractor' ? '' : 'none');

  // Toggle document upload sections
  if (partnerDocs) partnerDocs.style.display = (type === 'Contractor' ? 'none' : '');
  if (contractorDocs) contractorDocs.style.display = (type === 'Partner' ? 'none' : '');
  if (docsTitle) docsTitle.textContent = (type === 'Contractor'
    ? '4. OTHER DOCUMENTS & COMPLIANCE (7 MANDATORY UPLOADS)'
    : '4. OTHER DOCUMENTS & COMPLIANCE (3 MANDATORY UPLOADS FOR PARTNER, 3 OPTIONAL: TIN, VRN & TCRA)');

  // Dynamically update entity dropdown to show ONLY the respective partners or contractors
  if (select) {
    const prevVal = select.value;
    const list = (type === 'Contractor' ? contractorEntities : partnerEntities);
    select.innerHTML = '';

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = '-- Select ' + (type === 'Contractor' ? 'Contractor' : 'Partner') + ' --';
    select.appendChild(defaultOpt);

    let matchFound = false;
    list.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.label;
      opt.setAttribute('data-kyc-id', item.kyc_app_id);
      opt.setAttribute('data-kyc-status', item.kyc_status);
      if (String(item.id) === String(prevVal)) {
        opt.selected = true;
        matchFound = true;
      }
      select.appendChild(opt);
    });

    if (!matchFound) {
      select.value = '';
      const hiddenPartnerId = document.getElementById('hiddenPartnerId');
      if (hiddenPartnerId) hiddenPartnerId.value = '';
    }
  }

  updateKycSectionBadges();
}

function onEntitySelectChange() {
  const select = document.getElementById('entityPartnerSelect');
  const partnerId = select.value;
  const hiddenId = document.getElementById('hiddenPartnerId');
  if (hiddenId) hiddenId.value = partnerId || '';
  if (!partnerId) return;

  const opt = select.options[select.selectedIndex];
  const kycId = opt ? opt.getAttribute('data-kyc-id') : null;
  const isNewMode = <?= json_encode($action === 'new') ?>;

  if (isNewMode && kycId && parseInt(kycId) > 0) {
    const status = opt.getAttribute('data-kyc-status');
    const appUrl = (typeof APP_URL !== 'undefined' ? APP_URL : '');
    neilosConfirm(
      'A KYC application already exists for this organization (Status: ' + status + '). Would you like to view/edit the existing application?',
      'KYC Application Exists',
      'Open Application',
      'btn-primary'
    ).then(confirmed => {
      if (confirmed) {
        window.location.href = appUrl + '/?page=kyc&action=edit&id=' + kycId;
      }
    });
    return;
  }

  fetch((typeof APP_URL !== 'undefined' ? APP_URL : '') + '/?page=kyc&action=api_entity_data&partner_id=' + partnerId)
    .then(r => r.json())
    .then(res => {
      if (res && res.success && res.data) {
        const d = res.data;
        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el && val !== null && val !== undefined) el.value = val;
        };

        setVal('registered_name', d.registered_name || d.name);
        setVal('office_address', d.office_address || d.address);
        setVal('city_region', d.city_region || 'Dar es Salaam');
        setVal('country', d.country || 'Tanzania');

        setVal('auth_signatory_name', d.auth_signatory_name || d.main_contact_name);
        setVal('auth_signatory_title', d.auth_signatory_title || '');
        setVal('auth_signatory_email', d.auth_signatory_email || d.main_contact_email);
        setVal('auth_signatory_mobile', d.auth_signatory_mobile || d.main_contact_phone);

        setVal('signatory2_name', d.signatory2_name || d.ops_contact_name);
        setVal('signatory2_title', d.signatory2_title || '');
        setVal('signatory2_email', d.signatory2_email || d.ops_contact_email);
        setVal('signatory2_phone', d.signatory2_phone || d.ops_contact_phone);

        setVal('tech_supervisor_name', d.tech_supervisor_name);
        setVal('tech_supervisor_title', d.tech_supervisor_title || '');
        setVal('tech_supervisor_email', d.tech_supervisor_email);
        setVal('tech_supervisor_phone', d.tech_supervisor_phone);

        setVal('billing_contact_name', d.billing_contact_name || d.escalation_contact_name);
        setVal('billing_contact_title', d.billing_contact_title || '');
        setVal('billing_contact_email', d.billing_contact_email || d.escalation_contact_email);
        setVal('billing_contact_phone', d.billing_contact_phone || d.escalation_contact_phone);

        setVal('bank_name', d.bank_name);
        setVal('bank_branch', d.bank_branch);
        setVal('bank_account_name', d.bank_account_name);
        setVal('bank_account_number', d.bank_account_number);
        setVal('bank_payment_terms', d.bank_payment_terms || '30 Days');

        updateKycSectionBadges();
      }
    })
    .catch(() => {});
}

function addCustomFieldRow() {
  const container = document.getElementById('customFieldsContainer');
  const emptyMsg = document.getElementById('emptyCustomFieldsMsg');
  if (emptyMsg) emptyMsg.style.display = 'none';

  const idx = container.querySelectorAll('.custom-field-row').length;
  const row = document.createElement('div');
  row.className = 'custom-field-row kyc-grid-4';
  row.style.cssText = 'gap:10px;align-items:center;background:var(--surface-2);padding:8px 12px;border-radius:6px;margin-bottom:8px';
  row.innerHTML = `
    <input type="text" name="custom_fields[${idx}][name]" class="form-control" placeholder="Field Name (e.g. Employee Count)" required>
    <select name="custom_fields[${idx}][type]" class="form-control">
      <option value="text">Text</option>
      <option value="number">Number</option>
      <option value="date">Date</option>
    </select>
    <input type="text" name="custom_fields[${idx}][value]" class="form-control" placeholder="Field Value">
    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.custom-field-row').remove()">&times;</button>
  `;
  container.appendChild(row);
}

function openApproveKycModal() {
  const modal = document.getElementById('kycApproveModal');
  if (modal) modal.classList.add('open');
}

function closeApproveKycModal() {
  const modal = document.getElementById('kycApproveModal');
  if (modal) modal.classList.remove('open');
}

function openRejectKycModal() {
  const modal = document.getElementById('kycRejectModal');
  if (modal) modal.classList.add('open');
}

function closeRejectKycModal() {
  const modal = document.getElementById('kycRejectModal');
  if (modal) modal.classList.remove('open');
}
</script>
