<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Settings &amp; System Management</div>
    <div class="page-subtitle">Configure company details, currency, service pricing, standard defaults, and backup/migration</div>
  </div>
  <div class="page-header-actions">
    <div class="tab-nav" style="display:inline-flex;background:var(--surface-2);border-radius:var(--radius-md);padding:4px;gap:4px;flex-wrap:wrap">
      <a href="<?= APP_URL ?>/?page=settings&tab=company" class="btn <?= $tab === 'company' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:0.85rem">
        <?= svgIcon('building', 15) ?> Company Info
      </a>
      <a href="<?= APP_URL ?>/?page=settings&tab=pricing" class="btn <?= in_array($tab, ['pricing', 'service_types']) ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:0.85rem">
        <?= svgIcon('server', 15) ?> Service Types &amp; Package Costs
      </a>
      <a href="<?= APP_URL ?>/?page=settings&tab=defaults" class="btn <?= $tab === 'defaults' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:0.85rem">
        <?= svgIcon('dollar', 15) ?> Standard MRC &amp; NRC Defaults
      </a>
      <a href="<?= APP_URL ?>/?page=settings&tab=migration" class="btn <?= $tab === 'migration' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:0.85rem">
        <?= svgIcon('download', 15) ?> Backup &amp; Migration
      </a>
    </div>
  </div>
</div>

<?php 
  $sysCurrency = strtoupper($company['default_currency'] ?? 'USD') ?: 'USD';
?>

<?php if ($tab === 'migration'): ?>
<!-- ============================================================ -->
<!-- SYSTEM MIGRATION & PORTABILITY TAB                           -->
<!-- ============================================================ -->

<!-- Live System Overview Counters -->
<div class="kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:24px">
  <div class="card" style="padding:18px;border-left:4px solid var(--primary)">
    <div class="text-muted" style="font-size:0.8rem;text-transform:uppercase;font-weight:700">Database State</div>
    <div style="font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-top:4px">
      <?= count($dbStats) ?> <span style="font-size:0.9rem;font-weight:500;color:var(--text-muted)">Tables</span>
    </div>
    <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px">
      <?= array_sum(array_column($dbStats, 'rows')) ?> total database rows
    </div>
  </div>

  <div class="card" style="padding:18px;border-left:4px solid #10B981">
    <div class="text-muted" style="font-size:0.8rem;text-transform:uppercase;font-weight:700">Uploaded File Storage</div>
    <div style="font-size:1.6rem;font-weight:800;color:#10B981;margin-top:4px">
      <?= count($uploadFiles) ?> <span style="font-size:0.9rem;font-weight:500;color:var(--text-muted)">Files</span>
    </div>
    <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px">
      <?= round($totalUploadSize / (1024 * 1024), 2) ?> MB total storage size
    </div>
  </div>

  <div class="card" style="padding:18px;border-left:4px solid #F59E0B">
    <div class="text-muted" style="font-size:0.8rem;text-transform:uppercase;font-weight:700">System Environment</div>
    <div style="font-size:1.4rem;font-weight:800;color:var(--text-primary);margin-top:4px">
      PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>
    </div>
    <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px">
      <?= htmlspecialchars(php_uname('s')) ?> (<?= htmlspecialchars(php_uname('n')) ?>)
    </div>
  </div>

  <div class="card" style="padding:18px;border-left:4px solid <?= $systemIntegrity['healthy'] ? '#10B981' : '#EF4444' ?>">
    <div class="text-muted" style="font-size:0.8rem;text-transform:uppercase;font-weight:700">Integrity Health</div>
    <div style="font-size:1.4rem;font-weight:800;color:<?= $systemIntegrity['healthy'] ? '#10B981' : '#EF4444' ?>;margin-top:4px">
      <?= $systemIntegrity['healthy'] ? '100% Healthy' : 'Action Required' ?>
    </div>
    <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px">
      <?= count($systemIntegrity['warnings']) ?> warnings, <?= count($systemIntegrity['errors']) ?> errors
    </div>
  </div>
</div>

