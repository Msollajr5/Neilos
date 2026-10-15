<?php
// ============================================================
// Neilos Partner Portal — Partner Detail & KYC View
// ============================================================
$kycStatus = $kycApp['status'] ?? null;
$kycBadgeClass = match($kycStatus) {
    'Approved' => 'badge-success',
    'Submitted for Approval', 'Pending Approval', 'Submitted' => 'badge-warning',
    'Rejected' => 'badge-danger',
    'Draft' => 'badge-secondary',
    default => 'badge-secondary'
};
?>
<?php
  $isEnterprise = (($partner['partner_category'] ?? '') === 'Enterprise Client' || ($partner['partner_type'] ?? '') === 'Enterprise');
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($partner['name']) ?></div>
    <div class="page-subtitle" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <?php if ($isEnterprise): ?>
        <span class="badge badge-info" style="font-size:0.75rem">🏛️ Enterprise Client</span>
      <?php else: ?>
        <span class="badge badge-secondary" style="font-size:0.75rem">🏢 Wholesale Partner</span>
      <?php endif; ?>
      <span>·</span>
      <span><?= e($partner['partner_type']) ?></span>
      <span>·</span>
      <span><?= e($partner['status']) ?></span>
      <span>·</span>
      <span>Created <?= fmtDate($partner['created_at']) ?></span>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=new_order&partner_id=<?= $partner['id'] ?>" class="btn btn-primary" title="Create New Service Order for this Client">
      <?= svgIcon('plus') ?> New Service Order
    </a>
    <a href="<?= APP_URL ?>/?page=partners&action=edit&id=<?= $partner['id'] ?>" class="btn btn-secondary"><?= svgIcon('edit') ?> Edit</a>
    <?php if ($kycApp): ?>
      <a href="<?= APP_URL ?>/?page=kyc&action=<?= $kycApp['status'] === 'Approved' ? 'view' : 'edit' ?>&id=<?= $kycApp['id'] ?>" class="btn btn-secondary">
        <?= svgIcon('document') ?> <?= $kycApp['status'] === 'Approved' ? 'View KYC' : 'Edit KYC' ?>
      </a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/?page=kyc&action=new&partner_id=<?= $partner['id'] ?>" class="btn btn-secondary">
        <?= svgIcon('plus') ?> Apply for KYC
      </a>
    <?php endif; ?>
    <?php 
      $canDeletePartner = (isAdmin() || hasRole('Management') || (isKamUser() && ((int)($partner['kam_id'] ?? 0) === (int)($user['id'] ?? 0) || ($partner['assigned_kam_name'] ?? '') === ($user['full_name'] ?? ''))));
      if ($canDeletePartner): 
    ?>
    <form method="POST" action="<?= APP_URL ?>/?page=partners&action=delete" style="display:inline" data-confirm="Permanently delete partner <?= e($partner['name']) ?>?">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $partner['id'] ?>">
      <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete</button>
    </form>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary"><?= svgIcon('list') ?> All</a>
  </div>
</div>

<!-- 4-Card Stats Grid -->
<div class="stats-grid mb-24" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px">
  <div class="card">
    <div class="card-body" style="text-align:center;padding:20px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $userCount ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Linked Users</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:20px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $orderCount ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Service Orders</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:20px">
      <div style="font-size:1.4rem;font-weight:700;color:var(--primary)"><?= $isEnterprise ? 'Enterprise' : e($partner['partner_type']) ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Account Type</div>
    </div>
  </div>
  <div class="card" style="cursor:pointer" onclick="switchPartnerTab('kyc')">
    <div class="card-body" style="text-align:center;padding:20px">
      <div style="margin-top:2px">
        <?php if ($kycApp): ?>
          <span class="badge <?= $kycBadgeClass ?>" style="font-size:1rem;padding:6px 14px">
            <?= e($kycApp['status']) ?>
          </span>
        <?php else: ?>
          <span class="badge badge-secondary" style="font-size:0.95rem;padding:6px 14px;opacity:0.8">
            Not Applied
          </span>
        <?php endif; ?>
      </div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:10px">
        KYC Compliance Status
      </div>
    </div>
  </div>
</div>

