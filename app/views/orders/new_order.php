<?php
// New Service Order Form
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">New Service Order</h1>
    <div class="page-subtitle">Complete all required fields for the service order request.</div>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/?page=orders&action=create" enctype="multipart/form-data" id="orderForm">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Service Order Details</div></div>
  <div class="card-body">

    <!-- Section 1: Partner & KAM -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('building', 16) ?> Partner &amp; Account Selection</div>
      <div class="form-grid">
        <?php if (!isPartnerUser() && !empty($partners)): ?>
        <?php
          $enterprisePartners = array_filter($partners, fn($p) => ($p['partner_category'] ?? '') === 'Enterprise Client' || ($p['partner_type'] ?? '') === 'Enterprise');
          $wholesalePartners  = array_filter($partners, fn($p) => ($p['partner_category'] ?? '') !== 'Enterprise Client' && ($p['partner_type'] ?? '') !== 'Enterprise');
          $curSelectedPId = (int)($selectedPartnerId ?: ($_POST['partner_id'] ?? 0));
        ?>
        <div class="form-group" style="grid-column:1/-1">
          <label>Select Partner / Enterprise Client <span class="required">*</span></label>
          <select name="partner_id" id="partnerSelect" class="form-control" required onchange="handlePartnerSelect(this.value)">
            <option value="">— Select Organization / Account —</option>
            <?php if (!empty($enterprisePartners)): ?>
            <optgroup label="🏛️ Enterprise &amp; Business Clients (Auto-Fills Customer Info)">
              <?php foreach($enterprisePartners as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $curSelectedPId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?><?= !empty($p['trading_name']) ? ' (' . e($p['trading_name']) . ')' : '' ?> — Enterprise Client
              </option>
              <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
            <?php if (!empty($wholesalePartners)): ?>
            <optgroup label="🏢 Wholesale Partners &amp; ISPs (Retail Subscribers)">
              <?php foreach($wholesalePartners as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $curSelectedPId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?> (<?= e($p['partner_type'] ?: 'ISP') ?>)
              </option>
              <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
          </select>
          <small class="text-muted" style="margin-top:4px;display:block">
            Selecting an <strong>Enterprise Client</strong> automatically pre-fills customer &amp; contact information below without repetitive data entry.
          </small>
        </div>
        <?php endif; ?>
        <?php if (isPartnerUser()): ?>
        <div class="form-group">
          <label>Assigned Key Account Manager (KAM)</label>
          <input type="text" class="form-control" value="<?= e($partnerKamName ?: 'Assigned automatically by Neilos') ?>" readonly style="background:var(--surface-hover);color:var(--text-secondary);cursor:not-allowed">
        </div>
        <?php elseif (isKamUser()): ?>
        <div class="form-group">
          <label>Assigned Key Account Manager (KAM)</label>
          <input type="hidden" name="assigned_kam" value="<?= e(currentUser()['full_name']) ?>">
          <input type="text" class="form-control" value="<?= e(currentUser()['full_name']) ?> (You)" readonly style="background:var(--surface-hover);color:var(--text-secondary);cursor:not-allowed">
        </div>
        <?php else: ?>
        <div class="form-group">
          <label>Assigned KAM <span class="required">*</span></label>
          <select name="assigned_kam" class="form-control" required>
            <option value="">— Select KAM —</option>
            <?php foreach($kamList as $k): ?>
            <option value="<?= e($k['full_name']) ?>"><?= e($k['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Section 2: Customer Info -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('users', 16) ?> Customer Information</div>

      <!-- Enterprise Client Auto-Fill Active Notification Banner -->
      <div id="enterpriseAutoFillAlert" style="display:none;background:rgba(15,76,129,0.06);border:1px solid rgba(15,76,129,0.25);border-left:4px solid var(--primary);border-radius:var(--radius-sm);padding:10px 16px;font-size:0.86rem;color:var(--primary);margin-bottom:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
          <div>
            🏛️ <strong>Enterprise Client Profile Loaded:</strong> Basic customer &amp; contact details have been automatically pre-filled from <strong id="autoFilledPartnerName"></strong>'s record.
          </div>
          <button type="button" class="btn btn-sm btn-secondary" onclick="clearCustomerFields()" style="font-size:0.75rem;padding:3px 10px">
            Clear Customer Fields
          </button>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label>Customer Name <span class="required">*</span></label>
          <input type="text" name="customer_name" id="custNameInput" class="form-control" required placeholder="Full customer or company name">
        </div>
        <div class="form-group">
          <label>Customer Location <span class="required">*</span></label>
          <input type="text" name="customer_location" id="custLocInput" class="form-control" required placeholder="Address, building, or area">
        </div>
        <div class="form-group">
          <label>GPS Coordinates <span class="required">*</span></label>
          <input type="text" name="gps_coordinates" class="form-control" required placeholder="-6.7924, 39.2083">
        </div>
        <div class="form-group">
          <label>Site Category <span class="required">*</span></label>
          <select name="site_category" id="siteCategorySelect" class="form-control" required>
            <option value="">— Select Site Category —</option>
            <option value="Commercial Building">Commercial Building</option>
            <option value="Single Dwelling Unit (SDU)">Single Dwelling Unit (SDU)</option>
            <option value="Multi Dwelling Unit (MDU)">Multi Dwelling Unit (MDU)</option>
            <option value="Cell Site">Cell Site</option>
            <option value="Data Center">Data Center</option>
          </select>
        </div>
        <div class="form-group">
          <label>Building Name</label>
          <input type="text" name="building_name" id="buildingNameInput" class="form-control" placeholder="Building name">
        </div>
        <div class="form-group">
          <label>Floor Number</label>
          <input type="text" name="floor_number" class="form-control" placeholder="e.g. 3rd Floor">
        </div>
        <div class="form-group">
          <label>Apartment / Unit Number</label>
          <input type="text" name="apartment_number" class="form-control" placeholder="e.g. Suite 401">
        </div>
        <div class="form-group">
          <label>Contact Person Name <span class="required">*</span></label>
          <input type="text" name="customer_contact_name" id="custContactNameInput" class="form-control" placeholder="Contact person full name" required>
        </div>
        <div class="form-group">
          <label>Contact Phone <span class="required">*</span></label>
          <input type="tel" name="customer_contact_phone" id="custContactPhoneInput" class="form-control" placeholder="+255 7xx xxx xxx" required>
        </div>
        <div class="form-group">
          <label>Contact Email <span class="required">*</span></label>
          <input type="email" name="customer_contact_email" id="custContactEmailInput" class="form-control" placeholder="customer@example.com" required>
        </div>
      </div>
    </div>

    <!-- Section 3: Service Type -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('server', 16) ?> Service Details</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Service Type <span class="required">*</span></label>
          <select name="service_type" id="serviceType" class="form-control" required onchange="handleServiceType(this.value)">
            <option value="">— Select Service Type —</option>
            <?php
              $dynServiceTypes = getActiveServiceTypes($db);
              foreach ($dynServiceTypes as $stName):
                if (!empty($isCurrentPartnerEnterprise) && (stripos($stName, 'FTTH') !== false || stripos($stName, 'FTTB') !== false)) {
                    continue;
                }
            ?>
            <option value="<?= e($stName) ?>"><?= e($stName) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="enterpriseCatalogNotice" style="display:<?= !empty($isCurrentPartnerEnterprise) ? 'block' : 'none' ?>;margin-top:6px;font-size:0.8rem;color:var(--primary)">
            🏛️ <strong>Enterprise Dedicated Catalog:</strong> Retail shared broadband (FTTH/FTTB) is hidden for Enterprise Clients.
          </div>
        </div>

        <!-- FTTH package -->
        <div class="form-group" id="fttxPackageRow" style="display:none">
          <label id="fttxPackageLabel">FTTH Retail Package <span class="required">*</span></label>
          <select name="fttx_package" id="fttxPackage" class="form-control" data-is-partner="<?= isPartnerUser() ? '1' : '0' ?>" onchange="updateFTTxPrice(this.value)">
            <option value="">— Select Package —</option>
            <?php
              $dynPkgs = getActivePricingPackages('FTTH', $db);
              foreach ($dynPkgs as $pRow):
                $pCode = $pRow['package_code'] ?: $pRow['package_name'];
                $curr = $pRow['currency'] ?: 'USD';
                $pPriceStr = ($curr === 'USD') ? '$' . number_format((float)$pRow['mrc_amount'], 2) . ' USD / mo' : number_format((float)$pRow['mrc_amount'], 0) . ' TZS / mo';
                $pOptLabel = isPartnerUser() ? $pRow['package_name'] : "{$pRow['package_name']} ({$pPriceStr})";
            ?>
              <option value="<?= e($pCode) ?>" data-service="<?= e($pRow['service_type']) ?>"><?= e($pOptLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <script>
          window.SERVER_PRICING_PACKAGES = <?= json_encode(getActivePricingPackages(null, $db)) ?>;
          if (typeof getDynamicFttxPackageOptions === 'function') {
            fttxPackageOptions = getDynamicFttxPackageOptions();
          }
        </script>

        <!-- BIA bandwidth -->
        <div class="form-group" id="diaRow" style="display:none">
          <label>Bandwidth <span class="required">*</span></label>
          <div class="input-group">
            <input type="number" name="bandwidth" id="bandwidth" class="form-control" placeholder="e.g. 100" min="1">
            <span class="input-addon">Mbps</span>
          </div>
        </div>

        <!-- L2 NNI -->
        <div class="form-group" id="nniRow" style="display:none">
          <label>NNI / Handoff Point <span class="required">*</span></label>
          <input type="text" name="nni_location" class="form-control" placeholder="e.g. Wingu DC">
        </div>

        <!-- L2 NNI Port Size -->
        <div class="form-group" id="nniPortSizeRow" style="display:none">
          <label>NNI Port Size <span class="required">*</span></label>
          <select name="nni_port_size" id="nniPortSize" class="form-control">
            <option value="1G">1G</option>
            <option value="10G" selected>10G</option>
            <option value="40G">40G</option>
          </select>
        </div>

        <!-- L2 Capacity -->
        <div class="form-group" id="l2CapRow" style="display:none">
          <label>Capacity <span class="required">*</span></label>
          <div class="input-group">
            <input type="number" name="aggregate_capacity" id="aggregateCapacity" class="form-control" placeholder="e.g. 50" min="1" step="1" oninput="updateL2Price(this.value)" onchange="updateL2Price(this.value)">
            <span class="input-addon">Mbps</span>
          </div>
        </div>

        <!-- Contract term -->
        <div class="form-group" id="contractTermRow" style="display:none">
          <label>Committed Minimum Service Term</label>
          <select name="contract_term" id="contractTerm" class="form-control">
            <option value="">— Select Term —</option>
            <option value="12 Months">12 Months</option>
            <option value="24 Months">24 Months</option>
            <option value="36 Months">36 Months</option>
            <option value="Month to Month">Month to Month</option>
          </select>
        </div>

        <!-- Remote hands check -->
        <div class="form-group" id="remoteHandsRow" style="display:none">
          <label>Remote Hands Required?</label>
          <select name="remote_hands_required" class="form-control" onchange="updateRemoteHands(this.value)">
            <option value="0">No</option>
            <option value="1">Yes (+80,000 TZS NRC)</option>
          </select>
        </div>

        <!-- Other products pricing note -->
        <div class="form-group form-col-full" id="otherProductNoteRow" style="display:none">
          <div style="background:var(--surface-hover);border:1px solid var(--border);padding:12px;border-radius:6px;font-size:.875rem;color:var(--text-secondary)">
            <strong><?= svgIcon('info', 16) ?> Pricing Note:</strong> Predefined package costs are only available for <strong>FTTH</strong> and <strong>DIA</strong> service types. For other products, BSA will insert standard/revised NRC and KAM will insert MRC during feasibility &amp; commercial review.
          </div>
        </div>

        <div class="form-group form-col-full">
          <label>Special Requirements</label>
          <textarea name="special_requirements" class="form-control" rows="3" placeholder="Any special requirements, conditions, or notes..."></textarea>
        </div>
      </div>
    </div>

    <!-- Section 4: Commercial Summary (Hidden for Partner users) -->
    <?php if (!isPartnerUser()): ?>
    <div class="form-section" id="commercialSection" style="display:none">
      <div class="form-section-title"><?= svgIcon('chart', 16) ?> Commercial Summary</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px">
        <!-- NRC -->
        <div class="commercial-box">
          <div class="commercial-row section-header">One-Time Charges (NRC)</div>
          <div class="commercial-row">
            <span>Base NRC</span>
            <span class="commercial-value" id="baseNRCDisplay">—</span>
          </div>
          <div class="commercial-row" id="remoteHandsNRCRow" style="display:none">
            <span>Remote Hands NRC</span>
            <span class="commercial-value" id="rhNRCDisplay">TZS 80,000.00</span>
          </div>
          <div class="commercial-row">
            <span>NRC Subtotal</span>
            <span class="commercial-value" id="nrcSubtotal">—</span>
          </div>
          <div class="commercial-row">
            <span>VAT (18%)</span>
            <span class="commercial-value" id="vatNRC">—</span>
          </div>
          <div class="commercial-row total">
            <span>Total NRC incl. VAT</span>
            <span class="commercial-value highlight" id="totalNRC">—</span>
          </div>
          <input type="hidden" name="base_nrc_usd" id="hidBaseNRC" value="0">
          <input type="hidden" name="remote_hands_nrc_usd" id="hidRHNRC" value="0">
          <input type="hidden" name="nrc_subtotal_usd" id="hidNRCSub" value="0">
          <input type="hidden" name="vat_on_nrc" id="hidVatNRC" value="0">
          <input type="hidden" name="total_nrc_incl_vat" id="hidTotalNRC" value="0">
        </div>

        <!-- MRC -->
        <div class="commercial-box" id="mrcBox">
          <div class="commercial-row section-header">Monthly Charges (MRC)</div>
          <div class="commercial-row" id="mrcRow">
            <span id="mrcLabel">Base MRC</span>
            <span class="commercial-value" id="baseMRCDisplay">—</span>
          </div>
          <div class="commercial-row" id="discountRow" style="display:none">
            <span>Discount</span>
            <span class="commercial-value" id="discountDisplay">—</span>
          </div>
          <div class="commercial-row">
            <span>VAT (18%)</span>
            <span class="commercial-value" id="vatMRC">—</span>
          </div>
          <div class="commercial-row total">
            <span>Total MRC incl. VAT</span>
            <span class="commercial-value highlight" id="totalMRC">—</span>
          </div>
          <input type="hidden" name="base_mrc" id="hidBaseMRC" value="0">
          <input type="hidden" name="mrc_currency" id="hidMRCCurr" value="TZS">
          <input type="hidden" name="discount_pct" id="hidDiscPct" value="0">
          <input type="hidden" name="discount_amount" id="hidDiscAmt" value="0">
          <input type="hidden" name="vat_on_mrc" id="hidVatMRC" value="0">
          <input type="hidden" name="total_mrc_incl_vat" id="hidTotalMRC" value="0">
        </div>
      </div>
    </div>
    <?php else: ?>
      <!-- Hidden calculation fields preserved for partner submissions -->
      <input type="hidden" name="base_nrc_usd" id="hidBaseNRC" value="0">
      <input type="hidden" name="remote_hands_nrc_usd" id="hidRHNRC" value="0">
      <input type="hidden" name="nrc_subtotal_usd" id="hidNRCSub" value="0">
      <input type="hidden" name="vat_on_nrc" id="hidVatNRC" value="0">
      <input type="hidden" name="total_nrc_incl_vat" id="hidTotalNRC" value="0">
      <input type="hidden" name="base_mrc" id="hidBaseMRC" value="0">
      <input type="hidden" name="mrc_currency" id="hidMRCCurr" value="TZS">
      <input type="hidden" name="discount_pct" id="hidDiscPct" value="0">
      <input type="hidden" name="discount_amount" id="hidDiscAmt" value="0">
      <input type="hidden" name="vat_on_mrc" id="hidVatMRC" value="0">
      <input type="hidden" name="total_mrc_incl_vat" id="hidTotalMRC" value="0">
    <?php endif; ?>

    <!-- Section 5: Documents -->
    <div class="form-section">
      <div class="form-section-title"><?= svgIcon('document', 16) ?> Supporting Documents (Optional)</div>
      <div class="file-input-wrapper" id="dropZone" onclick="if(event.target.tagName !== 'INPUT') document.getElementById('fileInput').click()">
        <input type="file" name="documents[]" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.xls,.csv" data-custom-preview-container="#filePreviewList">
        <div class="file-input-icon"><?= svgIcon('upload', 32) ?></div>
        <div class="file-input-text"><strong>Click to upload</strong> or drag and drop</div>
        <div class="file-input-text" style="font-size:.75rem;margin-top:4px">PDF, DOCX, XLSX, JPG, PNG — max 10 MB each</div>
      </div>
      <div id="filePreviewList" style="margin-top:12px;display:flex;flex-direction:column;gap:8px"></div>
    </div>

  </div><!-- /.card-body -->
</div><!-- /.card -->

<div style="display:flex;gap:12px;justify-content:flex-end">
  <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary">Cancel</a>
  <button type="submit" class="btn btn-primary">
    <?= svgIcon('check') ?> Submits feasibility requests
  </button>
</div>

</form>

<script>
window.ALL_SERVICE_TYPES = <?= json_encode(getActiveServiceTypes($db)) ?>;
window.PARTNERS_DATA = <?= !empty($partners) ? json_encode(array_combine(array_column($partners, 'id'), $partners), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '{}' ?>;
window.IS_CURRENT_PARTNER_ENTERPRISE = <?= !empty($isCurrentPartnerEnterprise) ? 'true' : 'false' ?>;

function isRetailBroadband(st) {
  const upper = (st || '').toUpperCase();
  return upper.includes('FTTH') || upper.includes('FTTB') || upper.includes('FIBRE TO THE HOME') || upper.includes('FIBRE TO THE BUILDING');
}

function updateServiceTypesForPartner(isEnterprise) {
  const stSelect = document.getElementById('serviceType');
  if (!stSelect || !window.ALL_SERVICE_TYPES) return;

  const currentVal = stSelect.value;
  const filteredTypes = isEnterprise 
    ? window.ALL_SERVICE_TYPES.filter(st => !isRetailBroadband(st))
    : window.ALL_SERVICE_TYPES;

  stSelect.innerHTML = '<option value="">— Select Service Type —</option>';
  filteredTypes.forEach(st => {
    const opt = document.createElement('option');
    opt.value = st;
    opt.textContent = st;
    if (st === currentVal) {
      opt.selected = true;
    }
    stSelect.appendChild(opt);
  });

  // If previous selection was FTTH/FTTB and partner is Enterprise, reset selection
  if (isEnterprise && isRetailBroadband(currentVal)) {
    stSelect.value = '';
    if (typeof handleServiceType === 'function') {
      handleServiceType('');
    }
  }

  const noticeEl = document.getElementById('enterpriseCatalogNotice');
  if (noticeEl) {
    noticeEl.style.display = isEnterprise ? 'block' : 'none';
  }
}

function handlePartnerSelect(partnerId) {
  if (!partnerId || !window.PARTNERS_DATA || !window.PARTNERS_DATA[partnerId]) {
    hideEnterpriseAlert();
    updateServiceTypesForPartner(false);
    return;
  }

  const p = window.PARTNERS_DATA[partnerId];
  const isEnterprise = (p.partner_category === 'Enterprise Client' || p.partner_type === 'Enterprise' || p.partner_type === 'Government');

  // Dynamically filter service type dropdown for Enterprise vs Wholesale
  updateServiceTypesForPartner(isEnterprise);

  if (isEnterprise) {
    // 1. Auto-fill customer name
    const custNameEl = document.getElementById('custNameInput');
    if (custNameEl) {
      custNameEl.value = p.name || '';
      custNameEl.style.borderColor = 'var(--primary)';
      setTimeout(() => custNameEl.style.borderColor = '', 1500);
    }

    // 2. Auto-fill location
    const custLocEl = document.getElementById('custLocInput');
    if (custLocEl) {
      let loc = (p.address || '').trim();
      if (p.city_region && !loc.toLowerCase().includes(p.city_region.toLowerCase())) {
        loc = loc ? (loc + ', ' + p.city_region) : p.city_region;
      }
      custLocEl.value = loc;
      custLocEl.style.borderColor = 'var(--primary)';
      setTimeout(() => custLocEl.style.borderColor = '', 1500);
    }

    // 3. Auto-fill site category default for Enterprise
    const siteCatEl = document.getElementById('siteCategorySelect');
    if (siteCatEl && !siteCatEl.value) {
      siteCatEl.value = 'Commercial Building';
    }

    // 4. Auto-fill contact details
    const contactNameEl = document.getElementById('custContactNameInput');
    if (contactNameEl) {
      contactNameEl.value = p.contact_name || p.name || '';
      contactNameEl.style.borderColor = 'var(--primary)';
      setTimeout(() => contactNameEl.style.borderColor = '', 1500);
    }

    const contactPhoneEl = document.getElementById('custContactPhoneInput');
    if (contactPhoneEl) {
      contactPhoneEl.value = p.contact_phone || '';
      contactPhoneEl.style.borderColor = 'var(--primary)';
      setTimeout(() => contactPhoneEl.style.borderColor = '', 1500);
    }

    const contactEmailEl = document.getElementById('custContactEmailInput');
    if (contactEmailEl) {
      contactEmailEl.value = p.contact_email || '';
      contactEmailEl.style.borderColor = 'var(--primary)';
      setTimeout(() => contactEmailEl.style.borderColor = '', 1500);
    }

    // Show visual notice banner
    showEnterpriseAlert(p.name);
  } else {
    hideEnterpriseAlert();
  }
}

function showEnterpriseAlert(partnerName) {
  const alertEl = document.getElementById('enterpriseAutoFillAlert');
  const nameEl = document.getElementById('autoFilledPartnerName');
  if (nameEl) nameEl.textContent = partnerName;
  if (alertEl) {
    alertEl.style.display = 'block';
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function hideEnterpriseAlert() {
  const alertEl = document.getElementById('enterpriseAutoFillAlert');
  if (alertEl) alertEl.style.display = 'none';
}

function clearCustomerFields() {
  const ids = ['custNameInput', 'custLocInput', 'custContactNameInput', 'custContactPhoneInput', 'custContactEmailInput', 'buildingNameInput'];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  hideEnterpriseAlert();
}

// Trigger auto-fill on initial page load if partner is pre-selected
document.addEventListener('DOMContentLoaded', () => {
  const partnerSelect = document.getElementById('partnerSelect');
  if (partnerSelect && partnerSelect.value) {
    handlePartnerSelect(partnerSelect.value);
  } else if (window.IS_CURRENT_PARTNER_ENTERPRISE) {
    updateServiceTypesForPartner(true);
  }
});
</script>