<div class="grid-2col" style="gap:24px;margin-bottom:24px">
  <!-- Card 1: Export Complete System -->
  <div class="card" style="border:1px solid var(--border)">
    <div class="card-header" style="background:rgba(15, 76, 129, 0.04)">
      <div class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= svgIcon('download', 18) ?> Export System Package (Device A)
      </div>
    </div>
    <div class="card-body">
      <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;margin-bottom:16px">
        Creates a single, self-contained migration archive (<code>system-migration-*.zip</code>) containing:
      </p>
      <ul style="font-size:0.88rem;color:var(--text-primary);padding-left:20px;line-height:1.8;margin-bottom:20px">
        <li><strong>Complete Database Snapshot:</strong> All <?= count($dbStats) ?> tables, rows, relationships, and constraints.</li>
        <li><strong>Uploaded Files Storage:</strong> All <?= count($uploadFiles) ?> physical files (Signed SOFs, PDFs, DOCX, evidence, KYC attachments).</li>
        <li><strong>Portable Configuration:</strong> Company information, pricing rules, and SLA definitions.</li>
        <li><strong>Manifest &amp; SHA-256 Checksums:</strong> Cryptographic integrity verification for every bundled file.</li>
      </ul>

      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;margin-bottom:20px">
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px">Package Estimate</div>
        <div style="display:flex;justify-content:space-between;font-size:0.88rem">
          <span>Estimated Archive Size:</span>
          <span style="font-weight:700">~<?= round($totalUploadSize / (1024 * 1024), 2) + 0.5 ?> MB</span>
        </div>
      </div>

      <a href="<?= APP_URL ?>/?page=settings&action=export_system" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
        <?= svgIcon('download', 16) ?> Export &amp; Download Migration Package (.zip)
      </a>
    </div>
  </div>

  <!-- Card 2: Import & Restore System -->
  <div class="card" style="border:1px solid var(--border)">
    <div class="card-header" style="background:rgba(16, 185, 129, 0.04)">
      <div class="card-title" style="display:flex;align-items:center;gap:8px">
        <?= svgIcon('upload', 18) ?> Restore / Import System (Device B)
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= APP_URL ?>/?page=settings&action=import_system" enctype="multipart/form-data" onsubmit="return confirm('WARNING: Restoring this migration package will synchronize the database and uploads directory with the contents of the package. Do you wish to proceed?');">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.6;margin-bottom:16px">
          Select a <code>system-migration-*.zip</code> package exported from Device A to restore the entire portal state.
        </p>

        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label" style="font-weight:700">Migration Package File (.zip) <span class="text-danger">*</span></label>
          <input type="file" name="migration_package" class="form-control" accept=".zip" required style="padding:10px">
          <div class="form-hint">Must contain a valid <code>manifest.json</code> and database snapshot.</div>
        </div>

        <div class="alert alert-warning" style="font-size:0.84rem;margin-bottom:20px">
          <strong>Notice:</strong> Database tables will be safely imported using transactions, and uploaded files will be unpacked directly into the active upload directory.
        </div>

        <button type="submit" class="btn btn-success btn-lg" style="width:100%;justify-content:center">
          <?= svgIcon('check', 16) ?> Validate &amp; Restore System Migration
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Card 3: Live System Health & Integrity Audit -->
<div class="card" style="border:1px solid var(--border)">
  <div class="card-header">
    <div class="card-title"><?= svgIcon('activity') ?> Live System &amp; Storage Integrity Audit</div>
  </div>
  <div class="card-body">
    <div class="grid-2col" style="gap:24px">
      <!-- Database Entities -->
      <div>
        <div style="font-size:0.85rem;font-weight:700;color:var(--text-primary);margin-bottom:12px">Key Record Counts</div>
        <table class="table table-sm" style="width:100%;font-size:0.86rem">
          <thead>
            <tr>
              <th>Entity</th>
              <th style="text-align:right">Total Records</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($systemIntegrity['records'] as $tbl => $info): ?>
            <tr>
              <td><?= e($info['label']) ?> (<code><?= e($tbl) ?></code>)</td>
              <td style="text-align:right;font-weight:700"><?= number_format($info['count']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Storage Locations & Warnings -->
      <div>
        <div style="font-size:0.85rem;font-weight:700;color:var(--text-primary);margin-bottom:12px">Storage Status &amp; Warnings</div>
        <div style="background:var(--surface-2);border-radius:var(--radius-md);padding:14px;margin-bottom:14px;font-size:0.86rem">
          <div><strong>Upload Directory:</strong> <code><?= e($systemIntegrity['storage']['upload_dir']) ?></code></div>
          <div style="margin-top:6px"><strong>Directory Status:</strong> <?= $systemIntegrity['storage']['writable'] ? '<span class="badge badge-success">Writable</span>' : '<span class="badge badge-danger">Read Only</span>' ?></div>
          <div style="margin-top:6px"><strong>Physical Files on Disk:</strong> <?= number_format($systemIntegrity['storage']['file_count']) ?> files (<?= $systemIntegrity['storage']['total_mb'] ?> MB)</div>
        </div>

        <?php if (!empty($systemIntegrity['warnings'])): ?>
          <div class="alert alert-warning" style="font-size:0.82rem">
            <strong>Audit Notes:</strong>
            <ul style="margin:6px 0 0 16px;padding:0">
              <?php foreach ($systemIntegrity['warnings'] as $w): ?>
                <li><?= e($w) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php else: ?>
          <div class="alert alert-success" style="font-size:0.84rem">
            <?= svgIcon('check') ?> All database file links and physical storage directories are 100% synchronized and healthy.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'company'): ?>