<!-- Tab Navigation Bar -->
<div class="partner-tabs-nav" style="display:flex;gap:10px;border-bottom:2px solid var(--border);margin-bottom:24px">
  <button type="button" class="partner-tab-btn active" id="tabBtnOverview" onclick="switchPartnerTab('overview')">
    <?= svgIcon('building', 16) ?> General &amp; Contact
  </button>
  <button type="button" class="partner-tab-btn" id="tabBtnKyc" onclick="switchPartnerTab('kyc')">
    <?= svgIcon('document', 16) ?> KYC Details
    <?php if ($kycApp): ?>
      <span class="badge <?= $kycBadgeClass ?>" style="font-size:0.75rem;margin-left:6px">
        <?= e($kycApp['status']) ?>
      </span>
    <?php else: ?>
      <span class="badge badge-secondary" style="font-size:0.75rem;margin-left:6px;opacity:0.75">Not Applied</span>
    <?php endif; ?>
  </button>
  <button type="button" class="partner-tab-btn" id="tabBtnClients" onclick="switchPartnerTab('clients')">
    <?= svgIcon('users', 16) ?> <?= $isEnterprise ? 'Service Circuits &amp; Orders' : 'Clients &amp; Subscribers' ?>
    <span class="badge badge-primary" style="font-size:0.75rem;margin-left:6px"><?= count($partnerClients ?? []) ?></span>
  </button>
</div>