<!-- ============================================================ -->
<!-- COMPANY IDENTITY & SYSTEM CURRENCY SETTINGS TAB              -->
<!-- ============================================================ -->
<form method="POST" action="<?= APP_URL ?>/?page=settings&action=save_company" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

  <div class="grid-2col">
    <!-- General Information -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= svgIcon('building') ?> Company Identity &amp; Portal Currency</div>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">System Default Currency <span class="text-danger">*</span></label>
          <select name="default_currency" class="form-control" style="font-weight:600;background:var(--surface-2)">
            <option value="TZS" <?= $sysCurrency === 'TZS' ? 'selected' : '' ?>>TZS (Tanzanian Shilling - System Fixed Default)</option>
            <option value="USD" <?= $sysCurrency === 'USD' ? 'selected' : '' ?>>USD (US Dollar)</option>
          </select>
          <div class="form-hint">Controls primary calculation, order summaries, package listings, and default standard rates across all portal pages.</div>
        </div>

        <div class="form-group" style="margin-top:14px">
          <label class="form-label">Legal Company Name <span class="text-danger">*</span></label>
          <input type="text" name="company_name" class="form-control" required value="<?= e($company['company_name'] ?? 'Neilos Network Ltd') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Trading / Brand Name</label>
          <input type="text" name="trading_name" class="form-control" value="<?= e($company['trading_name'] ?? 'Neilos') ?>" placeholder="e.g. Neilos Network">
        </div>

        <div class="grid-2col" style="gap:12px">
          <div class="form-group">
            <label class="form-label">TIN (Tax Identification No.)</label>
            <input type="text" name="tin" class="form-control" value="<?= e($company['tin'] ?? '') ?>" placeholder="e.g. 123-456-789">
          </div>
          <div class="form-group">
            <label class="form-label">VAT / VRN</label>
            <input type="text" name="vat_vrn" class="form-control" value="<?= e($company['vat_vrn'] ?? '') ?>" placeholder="e.g. 40-001234-X">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Certificate of Incorporation / Reg. No.</label>
          <input type="text" name="registration_number" class="form-control" value="<?= e($company['registration_number'] ?? '') ?>" placeholder="e.g. 145982">
        </div>

        <div class="form-group">
          <label class="form-label">Physical Address</label>
          <textarea name="address" class="form-control" rows="2" placeholder="e.g. Plot 42, Ali Hassan Mwinyi Rd, Victoria"><?= e($company['address'] ?? '') ?></textarea>
        </div>

        <div class="grid-2col" style="gap:12px">
          <div class="form-group">
            <label class="form-label">City / Region</label>
            <input type="text" name="city_region" class="form-control" value="<?= e($company['city_region'] ?? 'Dar es Salaam') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" class="form-control" value="<?= e($company['country'] ?? 'Tanzania') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Contact & Signatory Details -->
    <div>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('user-check') ?> Authorized Signatory (For Countersigning SOF)</div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Authorized Signatory Full Name</label>
            <input type="text" name="authorized_signatory" class="form-control" value="<?= e($company['authorized_signatory'] ?? '') ?>" placeholder="e.g. John Doe">
          </div>
          <div class="form-group">
            <label class="form-label">Designation / Title</label>
            <input type="text" name="signatory_title" class="form-control" value="<?= e($company['signatory_title'] ?? '') ?>" placeholder="e.g. Chief Executive Officer / Managing Director">
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('phone') ?> Official Contacts</div>
        </div>
        <div class="card-body">
          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">Official Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($company['phone'] ?? '') ?>" placeholder="+255 22 ...">
            </div>
            <div class="form-group">
              <label class="form-label">General Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($company['email'] ?? '') ?>" placeholder="info@neilos.co.tz">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Website URL</label>
            <input type="text" name="website" class="form-control" value="<?= e($company['website'] ?? 'https://neilos.co.tz') ?>">
          </div>

          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">Finance Billing Contact</label>
              <input type="text" name="finance_contact" class="form-control" value="<?= e($company['finance_contact'] ?? '') ?>" placeholder="Finance Dept">
            </div>
            <div class="form-group">
              <label class="form-label">Finance Email</label>
              <input type="email" name="finance_email" class="form-control" value="<?= e($company['finance_email'] ?? '') ?>" placeholder="billing@neilos.co.tz">
            </div>
          </div>

          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">NOC / Technical Contact</label>
              <input type="text" name="tech_contact" class="form-control" value="<?= e($company['tech_contact'] ?? '') ?>" placeholder="NOC Support Desk">
            </div>
            <div class="form-group">
              <label class="form-label">Technical Email</label>
              <input type="email" name="tech_email" class="form-control" value="<?= e($company['tech_email'] ?? '') ?>" placeholder="noc@neilos.co.tz">
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('image') ?> Company Logo</div>
        </div>
        <div class="card-body">
          <?php if (!empty($company['logo_path'])): ?>
            <div style="margin-bottom:12px;padding:12px;background:#f8f9fa;border-radius:6px;text-align:center">
              <img src="<?= e(APP_URL . '/' . $company['logo_path']) ?>" alt="Neilos Logo" style="max-height:60px;max-width:200px;object-fit:contain">
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Upload New Logo (PNG / JPG / SVG)</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="form-hint">Shown in header of all printed Service Order Forms</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:1.5rem;text-align:right">
    <button type="submit" class="btn btn-primary btn-lg"><?= svgIcon('check') ?> Save Company Settings</button>
  </div>
</form>

<?php elseif (in_array($tab, ['pricing', 'service_types'])): ?>
<!-- ============================================================ -->
<!-- TAB 1: SERVICE TYPES & PACKAGE COSTS MANAGEMENT              -->
<!-- ============================================================ -->
<?php
  $activeFilter = $selectedFilter ?: 'all';
  $filteredPackages = $allPricingPackages;
  if ($activeFilter !== 'all') {
      $filteredPackages = array_filter($allPricingPackages, function($p) use ($activeFilter) {
          return strcasecmp($p['service_type'], $activeFilter) === 0;
      });
  }
?>

<!-- Header & Action Buttons -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
  <div>
    <h2 style="font-size:1.25rem;font-weight:700;margin:0">Service Types &amp; Package Cost Tiers</h2>
    <div style="font-size:0.86rem;color:var(--text-secondary);margin-top:2px">
      Manage active service categories and speed tier package costs in <?= e($sysCurrency) ?>.
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <button type="button" class="btn btn-secondary" onclick="openAddServiceTypeModal()">
      <?= svgIcon('plus') ?> Add Service Type
    </button>
    <button type="button" class="btn btn-primary" onclick="openAddPackageModal('<?= e($activeFilter !== 'all' ? $activeFilter : 'FTTH') ?>')">
      <?= svgIcon('plus') ?> Add Package Tier
    </button>
  </div>
</div>