<!-- TAB 1: OVERVIEW & LOCATION -->
<div id="partnerTabOverview">
  <div class="grid-2col mb-24">
    <div class="card">
      <div class="card-header"><div class="card-title">General Information</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Organization Name</label><div class="font-600"><?= e($partner['name']) ?></div></div>
          <div class="form-group"><label>Trading Name</label><div><?= e($partner['trading_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Classification</label><div>
            <?php if ($isEnterprise): ?>
              <span class="badge badge-info">🏛️ Enterprise Client</span>
            <?php else: ?>
              <span class="badge badge-secondary">🏢 Wholesale Partner</span>
            <?php endif; ?>
          </div></div>
          <div class="form-group"><label>Partner / Industry Type</label><div><span class="badge badge-primary"><?= e($partner['partner_type']) ?></span></div></div>
          <div class="form-group"><label>Assigned KAM</label><div class="font-600" style="color:var(--primary)"><?= e($partner['assigned_kam_name'] ?: 'Not Assigned') ?></div></div>
          <div class="form-group"><label>Status</label><div>
            <?php if ($partner['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($partner['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </div></div>
          <div class="form-group"><label>Registration Number</label><div><?= e($partner['registration_number'] ?: '—') ?></div></div>
          <div class="form-group"><label>TIN</label><div><?= e($partner['tin'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Contact &amp; Location</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2" style="margin-bottom:12px">
          <div class="form-group"><label>Primary Contact</label><div class="font-600"><?= e($partner['contact_person_name'] ?: ($kycApp['auth_signatory_name'] ?? '—')) ?></div></div>
          <div class="form-group"><label>Contact Phone</label><div><?= e($partner['contact_phone'] ?: ($kycApp['auth_signatory_mobile'] ?? '—')) ?></div></div>
          <div class="form-group"><label>Contact Email</label><div><?= e($partner['contact_email'] ?: ($kycApp['auth_signatory_email'] ?? '—')) ?></div></div>
          <div class="form-group"><label>City / Region</label><div><?= e($partner['city_region'] ?: '—') ?></div></div>
        </div>
        <div class="form-group">
          <label>Headquarters / Physical Address</label>
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;font-size:0.88rem">
            <?= e($partner['address'] ?: '—') ?> (<?= e($partner['country']) ?>)
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($partner['created_at'] !== $partner['updated_at']): ?>
  <div class="card">
    <div class="card-body" style="padding:14px 18px;font-size:.82rem;color:var(--text-muted)">
      Created <?= fmtDateTime($partner['created_at']) ?> · Last updated <?= fmtDateTime($partner['updated_at']) ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- TAB 2: KYC DETAILS -->
<div id="partnerTabKyc" style="display:none">
  <?php if ($kycApp): ?>
    <!-- KYC Header & Action Bar -->
    <div class="card mb-24" style="border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px 22px;background:var(--surface-1);box-shadow:var(--shadow-sm)">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
          <span class="badge <?= $kycBadgeClass ?>" style="font-size:0.92rem;padding:6px 14px;font-weight:700">
            <?= e($kycApp['status']) ?>
          </span>
          <span style="font-size:0.9rem;color:var(--text-secondary)">
            Application: <strong style="color:var(--text-primary)">#KYC-<?= $kycApp['id'] ?></strong>
          </span>
          <span style="font-size:0.9rem;color:var(--text-secondary)">
            Type: <span class="badge badge-info" style="font-size:0.75rem"><?= e($kycApp['kyc_type'] ?: 'Partner') ?></span>
          </span>
          <?php if (!empty($kycApp['submitted_at'])): ?>
          <span style="font-size:0.9rem;color:var(--text-secondary)">
            Submitted: <strong style="color:var(--text-primary)"><?= fmtDate($kycApp['submitted_at']) ?></strong>
          </span>
          <?php endif; ?>
          <?php if (!empty($kycApp['reviewer_name'])): ?>
          <span style="font-size:0.9rem;color:var(--text-secondary)">
            Reviewer: <strong style="color:var(--text-primary)"><?= e($kycApp['reviewer_name']) ?></strong>
          </span>
          <?php endif; ?>
        </div>

        <div>
          <a href="<?= APP_URL ?>/?page=kyc&action=<?= $kycApp['status'] === 'Approved' ? 'view' : 'edit' ?>&id=<?= $kycApp['id'] ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px">
            <?= svgIcon('edit', 14) ?> <?= $kycApp['status'] === 'Approved' ? 'View Full KYC Form' : 'Edit / Manage KYC Application' ?>
          </a>
        </div>
      </div>

      <?php if ($kycApp['status'] === 'Rejected' && !empty($kycApp['rejection_reason'])): ?>
      <div style="margin-top:16px;padding:12px 16px;background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius-md);color:#991b1b;font-size:0.9rem">
        <strong>⚠️ Rejection Reason:</strong> <?= e($kycApp['rejection_reason']) ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- 1. Registered Company Details -->
    <div class="card mb-24">
      <div class="card-header" style="background:var(--surface-2)">
        <div class="card-title" style="font-weight:700"><?= svgIcon('building', 15) ?> Company &amp; Registration Details</div>
      </div>
      <div class="card-body">
        <div class="form-grid form-grid-3" style="margin-bottom:16px">
          <div class="form-group"><label>Registered Legal Name</label><div class="font-600"><?= e($kycApp['registered_name'] ?: ($partner['name'] ?? '—')) ?></div></div>
          <div class="form-group"><label>Registration Number</label><div class="font-mono font-600"><?= e($kycApp['registration_number'] ?: '—') ?></div></div>
          <div class="form-group"><label>TIN</label><div class="font-mono font-600"><?= e($kycApp['tin'] ?: '—') ?></div></div>
        </div>
        <div class="form-grid form-grid-3" style="margin-bottom:16px">
          <div class="form-group"><label>VAT / VRN</label><div class="font-mono"><?= e($kycApp['vat_vrn'] ?: '—') ?></div></div>
          <div class="form-group"><label>City / Region</label><div><?= e($kycApp['city_region'] ?: ($partner['city_region'] ?: '—')) ?></div></div>
          <div class="form-group"><label>Country</label><div><?= e($kycApp['country'] ?: ($partner['country'] ?: 'Tanzania')) ?></div></div>
        </div>
        <div class="form-group">
          <label>Physical Office Address</label>
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;font-size:0.9rem">
            <?= e($kycApp['office_address'] ?: ($partner['address'] ?: '—')) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Contacts & Authorized Signatories -->
    <div class="card mb-24">
      <div class="card-header" style="background:var(--surface-2)">
        <div class="card-title" style="font-weight:700"><?= svgIcon('users', 15) ?> Contacts &amp; Authorized Signatories</div>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px">
          <!-- Signatory 1 -->
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px">
            <div style="font-size:0.8rem;font-weight:700;color:var(--accent);text-transform:uppercase;margin-bottom:8px">Signatory 1 (Primary)</div>
            <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px"><?= e($kycApp['auth_signatory_name'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2px">✉ <?= e($kycApp['auth_signatory_email'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary)">☎ <?= e($kycApp['auth_signatory_mobile'] ?: '—') ?></div>
          </div>

          <!-- Signatory 2 -->
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px">
            <div style="font-size:0.8rem;font-weight:700;color:var(--accent);text-transform:uppercase;margin-bottom:8px">Signatory 2 / Operations</div>
            <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px"><?= e($kycApp['signatory2_name'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2px">✉ <?= e($kycApp['signatory2_email'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary)">☎ <?= e($kycApp['signatory2_phone'] ?: '—') ?></div>
          </div>

          <!-- Technical Contact -->
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px">
            <div style="font-size:0.8rem;font-weight:700;color:var(--accent);text-transform:uppercase;margin-bottom:8px">Technical Supervisor</div>
            <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px"><?= e($kycApp['tech_supervisor_name'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2px">✉ <?= e($kycApp['tech_supervisor_email'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary)">☎ <?= e($kycApp['tech_supervisor_phone'] ?: '—') ?></div>
          </div>

          <!-- Billing Contact -->
          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px">
            <div style="font-size:0.8rem;font-weight:700;color:var(--accent);text-transform:uppercase;margin-bottom:8px">Billing / Escalation</div>
            <div style="font-weight:600;font-size:0.95rem;margin-bottom:4px"><?= e($kycApp['billing_contact_name'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:2px">✉ <?= e($kycApp['billing_contact_email'] ?: '—') ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary)">☎ <?= e($kycApp['billing_contact_phone'] ?: '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Bank & Payment Details -->
    <div class="card mb-24">
      <div class="card-header" style="background:var(--surface-2)">
        <div class="card-title" style="font-weight:700"><?= svgIcon('bank', 15) ?> Bank &amp; Settlement Details</div>
      </div>
      <div class="card-body">
        <div class="form-grid form-grid-3" style="margin-bottom:16px">
          <div class="form-group"><label>Bank Name</label><div class="font-600"><?= e($kycApp['bank_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Branch</label><div><?= e($kycApp['bank_branch'] ?: '—') ?></div></div>
          <div class="form-group"><label>Payment Terms</label><div><span class="badge badge-secondary"><?= e($kycApp['bank_payment_terms'] ?: '30 Days') ?></span></div></div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Account Name</label><div class="font-600"><?= e($kycApp['bank_account_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Account Number</label><div class="font-mono font-600"><?= e($kycApp['bank_account_number'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>

    <!-- 4. Uploaded Compliance Documents -->
    <div class="card mb-24">
      <div class="card-header" style="background:var(--surface-2)">
        <div class="card-title" style="font-weight:700"><?= svgIcon('document', 15) ?> Compliance Documents</div>
      </div>
      <div class="card-body" style="padding:0">
        <?php
        $partnerDocs = [
            'partner_tin_certificate'    => '1. TIN Certificate (Optional)',
            'partner_vrn_certificate'    => '2. VRN Certificate (Optional)',
            'partner_incorporation_cert' => '3. Certificate of Incorporation',
            'partner_tcra_certificate'   => '4. TCRA Certificate (Optional)',
            'partner_tax_clearance'      => '5. Tax Clearance Certificate',
            'partner_business_licence'   => '6. Business Licence Certificate',
        ];
        ?>
        <table class="data-table" style="width:100%;border-collapse:collapse">
          <thead>
            <tr>
              <th style="padding:12px 18px">Compliance Document</th>
              <th style="padding:12px 18px">File Status</th>
              <th style="padding:12px 18px;text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($partnerDocs as $docKey => $docTitle): ?>
            <?php
              $filePath = $kycApp[$docKey] ?? '';
              $hasFile = !empty($filePath);
              $fileName = basename($filePath);
              $downloadUrl = APP_URL . "/?page=download&table=partner_kyc_applications&column={$docKey}&id={$kycApp['id']}";

              $fileExistsOnDisk = false;
              if ($hasFile) {
                  $rel = ltrim(str_replace('\\', '/', $filePath), '/');
                  $checkPath = PUBLIC_DIR . '/' . $rel;
                  if (!is_file($checkPath)) $checkPath = dirname(APP_DIR) . '/public/' . $rel;
                  if (!is_file($checkPath)) $checkPath = dirname(APP_DIR) . '/' . $rel;
                  $fileExistsOnDisk = is_file($checkPath);
              }
            ?>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:12px 18px;font-weight:600">
                <?= e($docTitle) ?>
              </td>
              <td style="padding:12px 18px">
                <?php if ($hasFile && $fileExistsOnDisk): ?>
                  <span class="badge badge-success" style="display:inline-flex;align-items:center;gap:4px">✓ Uploaded</span>
                <?php elseif ($hasFile && !$fileExistsOnDisk): ?>
                  <span class="badge badge-warning" style="display:inline-flex;align-items:center;gap:4px">⚠️ Missing on Disk</span>
                <?php else: ?>
                  <span class="badge badge-secondary" style="opacity:0.75">Not Uploaded</span>
                <?php endif; ?>
              </td>
              <td style="padding:12px 18px;text-align:right">
                <?php if ($hasFile): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="viewSystemFile('<?= e($filePath) ?>', '<?= e($fileName) ?>', '<?= e($downloadUrl) ?>')" style="padding:4px 10px;font-size:0.8rem">
                    <?= svgIcon('eye', 13) ?> View
                  </button>
                  <a href="<?= e($downloadUrl) ?>" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.8rem;text-decoration:none">
                    <?= svgIcon('download', 13) ?> Download
                  </a>
                <?php else: ?>
                  <span class="text-muted" style="font-size:0.82rem">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 5. Audit Trail & History -->
    <?php if (!empty($kycHistory)): ?>
    <div class="card mb-24">
      <div class="card-header" style="background:var(--surface-2)">
        <div class="card-title" style="font-weight:700">📜 KYC Audit History</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table" style="font-size:0.84rem">
          <thead>
            <tr>
              <th>Date / Time</th>
              <th>Action</th>
              <th>Actor</th>
              <th>Status Transition</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kycHistory as $h): ?>
            <tr>
              <td class="font-mono text-muted"><?= fmtDateTime($h['created_at']) ?></td>
              <td class="font-600"><?= e($h['action_title']) ?></td>
              <td><?= e($h['actor_name'] ?: 'System') ?> (<?= e($h['action_role']) ?>)</td>
              <td>
                <span class="badge <?= $h['to_status'] === 'Approved' ? 'badge-success' : ($h['to_status'] === 'Rejected' ? 'badge-danger' : 'badge-warning') ?>">
                  <?= e($h['to_status']) ?>
                </span>
              </td>
              <td><?= e($h['details'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Empty State: No KYC on file -->
    <div class="card" style="padding:48px 24px;text-align:center;border:1px dashed var(--border);border-radius:var(--radius-lg);background:var(--surface-1)">
      <div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:rgba(15,76,129,0.08);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:28px">
        📋
      </div>
      <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:8px;color:var(--text-primary)">No KYC Application on File</h3>
      <p style="font-size:0.9rem;color:var(--text-muted);max-width:480px;margin:0 auto 20px;line-height:1.5">
        A compliance KYC application has not been submitted for <strong><?= e($partner['name']) ?></strong> yet.
        You can initiate and complete their KYC application now.
      </p>
      <a href="<?= APP_URL ?>/?page=kyc&action=new&partner_id=<?= $partner['id'] ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px">
        <?= svgIcon('plus', 16) ?> Start KYC Application for <?= e($partner['name']) ?>
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- TAB 3: CLIENTS & SUBSCRIBERS -->
<div id="partnerTabClients" style="display:none">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
    <div>
      <h2 style="font-size:1.2rem;font-weight:700;margin:0">Partner Clients &amp; Subscribers</h2>
      <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">
        All registered clients, customer installations, and active circuits for <strong><?= e($partner['name']) ?></strong>.
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <input type="text" id="clientSearchInput" class="form-control" placeholder="Filter clients by name, location, CID, ONU..." onkeyup="filterClientsTable()" style="width:260px;font-size:0.85rem">
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="data-table" id="partnerClientsTable">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Location</th>
            <th>ONU / ONT Serial #</th>
            <th>Service Type &amp; Package</th>
            <th>Circuit ID</th>
            <th>Status</th>
            <th>Billing Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($partnerClients)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">
              <div style="font-size:28px;margin-bottom:8px">👥</div>
              <div style="font-weight:600;font-size:1rem;color:var(--text-primary);margin-bottom:4px">No Clients Found</div>
              <div style="font-size:0.85rem;color:var(--text-secondary)">No client service orders have been registered under this partner yet.</div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($partnerClients as $c): ?>
          <?php
            $onuSerial = !empty($c['onu_serial_number']) ? $c['onu_serial_number'] : (!empty($c['onu_evidence_notes']) ? $c['onu_evidence_notes'] : '');
            $pkgStr = $c['fttx_package'] ?: ($c['bandwidth'] ? $c['bandwidth'].' Mbps' : ($c['aggregate_capacity'] ? $c['aggregate_capacity'].' Mbps' : ''));
            $isActive = isOrderActive($c);
            $locDetails = array_filter([$c['customer_location'], $c['building_name'], $c['floor_number'] ? 'Fl: '.$c['floor_number'] : '', $c['apartment_number'] ? 'Unit: '.$c['apartment_number'] : '']);
          ?>
          <tr>
            <td>
              <div class="font-600" style="color:var(--text-primary)"><?= e($c['customer_name']) ?></div>
              <?php if (!empty($c['customer_contact_phone'])): ?>
                <div class="font-sm text-muted" style="font-size:0.75rem"><?= svgIcon('phone', 11) ?> <?= e($c['customer_contact_phone']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size:0.85rem"><?= e($c['customer_location']) ?></div>
              <?php if (!empty($c['building_name']) || !empty($c['apartment_number'])): ?>
                <div class="font-sm text-muted" style="font-size:0.75rem"><?= e(implode(', ', array_slice($locDetails, 1))) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($onuSerial): ?>
                <span class="badge badge-primary" style="font-family:monospace;font-size:0.8rem"><?= e($onuSerial) ?></span>
              <?php else: ?>
                <span class="text-muted font-sm">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div><span class="badge badge-secondary"><?= e($c['service_type'] ?: 'FTTH') ?></span></div>
              <?php if ($pkgStr): ?>
                <div class="font-sm text-secondary" style="font-size:0.78rem;margin-top:2px"><?= e($pkgStr) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($c['circuit_id'])): ?>
                <code style="font-size:0.8rem"><?= e($c['circuit_id']) ?></code>
              <?php else: ?>
                <span class="text-muted font-sm">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= orderStatusClass($c['status']) ?>"><?= e($c['status']) ?></span>
              <?php if ($c['status'] === 'Closed'): ?>
                <?php if ($isActive): ?>
                  <div style="margin-top:3px"><span class="badge badge-success" style="font-size:0.7rem;padding:2px 6px">● Live Active</span></div>
                <?php else: ?>
                  <div style="margin-top:3px"><span class="badge badge-secondary" style="font-size:0.7rem;padding:2px 6px">Inactive</span></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td class="font-sm text-muted">
              <?= !empty($c['billing_start_date']) ? fmtDate($c['billing_start_date']) : '—' ?>
            </td>
            <td>
              <div class="actions" style="display:flex;gap:6px">
                <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Quick View Client Details" onclick='viewClientModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($onuSerial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                  <?= svgIcon('eye') ?>
                </button>
                <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm" style="font-size:0.75rem;padding:3px 8px;text-decoration:none" title="Open Full Order Record">
                  Open Order
                </a>
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

<!-- Modal: Quick View Client Details -->
<div class="modal-backdrop" id="clientQuickViewModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <div class="modal-title" id="clientModalTitle">Client Details</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body" id="clientModalBody">
      <!-- Populated via JavaScript -->
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
      <a href="#" id="clientModalOrderLink" class="btn btn-primary">Open Full Order Record</a>
    </div>
  </div>
</div>

<style>
.partner-tab-btn {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  padding: 10px 18px;
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}
.partner-tab-btn:hover {
  color: var(--text-primary);
  background: var(--surface-2);
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}
.partner-tab-btn.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
  font-weight: 700;
}
</style>

<script>
function switchPartnerTab(tab) {
  const overviewPane = document.getElementById('partnerTabOverview');
  const kycPane      = document.getElementById('partnerTabKyc');
  const clientsPane  = document.getElementById('partnerTabClients');

  const btnOverview  = document.getElementById('tabBtnOverview');
  const btnKyc       = document.getElementById('tabBtnKyc');
  const btnClients   = document.getElementById('tabBtnClients');

  if (overviewPane) overviewPane.style.display = 'none';
  if (kycPane)      kycPane.style.display = 'none';
  if (clientsPane)  clientsPane.style.display = 'none';

  if (btnOverview) btnOverview.classList.remove('active');
  if (btnKyc)      btnKyc.classList.remove('active');
  if (btnClients)  btnClients.classList.remove('active');

  if (tab === 'kyc') {
    if (kycPane) kycPane.style.display = 'block';
    if (btnKyc)  btnKyc.classList.add('active');
    history.replaceState(null, null, '#kyc');
  } else if (tab === 'clients') {
    if (clientsPane) clientsPane.style.display = 'block';
    if (btnClients)  btnClients.classList.add('active');
    history.replaceState(null, null, '#clients');
  } else {
    if (overviewPane) overviewPane.style.display = 'block';
    if (btnOverview)  btnOverview.classList.add('active');
    history.replaceState(null, null, '#overview');
  }
}

function filterClientsTable() {
  const input = document.getElementById('clientSearchInput');
  const filter = input ? input.value.toLowerCase() : '';
  const table = document.getElementById('partnerClientsTable');
  if (!table) return;
  const rows = table.getElementsByTagName('tr');

  for (let i = 1; i < rows.length; i++) {
    const text = rows[i].textContent || rows[i].innerText;
    if (text.toLowerCase().indexOf(filter) > -1) {
      rows[i].style.display = '';
    } else {
      rows[i].style.display = 'none';
    }
  }
}

function viewClientModal(order, onuSerial) {
  document.getElementById('clientModalTitle').innerText = 'Client: ' + (order.customer_name || 'Client Details');
  document.getElementById('clientModalOrderLink').href = '<?= APP_URL ?>/?page=order_detail&id=' + order.id;

  const body = document.getElementById('clientModalBody');
  body.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:0.88rem">
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Client / Customer Name</div>
        <div style="font-weight:600;font-size:1rem;color:var(--text-primary);margin-top:2px">${order.customer_name || '—'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Order Number</div>
        <div style="font-weight:600;color:var(--primary);margin-top:2px">${order.order_number || '—'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Service Type</div>
        <div style="font-weight:600;margin-top:2px">${order.service_type || 'FTTH'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Package / Speed</div>
        <div style="font-weight:600;margin-top:2px">${order.fttx_package || order.bandwidth || order.aggregate_capacity || 'Standard'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">ONU / ONT Serial Number</div>
        <div style="font-weight:700;font-family:monospace;color:var(--accent);margin-top:2px">${onuSerial || 'Not Registered / Pending'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Circuit ID</div>
        <div style="font-weight:600;font-family:monospace;margin-top:2px">${order.circuit_id || '—'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Contact Person</div>
        <div style="margin-top:2px">${order.customer_contact_name || '—'}</div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Contact Phone / Email</div>
        <div style="margin-top:2px">${order.customer_contact_phone || ''} ${order.customer_contact_email ? '· ' + order.customer_contact_email : ''}</div>
      </div>
      <div style="grid-column:1/-1">
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Physical Location & Address</div>
        <div style="margin-top:2px;background:var(--surface-2);padding:8px 12px;border-radius:6px;border:1px solid var(--border)">
          ${order.customer_location || '—'}
          ${order.building_name ? ' · Building: ' + order.building_name : ''}
          ${order.floor_number ? ' · Floor: ' + order.floor_number : ''}
          ${order.apartment_number ? ' · Unit: ' + order.apartment_number : ''}
          ${order.gps_coordinates ? '<br><small class="text-muted">GPS: ' + order.gps_coordinates + '</small>' : ''}
        </div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Order Status</div>
        <div style="margin-top:2px"><span class="badge badge-primary">${order.status || '—'}</span></div>
      </div>
      <div>
        <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;font-weight:700">Billing Start Date</div>
        <div style="margin-top:2px">${order.billing_start_date || '—'}</div>
      </div>
    </div>
  `;

  document.getElementById('clientQuickViewModal').classList.add('active');
}

// Auto open tab if URL has hash or param
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const hash = window.location.hash;
  if (hash === '#kyc' || urlParams.get('tab') === 'kyc') {
    switchPartnerTab('kyc');
  } else if (hash === '#clients' || urlParams.get('tab') === 'clients') {
    switchPartnerTab('clients');
  }
});
</script>