<!-- Section 1: Service Types List -->
<div class="card" style="margin-bottom:24px;border:1px solid var(--border)">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1rem">
      <?= svgIcon('server', 17) ?> Registered Service Types
    </div>
    <span class="badge badge-secondary"><?= count($allServiceTypes) ?> Service Types</span>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Service Type Name</th>
          <th>Code / Key</th>
          <th>Short Code</th>
          <th>Description</th>
          <th>Pricing Model</th>
          <th>Configured Tiers</th>
          <th>Status</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allServiceTypes)): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">
            No service types found. Click <strong>[ + Add Service Type ]</strong> to configure.
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($allServiceTypes as $st): ?>
        <?php 
          $pkgCount = $serviceTypeCounts[$st['name']] ?? 0;
        ?>
        <tr>
          <td class="font-600">
            <span class="badge badge-primary"><?= e($st['name']) ?></span>
          </td>
          <td><code><?= e($st['code']) ?></code></td>
          <td><span class="badge badge-info" style="font-weight:700"><?= e($st['short_code'] ?: '—') ?></span></td>
          <td class="font-sm text-secondary"><?= e($st['description'] ?: '—') ?></td>
          <td>
            <?php
              $modelLabel = match($st['pricing_model'] ?? 'package') {
                'package'  => 'Package Tiers (FTTH / FTTB)',
                'capacity' => 'Capacity-based (Layer 2)',
                'custom'   => 'Custom Quote (BIA / DIA)',
                'fixed'    => 'Fixed Charge (Remote Hands)',
                default    => ucfirst($st['pricing_model'] ?? 'package')
              };
            ?>
            <span class="badge badge-secondary"><?= e($modelLabel) ?></span>
          </td>
          <td>
            <?php if ($pkgCount > 0): ?>
              <a href="<?= APP_URL ?>/?page=settings&tab=pricing&filter=<?= urlencode($st['name']) ?>" class="badge badge-info" style="text-decoration:none;font-weight:600" title="Filter Packages">
                <?= svgIcon('dollar', 12) ?> <?= $pkgCount ?> Tier<?= $pkgCount > 1 ? 's' : '' ?>
              </a>
            <?php else: ?>
              <span class="text-muted font-sm">0 Tiers</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ((int)$st['is_active'] === 1): ?>
              <span class="badge badge-success">Active</span>
            <?php else: ?>
              <span class="badge badge-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-muted font-sm"><?= (int)$st['display_order'] ?></td>
          <td>
            <div class="actions" style="display:flex;gap:6px">
              <button type="button" class="btn btn-secondary btn-sm" title="Add Package Tier" onclick="openAddPackageModal('<?= e($st['name']) ?>')" style="font-size:0.75rem;padding:3px 7px">
                + Tier
              </button>
              <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Edit Service Type" onclick='openEditServiceTypeModal(<?= json_encode($st, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                <?= svgIcon('edit') ?>
              </button>
              <form method="POST" action="<?= APP_URL ?>/?page=settings&action=delete_service_type" style="display:inline" data-confirm="Delete service type '<?= e($st['name']) ?>'?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="service_type_id" value="<?= $st['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Service Type"><?= svgIcon('trash') ?></button>
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

<!-- Section 2: Configured Service Pricing Packages / Speed Tiers -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px">
  <h3 style="font-size:1.1rem;font-weight:700;margin:0">Configured Speed Tiers &amp; Package Costs</h3>
</div>

<!-- Service Type Filter Pills -->
<div class="service-type-filters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:6px;background:var(--surface-2);border-radius:var(--radius-md)">
  <a href="<?= APP_URL ?>/?page=settings&tab=pricing&filter=all" 
     class="btn btn-sm <?= $activeFilter === 'all' ? 'btn-primary' : 'btn-ghost' ?>" 
     style="font-size:0.82rem;font-weight:600">
    All Services <span class="badge" style="background:rgba(255,255,255,0.25);margin-left:4px"><?= count($allPricingPackages) ?></span>
  </a>
  <?php 
    $knownTypes = [];
    foreach ($allServiceTypes as $st) { $knownTypes[$st['name']] = true; }
    foreach ($allPricingPackages as $pkg) { $knownTypes[$pkg['service_type']] = true; }
    ksort($knownTypes);
  ?>
  <?php foreach (array_keys($knownTypes) as $stName): ?>
    <?php 
      $countForType = count(array_filter($allPricingPackages, fn($p) => strcasecmp($p['service_type'], $stName) === 0));
      $isCurrent = strcasecmp($activeFilter, $stName) === 0;
    ?>
    <a href="<?= APP_URL ?>/?page=settings&tab=pricing&filter=<?= urlencode($stName) ?>" 
       class="btn btn-sm <?= $isCurrent ? 'btn-primary' : 'btn-ghost' ?>" 
       style="font-size:0.82rem;font-weight:600">
      <?= e($stName) ?> 
      <span class="badge" style="background:rgba(255,255,255,0.25);margin-left:4px"><?= $countForType ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Package Data Table -->
<div class="card" style="border:1px solid var(--border)">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Service Type</th>
          <th>Package Name</th>
          <th>Code / Key</th>
          <th>Speed</th>
          <th>Currency</th>
          <th>Monthly Recurring Charge (MRC)</th>
          <th>Non-Recurring Charge (NRC)</th>
          <th>Status</th>
          <th>Order</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filteredPackages)): ?>
        <tr>
          <td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted)">
            No pricing package tiers found <?= $activeFilter !== 'all' ? 'for <strong>' . e($activeFilter) . '</strong>' : '' ?>. 
            <button type="button" class="btn btn-sm btn-primary" style="margin-left:8px" onclick="openAddPackageModal('<?= e($activeFilter !== 'all' ? $activeFilter : 'FTTH') ?>')">
              + Add Package Tier
            </button>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($filteredPackages as $pkg): ?>
        <?php 
          $curr = strtoupper($pkg['currency'] ?? $sysCurrency);
          $mrcFormatted = ($curr === 'USD') ? ('$' . number_format((float)$pkg['mrc_amount'], 2) . ' USD') : ('TZS ' . number_format((float)$pkg['mrc_amount']));
          $nrcFormatted = ($curr === 'USD') ? ('$' . number_format((float)$pkg['nrc_amount'], 2) . ' USD') : ('TZS ' . number_format((float)$pkg['nrc_amount']));
        ?>
        <tr>
          <td>
            <span class="badge badge-primary"><?= e($pkg['service_type']) ?></span>
          </td>
          <td class="font-600"><?= e($pkg['package_name']) ?></td>
          <td><code><?= e($pkg['package_code']) ?></code></td>
          <td><?= $pkg['speed_mbps'] ? (int)$pkg['speed_mbps'] . ' Mbps' : '—' ?></td>
          <td>
            <span class="badge <?= $curr === 'USD' ? 'badge-info' : 'badge-secondary' ?>"><?= $curr ?></span>
          </td>
          <td class="font-600" style="color:var(--accent)">
            <?= $mrcFormatted ?>
          </td>
          <td class="text-muted font-sm">
            <?= $nrcFormatted ?>
          </td>
          <td>
            <?php if ((int)$pkg['is_active'] === 1): ?>
              <span class="badge badge-success">Active</span>
            <?php else: ?>
              <span class="badge badge-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-muted font-sm"><?= (int)$pkg['display_order'] ?></td>
          <td>
            <div class="actions" style="display:flex;gap:6px">
              <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Edit Package" onclick='openEditPackageModal(<?= json_encode($pkg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                <?= svgIcon('edit') ?>
              </button>
              <form method="POST" action="<?= APP_URL ?>/?page=settings&action=delete_pricing_package" style="display:inline" data-confirm="Delete pricing package '<?= e($pkg['package_name']) ?>'?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                <input type="hidden" name="return_filter" value="<?= e($activeFilter) ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Package"><?= svgIcon('trash') ?></button>
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

<!-- Modal: Add / Edit Pricing Package -->
<div class="modal-backdrop" id="addEditPackageModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title" id="packageModalTitle">Add New Pricing Package</div>
      <button class="modal-close" type="button" onclick="closeModal(document.getElementById('addEditPackageModal'))">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=settings&action=save_pricing_package">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="package_id" id="modalPkgId" value="0">
      <input type="hidden" name="return_filter" id="modalReturnFilter" value="<?= e($activeFilter) ?>">
      
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Service Type <span class="text-danger">*</span></label>
            <select name="service_type" id="modalPkgServiceType" class="form-control" required>
              <?php foreach ($allServiceTypes as $st): ?>
              <option value="<?= e($st['name']) ?>"><?= e($st['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Speed in Mbps <span class="text-muted font-sm">(Optional)</span></label>
            <input type="number" name="speed_mbps" id="modalPkgSpeed" class="form-control" placeholder="e.g. 30" min="0">
          </div>
        </div>

        <div class="form-grid form-grid-2" style="margin-top:10px">
          <div class="form-group">
            <label class="form-label">Package Name <span class="text-danger">*</span></label>
            <input type="text" name="package_name" id="modalPkgName" class="form-control" placeholder="e.g. 20 Mbps or FTTB-30" required>
          </div>

          <div class="form-group">
            <label class="form-label">Package Code / Key <span class="text-muted font-sm">(Unique identifier)</span></label>
            <input type="text" name="package_code" id="modalPkgCode" class="form-control" placeholder="e.g. FTTH-20 or FTTB-30">
          </div>
        </div>

        <div class="form-grid form-grid-3" style="margin-top:10px;display:grid;grid-template-columns:110px 1fr 1fr;gap:10px">
          <div class="form-group">
            <label class="form-label">Currency <span class="text-danger">*</span></label>
            <select name="currency" id="modalPkgCurrency" class="form-control" onchange="updateCurrencyBadges(this.value)">
              <option value="TZS" <?= $sysCurrency === 'TZS' ? 'selected' : '' ?>>TZS</option>
              <option value="USD" <?= $sysCurrency === 'USD' ? 'selected' : '' ?>>USD ($)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">MRC (Monthly) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text currency-badge" id="mrcBadge"><?= e($sysCurrency) ?></span>
              <input type="text" name="mrc_amount" id="modalPkgMrc" class="form-control" placeholder="e.g. 33,000" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">NRC (Install)</label>
            <div class="input-group">
              <span class="input-group-text currency-badge" id="nrcBadge"><?= e($sysCurrency) ?></span>
              <input type="text" name="nrc_amount" id="modalPkgNrc" class="form-control" placeholder="e.g. 160,000" value="160,000">
            </div>
          </div>
        </div>

        <div class="form-grid form-grid-2" style="margin-top:10px">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="is_active" id="modalPkgActive" class="form-control">
              <option value="1">Active (Available in Portal)</option>
              <option value="0">Inactive (Hidden from New Orders)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="modalPkgOrder" class="form-control" value="0" min="0">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addEditPackageModal'))">Cancel</button>
        <button type="submit" class="btn btn-primary" id="packageModalSubmitBtn">Save Package</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add / Edit Service Type -->
<div class="modal-backdrop" id="addEditServiceTypeModal">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title" id="serviceTypeModalTitle">Add Service Type</div>
      <button class="modal-close" type="button" onclick="closeModal(document.getElementById('addEditServiceTypeModal'))">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=settings&action=save_service_type">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="service_type_id" id="modalStId" value="0">
      
      <div class="modal-body">
        <div class="form-grid form-grid-3" style="display:grid;grid-template-columns:1fr 1fr 110px;gap:10px">
          <div class="form-group">
            <label class="form-label">Service Type Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="modalStName" class="form-control" placeholder="e.g. FTTH or Metro Fibre" required>
          </div>

          <div class="form-group">
            <label class="form-label">Code / Key <span class="text-muted font-sm">(Unique)</span></label>
            <input type="text" name="code" id="modalStCode" class="form-control" placeholder="e.g. FTTH or Metro-Fibre">
          </div>

          <div class="form-group">
            <label class="form-label">Short Code <span class="text-danger">*</span></label>
            <input type="text" name="short_code" id="modalStShortCode" class="form-control" placeholder="e.g. FTH" maxlength="10" style="text-transform:uppercase;font-weight:700">
          </div>
        </div>

        <div class="form-group" style="margin-top:10px">
          <label class="form-label">Description</label>
          <textarea name="description" id="modalStDescription" class="form-control" rows="2" placeholder="Brief explanation of the service type..."></textarea>
        </div>

        <div class="form-grid form-grid-2" style="margin-top:10px">
          <div class="form-group">
            <label class="form-label">Pricing Model</label>
            <select name="pricing_model" id="modalStModel" class="form-control">
              <option value="package">Package Tiers (FTTH / FTTB)</option>
              <option value="capacity">Capacity-based (Layer 2)</option>
              <option value="custom">Custom / Quote-based (BIA / DIA)</option>
              <option value="fixed">Fixed Rate (Remote Hands)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="is_active" id="modalStActive" class="form-control">
              <option value="1">Active (Enabled across CRM)</option>
              <option value="0">Inactive (Disabled)</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-top:10px">
          <label class="form-label">Display Order</label>
          <input type="number" name="display_order" id="modalStOrder" class="form-control" value="0" min="0">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('addEditServiceTypeModal'))">Cancel</button>
        <button type="submit" class="btn btn-primary" id="serviceTypeModalSubmitBtn">Save Service Type</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateCurrencyBadges(curr) {
  var c = (curr || '<?= e($sysCurrency) ?>').toUpperCase();
  var mrcBadge = document.getElementById('mrcBadge');
  var nrcBadge = document.getElementById('nrcBadge');
  if (mrcBadge) mrcBadge.innerText = c;
  if (nrcBadge) nrcBadge.innerText = c;
}

function openAddPackageModal(defaultServiceType) {
  document.getElementById('packageModalTitle').innerText = 'Add New Pricing Package Tier';
  document.getElementById('modalPkgId').value = '0';
  if (defaultServiceType && defaultServiceType !== 'all') {
    document.getElementById('modalPkgServiceType').value = defaultServiceType;
  }
  document.getElementById('modalPkgName').value = '';
  document.getElementById('modalPkgCode').value = '';
  document.getElementById('modalPkgSpeed').value = '';
  
  var curr = '<?= e($sysCurrency) ?>';
  document.getElementById('modalPkgCurrency').value = curr;
  updateCurrencyBadges(curr);

  document.getElementById('modalPkgMrc').value = '';
  document.getElementById('modalPkgNrc').value = '160,000';
  document.getElementById('modalPkgActive').value = '1';
  document.getElementById('modalPkgOrder').value = '0';
  document.getElementById('packageModalSubmitBtn').innerText = 'Create Package Tier';
  
  var modal = document.getElementById('addEditPackageModal');
  if (modal) modal.classList.add('open');
}

function openEditPackageModal(pkg) {
  document.getElementById('packageModalTitle').innerText = 'Edit Package Tier: ' + pkg.package_name;
  document.getElementById('modalPkgId').value = pkg.id;
  document.getElementById('modalPkgServiceType').value = pkg.service_type;
  document.getElementById('modalPkgName').value = pkg.package_name;
  document.getElementById('modalPkgCode').value = pkg.package_code;
  document.getElementById('modalPkgSpeed').value = pkg.speed_mbps || '';
  
  var curr = (pkg.currency || '<?= e($sysCurrency) ?>').toUpperCase();
  document.getElementById('modalPkgCurrency').value = curr;
  updateCurrencyBadges(curr);

  if (curr === 'USD') {
    document.getElementById('modalPkgMrc').value = Number(pkg.mrc_amount).toFixed(2);
    document.getElementById('modalPkgNrc').value = Number(pkg.nrc_amount).toFixed(2);
  } else {
    document.getElementById('modalPkgMrc').value = Number(pkg.mrc_amount).toLocaleString('en-US');
    document.getElementById('modalPkgNrc').value = Number(pkg.nrc_amount).toLocaleString('en-US');
  }

  document.getElementById('modalPkgActive').value = pkg.is_active;
  document.getElementById('modalPkgOrder').value = pkg.display_order || 0;
  document.getElementById('packageModalSubmitBtn').innerText = 'Update Package Tier';
  
  var modal = document.getElementById('addEditPackageModal');
  if (modal) modal.classList.add('open');
}

function openAddServiceTypeModal() {
  document.getElementById('serviceTypeModalTitle').innerText = 'Add New Service Type';
  document.getElementById('modalStId').value = '0';
  document.getElementById('modalStName').value = '';
  document.getElementById('modalStCode').value = '';
  document.getElementById('modalStShortCode').value = '';
  document.getElementById('modalStDescription').value = '';
  document.getElementById('modalStModel').value = 'package';
  document.getElementById('modalStActive').value = '1';
  document.getElementById('modalStOrder').value = '0';
  document.getElementById('serviceTypeModalSubmitBtn').innerText = 'Create Service Type';
  
  var modal = document.getElementById('addEditServiceTypeModal');
  if (modal) modal.classList.add('open');
}

function openEditServiceTypeModal(st) {
  document.getElementById('serviceTypeModalTitle').innerText = 'Edit Service Type: ' + st.name;
  document.getElementById('modalStId').value = st.id;
  document.getElementById('modalStName').value = st.name;
  document.getElementById('modalStCode').value = st.code;
  document.getElementById('modalStShortCode').value = st.short_code || '';
  document.getElementById('modalStDescription').value = st.description || '';
  document.getElementById('modalStModel').value = st.pricing_model || 'package';
  document.getElementById('modalStActive').value = st.is_active;
  document.getElementById('modalStOrder').value = st.display_order || 0;
  document.getElementById('serviceTypeModalSubmitBtn').innerText = 'Update Service Type';
  
  var modal = document.getElementById('addEditServiceTypeModal');
  if (modal) modal.classList.add('open');
}
</script>

<?php elseif ($tab === 'defaults'): ?>
<!-- ============================================================ -->
<!-- TAB 2: STANDARD MRC & NRC DEFAULTS PER SERVICE TYPE          -->
<!-- ============================================================ -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
  <div>
    <h2 style="font-size:1.25rem;font-weight:700;margin:0">Standard MRC &amp; NRC Defaults</h2>
    <div style="font-size:0.86rem;color:var(--text-secondary);margin-top:2px">
      Configure default Standard Non-Recurring Charge (NRC) and Standard Monthly Recurring Charge (MRC) per service type in <?= e($sysCurrency) ?>.
    </div>
  </div>
</div>

<!-- Business Rule / Policy Banner -->
<div class="alert alert-info" style="border-radius:var(--radius-md);margin-bottom:20px;font-size:0.88rem;line-height:1.6">
  <div style="font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:6px">
    <?= svgIcon('info', 16) ?> Pricing Rule Architecture:
  </div>
  <div>
    • <strong>Pre-defined System Defaults:</strong> Applied exclusively to <strong>FTTH</strong> (pre-defined package speed tiers &amp; TZS 160,000 NRC) and <strong>DIA</strong> (TZS 250,000 NRC).<br>
    • <strong>Custom Quoted Services:</strong> All other service types (<strong>FTTB, Layer 2, BIA, Remote Hands</strong>) initialize with <strong>TZS 0.00</strong> standard costs upon order submission and are quoted directly by <strong>BSA</strong> (Feasibility NRC) and <strong>KAM</strong> (Commercial MRC).<br>
    • You may edit default baseline NRC/MRC amounts for any service type below using the <strong>[ Edit Defaults ]</strong> action.
  </div>
</div>

<!-- Standard Defaults Table -->
<div class="card" style="border:1px solid var(--border)">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Service Type Name</th>
          <th>Code / Key</th>
          <th>Pricing Model</th>
          <th>Standard NRC (<?= e($sysCurrency) ?>)</th>
          <th>Standard MRC (<?= e($sysCurrency) ?>)</th>
          <th>Default Mode / Policy</th>
          <th>Status</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allServiceTypes as $st): ?>
        <?php
          $isPredefined = in_array(strtoupper(trim($st['name'])), ['FTTH', 'DIA']);
          $nrcVal = (float)($st['default_nrc'] ?? 0);
          $mrcVal = (float)($st['default_mrc'] ?? 0);
        ?>
        <tr>
          <td class="font-600">
            <span class="badge badge-primary"><?= e($st['name']) ?></span>
          </td>
          <td><code><?= e($st['code']) ?></code></td>
          <td>
            <span class="badge badge-secondary"><?= e(ucfirst($st['pricing_model'] ?? 'package')) ?></span>
          </td>
          <td class="font-600" style="color:var(--text-primary)">
            <?= $sysCurrency ?> <?= number_format($nrcVal, 2) ?>
          </td>
          <td class="font-600" style="color:var(--accent)">
            <?= $mrcVal > 0 ? ($sysCurrency . ' ' . number_format($mrcVal, 2)) : '<span class="text-muted">0.00 (Quoted by KAM)</span>' ?>
          </td>
          <td>
            <?php if ($isPredefined): ?>
              <span class="badge badge-success" style="font-weight:600">Pre-defined Default</span>
            <?php else: ?>
              <span class="badge badge-info" style="font-weight:600">Quoted by BSA &amp; KAM</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ((int)$st['is_active'] === 1): ?>
              <span class="badge badge-success">Active</span>
            <?php else: ?>
              <span class="badge badge-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <button type="button" class="btn btn-secondary btn-sm" onclick='openEditStandardDefaultsModal(<?= json_encode($st, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
              <?= svgIcon('edit', 14) ?> Edit Defaults
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Edit Standard Defaults -->
<div class="modal-backdrop" id="editStandardDefaultsModal">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <div class="modal-title" id="defaultsModalTitle">Edit Standard MRC &amp; NRC Defaults</div>
      <button class="modal-close" type="button" onclick="closeModal(document.getElementById('editStandardDefaultsModal'))">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=settings&action=save_standard_defaults">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="service_type_id" id="defaultsModalStId" value="0">

      <div class="modal-body">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">Service Type</label>
          <input type="text" id="defaultsModalStName" class="form-control" readonly style="background:var(--surface-2);font-weight:700">
        </div>

        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">Standard Non-Recurring Charge (NRC) <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><?= e($sysCurrency) ?></span>
            <input type="text" name="default_nrc" id="defaultsModalNrc" class="form-control" placeholder="e.g. 160,000" required>
          </div>
          <div class="form-hint">Baseline installation fee applied on order creation (use 0 for custom BSA quotes).</div>
        </div>

        <div class="form-group">
          <label class="form-label">Standard Monthly Recurring Charge (MRC) <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><?= e($sysCurrency) ?></span>
            <input type="text" name="default_mrc" id="defaultsModalMrc" class="form-control" placeholder="e.g. 27,500" required>
          </div>
          <div class="form-hint">Baseline recurring fee (use 0 for custom KAM/BSA commercial quotes).</div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editStandardDefaultsModal'))">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Standard Defaults</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditStandardDefaultsModal(st) {
  document.getElementById('defaultsModalStId').value = st.id;
  document.getElementById('defaultsModalStName').value = st.name + ' (' + (st.code || st.name) + ')';
  
  var nrc = Number(st.default_nrc || 0);
  var mrc = Number(st.default_mrc || 0);
  document.getElementById('defaultsModalNrc').value = nrc > 0 ? nrc.toLocaleString('en-US') : '0.00';
  document.getElementById('defaultsModalMrc').value = mrc > 0 ? mrc.toLocaleString('en-US') : '0.00';

  var modal = document.getElementById('editStandardDefaultsModal');
  if (modal) modal.classList.add('open');
}
</script>

<?php endif; ?>
