// ============================================================
// Neilos Portal — Order Form JS
// FTTx, Layer 2 (last mile), BIA, Remote Hands dynamic pricing
// ============================================================

const VAT = 0.18;

function getDynamicFttxPackageOptions() {
  if (window.SERVER_PRICING_PACKAGES && Array.isArray(window.SERVER_PRICING_PACKAGES) && window.SERVER_PRICING_PACKAGES.length > 0) {
    const opts = { FTTH: [], FTTB: [] };
    window.SERVER_PRICING_PACKAGES.forEach(pkg => {
      const st = (pkg.service_type || '').toUpperCase();
      const curr = (pkg.currency || 'USD').toUpperCase();
      const mrcVal = parseFloat(pkg.mrc_amount) || 0;
      const nrcVal = parseFloat(pkg.nrc_amount) || (curr === 'USD' ? 60 : 60);
      const formattedMrc = `$${mrcVal.toFixed(2)} USD`;
      const item = {
        key: pkg.package_code || pkg.package_name,
        label: `${pkg.package_name}`,
        price: mrcVal,
        nrc: nrcVal,
        currency: curr,
        priceLabel: `${formattedMrc} / mo`
      };
      if (st.includes('FTTB')) {
        opts.FTTB.push(item);
      } else if (st.includes('FTTH') || st.includes('FTTX')) {
        opts.FTTH.push(item);
      }
    });
    if (opts.FTTH.length > 0 || opts.FTTB.length > 0) {
      return opts;
    }
  }

  return {
    FTTH: [
      { key: 'FTTH-20', label: '20 Mbps', price: 10.40, nrc: 60.00, currency: 'USD', priceLabel: '$10.40 USD / mo' },
      { key: 'FTTH-30', label: '30 Mbps', price: 12.48, nrc: 60.00, currency: 'USD', priceLabel: '$12.48 USD / mo' },
      { key: 'FTTH-40', label: '40 Mbps', price: 13.52, nrc: 60.00, currency: 'USD', priceLabel: '$13.52 USD / mo' },
      { key: 'FTTH-50', label: '50 Mbps', price: 16.22, nrc: 60.00, currency: 'USD', priceLabel: '$16.22 USD / mo' },
      { key: 'FTTH-60', label: '60 Mbps', price: 19.47, nrc: 60.00, currency: 'USD', priceLabel: '$19.47 USD / mo' },
      { key: 'FTTH-80', label: '80 Mbps', price: 23.36, nrc: 60.00, currency: 'USD', priceLabel: '$23.36 USD / mo' },
      { key: 'FTTH-100', label: '100 Mbps', price: 28.04, nrc: 60.00, currency: 'USD', priceLabel: '$28.04 USD / mo' }
    ],
    FTTB: [
      { key: 'FTTB-30', label: '30 Mbps (FTTB)', price: 38.00, nrc: 60.00, currency: 'USD', priceLabel: '$38.00 USD / mo' },
      { key: 'FTTB-40', label: '40 Mbps (FTTB)', price: 48.00, nrc: 60.00, currency: 'USD', priceLabel: '$48.00 USD / mo' },
      { key: 'FTTB-50', label: '50 Mbps (FTTB)', price: 58.00, nrc: 60.00, currency: 'USD', priceLabel: '$58.00 USD / mo' },
      { key: 'FTTB-60', label: '60 Mbps (FTTB)', price: 68.00, nrc: 60.00, currency: 'USD', priceLabel: '$68.00 USD / mo' },
      { key: 'FTTB-70', label: '70 Mbps (FTTB)', price: 78.00, nrc: 60.00, currency: 'USD', priceLabel: '$78.00 USD / mo' },
      { key: 'FTTB-80', label: '80 Mbps (FTTB)', price: 88.00, nrc: 60.00, currency: 'USD', priceLabel: '$88.00 USD / mo' },
      { key: 'FTTB-90', label: '90 Mbps (FTTB)', price: 98.00, nrc: 60.00, currency: 'USD', priceLabel: '$98.00 USD / mo' },
      { key: 'FTTB-100', label: '100 Mbps (FTTB)', price: 108.00, nrc: 60.00, currency: 'USD', priceLabel: '$108.00 USD / mo' }
    ]
  };
}

let fttxPackageOptions = getDynamicFttxPackageOptions();

const fttxMrcPrices = {
  'FTTH-20': 10.40,
  'FTTH-30': 12.48,
  'FTTH-40': 13.52,
  'FTTH-50': 16.22,
  'FTTH-60': 19.47,
  'FTTH-80': 23.36,
  'FTTH-100': 28.04,
  'FTTx-30': 12.48,
  'FTTB-30': 38.00,
  'FTTB-40': 48.00,
  'FTTB-50': 58.00,
  'FTTB-60': 68.00,
  'FTTB-70': 78.00,
  'FTTB-80': 88.00,
  'FTTB-90': 98.00,
  'FTTB-100': 108.00
};

let currentServiceType = '';
let currentBaseMRC     = 0;
let currentBaseNRC     = 0;
let currentRHNRC       = 0;
let currentCurrency    = 'USD';

function fmt(n, curr = '$') {
  const s = Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  return curr ? (curr === '$' ? `$${s}` : `${curr} ${s}`) : s;
}
function setHid(id, val) { const el = document.getElementById(id); if (el) el.value = val; }
function setTxt(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function show(id) { const el = document.getElementById(id); if (el) el.style.display = ''; }
function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

function handleServiceType(type) {
  currentServiceType = type;

  // Reset all optional rows
  hide('fttxPackageRow'); hide('diaRow'); hide('nniRow'); hide('nniPortSizeRow'); hide('l2CapRow');
  hide('contractTermRow'); hide('remoteHandsRow'); hide('otherProductNoteRow');

  currentBaseMRC  = 0;
  currentBaseNRC  = 0;
  currentRHNRC    = 0;
  currentCurrency = 'USD';

  if (type === 'FTTH') {
    show('fttxPackageRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentBaseNRC = 60;
    currentCurrency = 'USD';

    const labelEl = document.getElementById('fttxPackageLabel');
    if (labelEl) {
      labelEl.innerHTML = 'FTTH Retail Package <span class="required">*</span>';
    }

    const pkgSelect = document.getElementById('fttxPackage');
    if (pkgSelect) {
      const isPartner = pkgSelect.dataset.isPartner === '1';
      const options = fttxPackageOptions.FTTH || [];
      const prevVal = pkgSelect.value;

      let matched = false;
      pkgSelect.innerHTML = '<option value="">— Select Package —</option>';
      options.forEach(opt => {
        const optEl = document.createElement('option');
        optEl.value = opt.key;
        optEl.textContent = isPartner ? opt.label : `${opt.label} (${opt.priceLabel})`;
        if (prevVal === opt.key || (prevVal && prevVal.split('-')[1] && prevVal.split('-')[1] === opt.key.split('-')[1])) {
          optEl.selected = true;
          matched = true;
        }
        pkgSelect.appendChild(optEl);
      });

      if (pkgSelect.value) {
        updateFTTxPrice(pkgSelect.value);
      }
    }
  } else if (type === 'FTTB') {
    show('fttxPackageRow');
    show('contractTermRow');
    show('remoteHandsRow');
    show('otherProductNoteRow');
    currentCurrency = 'USD';
    currentBaseNRC = 0;
    currentBaseMRC = 0;

    const labelEl = document.getElementById('fttxPackageLabel');
    if (labelEl) {
      labelEl.innerHTML = 'FTTB Package Tier <span class="required">*</span>';
    }

    const pkgSelect = document.getElementById('fttxPackage');
    if (pkgSelect) {
      const options = fttxPackageOptions.FTTB || [];
      const prevVal = pkgSelect.value;

      let matched = false;
      pkgSelect.innerHTML = '<option value="">— Select Package —</option>';
      options.forEach(opt => {
        const optEl = document.createElement('option');
        optEl.value = opt.key;
        optEl.textContent = opt.label;
        if (prevVal === opt.key || (prevVal && prevVal.split('-')[1] && prevVal.split('-')[1] === opt.key.split('-')[1])) {
          optEl.selected = true;
          matched = true;
        }
        pkgSelect.appendChild(optEl);
      });
    }
  } else if (type === 'DIA') {
    show('diaRow');
    show('contractTermRow');
    show('remoteHandsRow');
    currentCurrency = 'USD';
    currentBaseNRC = 0;
    currentBaseMRC = 0;
  } else if (type === 'Layer 2 ( last mile)' || type === 'Dedicated Layer 2' || (typeof type === 'string' && type.includes('Layer 2'))) {
    show('nniRow');
    show('nniPortSizeRow');
    show('l2CapRow');
    show('contractTermRow');
    show('remoteHandsRow');
    show('otherProductNoteRow');
    currentCurrency = 'USD';
    currentBaseNRC = 0;
    currentBaseMRC = 0;
  } else if (type === 'BIA (Broadband Internet Access)') {
    show('diaRow');
    show('contractTermRow');
    show('remoteHandsRow');
    show('otherProductNoteRow');
    currentCurrency = 'USD';
    currentBaseNRC = 0;
    currentBaseMRC = 0;
  } else if (type) {
    show('contractTermRow');
    show('remoteHandsRow');
    show('otherProductNoteRow');
    currentCurrency = 'USD';
    currentBaseNRC = 0;
    currentBaseMRC = 0;
  }

  const rhSelect = document.querySelector('[name="remote_hands_required"]');
  const rhVal = rhSelect ? rhSelect.value : '0';
  const rhNRC = (rhVal === '1' || rhVal === 1) ? 35 : 0;

  setHid('hidMRCCurr', currentCurrency);
  updateNRC(currentBaseNRC, rhNRC, currentCurrency);
  updateMRC(currentBaseMRC, currentCurrency);
  if (type) {
    show('commercialSection');
  } else {
    hide('commercialSection');
  }
}

function updateNRC(base, rhNRC, curr = 'USD') {
  currentBaseNRC = base;
  currentRHNRC   = rhNRC;
  const sub  = base + rhNRC;
  const vat  = sub * VAT;
  const tot  = sub + vat;

  setTxt('baseNRCDisplay', base > 0 ? `$${fmt(base, '')}` : 'Inserted by BSA');
  setTxt('nrcSubtotal',    sub > 0 ? `$${fmt(sub, '')}` : (rhNRC > 0 ? `$${fmt(rhNRC, '')}` : 'Inserted by BSA'));
  setTxt('vatNRC',         sub > 0 ? `$${fmt(vat, '')}` : (rhNRC > 0 ? `$${fmt(vat, '')}` : '—'));
  setTxt('totalNRC',       sub > 0 ? `$${fmt(tot, '')}` : (rhNRC > 0 ? `$${fmt(tot, '')}` : 'Inserted by BSA'));

  setHid('hidBaseNRC', base.toFixed(2));
  setHid('hidRHNRC', rhNRC.toFixed(2));
  setHid('hidNRCSub', sub.toFixed(2));
  setHid('hidVatNRC', vat.toFixed(2));
  setHid('hidTotalNRC', tot.toFixed(2));

  if (rhNRC > 0) { setTxt('rhNRCDisplay', `$${fmt(rhNRC, '')}`); show('remoteHandsNRCRow'); }
  else           { hide('remoteHandsNRCRow'); }
}

function updateMRC(baseMRC, curr = 'USD') {
  currentBaseMRC = baseMRC;
  const vatMRC  = baseMRC * VAT;
  const totMRC  = baseMRC + vatMRC;

  setTxt('baseMRCDisplay', baseMRC > 0 ? `$${fmt(baseMRC, '')}` : 'Inserted by KAM');
  setTxt('vatMRC',         baseMRC > 0 ? `$${fmt(vatMRC, '')}` : '—');
  setTxt('totalMRC',       baseMRC > 0 ? `$${fmt(totMRC, '')}` : 'Inserted by KAM');

  setHid('hidBaseMRC', baseMRC.toFixed(2));
  setHid('hidDiscPct', '0.00');
  setHid('hidDiscAmt', '0.00');
  setHid('hidVatMRC',  vatMRC.toFixed(2));
  setHid('hidTotalMRC',totMRC.toFixed(2));
  hide('discountRow');
}

function updateFTTxPrice(pkg) {
  let mrc = 0;
  let nrc = 60.00;
  let curr = 'USD';

  const options = fttxPackageOptions.FTTH || [];
  const found = options.find(o => o.key === pkg || o.key.replace('FTTH-', 'FTTx-') === pkg || o.key.replace('FTTH-', '') === pkg);
  if (found) {
    mrc = found.price;
    nrc = found.nrc;
    curr = found.currency || curr;
  } else if (fttxMrcPrices[pkg]) {
    mrc = fttxMrcPrices[pkg];
  }

  currentCurrency = curr;
  const rhSelect = document.querySelector('[name="remote_hands_required"]');
  const rhVal = rhSelect ? rhSelect.value : '0';
  const rhNRC = (rhVal === '1' || rhVal === 1) ? 35 : 0;

  setHid('hidMRCCurr', curr);
  updateNRC(nrc, rhNRC, curr);
  updateMRC(mrc, curr);
}

function updateL2Price(cap) {
  // Layer 2 pricing is custom: NRC inserted by BSA, MRC inserted by KAM
}

function updateRemoteHands(val) {
  const rhNRC = (val === '1' || val === 1) ? 35 : 0;
  updateNRC(currentBaseNRC, rhNRC, currentCurrency);
}

function renderSelectedFiles(input) {
  if (typeof window.renderFileInputPreview === 'function') {
    window.renderFileInputPreview(input);
  }
}

window.renderSelectedFiles = renderSelectedFiles;

document.addEventListener('DOMContentLoaded', function() {
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  if (!dropZone || !fileInput) return;

  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.style.borderColor = 'var(--accent)';
      dropZone.style.background = 'rgba(59, 130, 246, 0.08)';
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.style.borderColor = '';
      dropZone.style.background = '';
    }, false);
  });

  dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length > 0) {
      const existing = Array.from(fileInput.files || []);
      const incoming = Array.from(dt.files);
      const dedupeFn = window.deduplicateFiles || function(arr) {
        const seen = new Set();
        return arr.filter(f => {
          const k = `${f.name}_${f.size}_${f.lastModified || 0}`;
          if (seen.has(k)) return false;
          seen.add(k);
          return true;
        });
      };

      const merged = dedupeFn([...existing, ...incoming]);
      try {
        const newDt = new DataTransfer();
        merged.forEach(f => newDt.items.add(f));
        fileInput.files = newDt.files;
      } catch (err) {}

      fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, false);
});

// ============================================================
// BSA Bill of Materials (BoM) / Cost Breakdown Functions
// ============================================================
window.onBoMCatChange = function(selectEl) {
  const row = selectEl.closest('tr');
  if (!row) return;
  const cat = selectEl.value;
  const isDev = (cat === 'Device');
  const isMat = (cat === 'Material');
  const isSvc = (cat === 'Service');
  
  const descInput = row.querySelector('.bom-desc');
  const brandInput = row.querySelector('.bom-brand');
  const modelInput = row.querySelector('.bom-model');
  const serialInput = row.querySelector('.bom-serial');
  const qtyInput = row.querySelector('.bom-qty');
  const priceInput = row.querySelector('.bom-price');
  
  if (isDev) {
    if (descInput) descInput.placeholder = 'e.g. Fiber Router, Mesh Extender, ONU';
    if (brandInput) {
      brandInput.disabled = false;
      brandInput.style.background = '#fff';
      brandInput.style.color = 'inherit';
      if (!brandInput.value || brandInput.value === '—') brandInput.value = 'FiberHome';
      brandInput.placeholder = 'FiberHome';
    }
    if (modelInput) {
      modelInput.disabled = false;
      modelInput.style.background = '#fff';
      modelInput.style.color = 'inherit';
      if (modelInput.value === '—') modelInput.value = '';
      modelInput.placeholder = 'e.g. HG6245D';
    }
    if (serialInput) {
      serialInput.disabled = false;
      serialInput.style.background = '#fff';
      serialInput.style.color = 'inherit';
      if (serialInput.value === '—') serialInput.value = '';
      serialInput.placeholder = 'SN (if available)';
    }
    if (qtyInput) {
      qtyInput.disabled = false;
      qtyInput.style.background = '#fff';
      qtyInput.style.color = 'inherit';
      if (!qtyInput.value || qtyInput.value === '0') qtyInput.value = '1';
    }
    if (priceInput) priceInput.placeholder = '0.00';
  } else if (isMat) {
    if (descInput) descInput.placeholder = 'e.g. Fiber Cable 100m, Patch Cord, OTB, Fast Connector';
    if (brandInput) {
      brandInput.disabled = true;
      brandInput.style.background = '#f1f5f9';
      brandInput.style.color = '#94a3b8';
      brandInput.value = '—';
    }
    if (modelInput) {
      modelInput.disabled = true;
      modelInput.style.background = '#f1f5f9';
      modelInput.style.color = '#94a3b8';
      modelInput.value = '—';
    }
    if (serialInput) {
      serialInput.disabled = true;
      serialInput.style.background = '#f1f5f9';
      serialInput.style.color = '#94a3b8';
      serialInput.value = '—';
    }
    if (qtyInput) {
      qtyInput.disabled = false;
      qtyInput.style.background = '#fff';
      qtyInput.style.color = 'inherit';
      if (!qtyInput.value || qtyInput.value === '0') qtyInput.value = '1';
    }
    if (priceInput) priceInput.placeholder = '0.00';
  } else {
    // Service
    if (descInput) descInput.placeholder = 'e.g. Installation Fee, Splicing, Civil Works';
    if (brandInput) {
      brandInput.disabled = true;
      brandInput.style.background = '#f1f5f9';
      brandInput.style.color = '#94a3b8';
      brandInput.value = '—';
    }
    if (modelInput) {
      modelInput.disabled = true;
      modelInput.style.background = '#f1f5f9';
      modelInput.style.color = '#94a3b8';
      modelInput.value = '—';
    }
    if (serialInput) {
      serialInput.disabled = true;
      serialInput.style.background = '#f1f5f9';
      serialInput.style.color = '#94a3b8';
      serialInput.value = '—';
    }
    if (qtyInput) {
      qtyInput.disabled = true;
      qtyInput.style.background = '#f1f5f9';
      qtyInput.style.color = '#94a3b8';
      qtyInput.value = '1';
    }
    if (priceInput) priceInput.placeholder = '0.00';
  }
  
  recalcNrcBoMTotal();
};

window.addNrcBoMRow = function(preset = null) {
  const tbody = document.getElementById('bomTableBody');
  if (!tbody) return;

  const emptyRow = document.getElementById('bomEmptyRow');
  if (emptyRow) {
    emptyRow.style.display = 'none';
  }

  const rowId = 'bom_row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
  const rawCat = preset?.category || 'Device';
  let cat = 'Device';
  if (rawCat === 'Material' || rawCat === 'Materials') cat = 'Material';
  else if (rawCat === 'Service') cat = 'Service';

  const isDev = (cat === 'Device');
  const isMat = (cat === 'Material');

  const desc = preset?.description || '';
  const brand = isDev ? (preset?.brand || preset?.brand_model || 'FiberHome') : '—';
  const model = isDev ? (preset?.model || '') : '—';
  const serial = isDev ? (preset?.serial || '') : '—';
  const qty = (isDev || isMat) ? (preset?.qty !== undefined ? preset.qty : 1) : 1;
  const unitPrice = preset?.unit_price !== undefined ? parseFloat(preset.unit_price) || 0 : 0;
  const lineTotal = (qty * unitPrice);

  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.className = 'bom-item-row';
  tr.style.borderBottom = '1px solid var(--border)';
  tr.innerHTML = `
    <td style="padding:6px 4px;vertical-align:middle">
      <select class="form-control form-control-sm bom-cat" onchange="onBoMCatChange(this)" style="padding:5px 6px;font-size:0.82rem;font-weight:600;background:#fff;border:1px solid #cbd5e1">
        <option value="Device" ${cat === 'Device' ? 'selected' : ''}>Device</option>
        <option value="Material" ${cat === 'Material' ? 'selected' : ''}>Material</option>
        <option value="Service" ${cat === 'Service' ? 'selected' : ''}>Service</option>
      </select>
    </td>
    <td style="padding:6px 4px;vertical-align:middle">
      <input type="text" class="form-control form-control-sm bom-desc" placeholder="${isDev ? 'e.g. Fiber Router, Extender, ONU' : (isMat ? 'e.g. Fiber Cable 100m, Patch Cord' : 'e.g. Installation Fee, Splicing')}" value="${escapeHtml(desc)}" oninput="recalcNrcBoMTotal()" style="padding:5px 8px;font-size:0.82rem;background:#fff;border:1px solid #cbd5e1" required>
    </td>
    <td style="padding:6px 4px;vertical-align:middle">
      <input type="text" class="form-control form-control-sm bom-brand" placeholder="FiberHome" value="${escapeHtml(brand)}" oninput="recalcNrcBoMTotal()" style="padding:5px 6px;font-size:0.82rem;background:${isDev ? '#fff' : '#f1f5f9'};color:${isDev ? 'inherit' : '#94a3b8'};border:1px solid #cbd5e1" ${!isDev ? 'disabled' : ''}>
    </td>
    <td style="padding:6px 4px;vertical-align:middle">
      <input type="text" class="form-control form-control-sm bom-model" placeholder="e.g. HG6245D" value="${escapeHtml(model)}" oninput="recalcNrcBoMTotal()" style="padding:5px 6px;font-size:0.82rem;background:${isDev ? '#fff' : '#f1f5f9'};color:${isDev ? 'inherit' : '#94a3b8'};border:1px solid #cbd5e1" ${!isDev ? 'disabled' : ''}>
    </td>
    <td style="padding:6px 4px;vertical-align:middle">
      <input type="text" class="form-control form-control-sm bom-serial" placeholder="SN (if available)" value="${escapeHtml(serial)}" oninput="recalcNrcBoMTotal()" style="padding:5px 6px;font-size:0.82rem;background:${isDev ? '#fff' : '#f1f5f9'};color:${isDev ? 'inherit' : '#94a3b8'};border:1px solid #cbd5e1" ${!isDev ? 'disabled' : ''}>
    </td>
    <td style="padding:6px 4px;vertical-align:middle;width:65px">
      <input type="number" min="1" step="1" class="form-control form-control-sm bom-qty text-center" value="${qty}" oninput="recalcNrcBoMTotal()" style="padding:5px 2px;font-size:0.82rem;background:${(isDev || isMat) ? '#fff' : '#f1f5f9'};color:${(isDev || isMat) ? 'inherit' : '#94a3b8'};border:1px solid #cbd5e1" ${(!isDev && !isMat) ? 'disabled' : ''} required>
    </td>
    <td style="padding:6px 4px;vertical-align:middle;width:135px">
      <div class="input-group input-group-sm" style="margin:0;display:flex">
        <span class="input-group-text" style="font-size:0.75rem;padding:2px 6px;background:#f1f5f9;border:1px solid #cbd5e1;border-right:none">$</span>
        <input type="text" class="form-control form-control-sm bom-price text-end currency-input" data-currency="USD" placeholder="0.00" value="${formatNumber(unitPrice)}" oninput="recalcNrcBoMTotal()" style="padding:5px 6px;font-size:0.82rem;background:#fff;border:1px solid #cbd5e1" required>
      </div>
    </td>
    <td style="padding:6px 4px;vertical-align:middle;text-align:right;font-weight:700;font-size:0.85rem;white-space:nowrap;width:130px">
      <span class="bom-line-total" style="color:var(--text-primary)">$${formatNumber(lineTotal)}</span>
    </td>
    <td style="padding:6px 4px;vertical-align:middle;text-align:center;width:36px">
      <button type="button" class="btn btn-sm btn-ghost text-danger" onclick="removeNrcBoMRow(this)" title="Remove item" style="padding:4px 6px;font-size:1.1rem;line-height:1;border:none;cursor:pointer">
        &times;
      </button>
    </td>
  `;

  tbody.appendChild(tr);
  recalcNrcBoMTotal();
};

window.removeNrcBoMRow = function(btn) {
  const row = btn.closest('tr');
  if (row) {
    row.remove();
    recalcNrcBoMTotal();
  }
};

window.recalcNrcBoMTotal = function() {
  const tbody = document.getElementById('bomTableBody');
  if (!tbody) return;

  const rows = tbody.querySelectorAll('.bom-item-row');
  const emptyRow = document.getElementById('bomEmptyRow');
  if (rows.length === 0) {
    if (emptyRow) emptyRow.style.display = '';
  } else {
    if (emptyRow) emptyRow.style.display = 'none';
  }

  const items = [];
  let grandTotal = 0;

  rows.forEach(r => {
    const cat = r.querySelector('.bom-cat')?.value || 'Device';
    const isDev = (cat === 'Device');
    const isMat = (cat === 'Material');
    const desc = r.querySelector('.bom-desc')?.value.trim() || '';
    const brand = isDev ? (r.querySelector('.bom-brand')?.value.trim() || 'FiberHome') : '';
    const model = isDev ? (r.querySelector('.bom-model')?.value.trim() || '') : '';
    const serial = isDev ? (r.querySelector('.bom-serial')?.value.trim() || '') : '';
    const qtyVal = parseInt(r.querySelector('.bom-qty')?.value, 10);
    const qty = (isDev || isMat) ? (isNaN(qtyVal) || qtyVal < 1 ? 1 : qtyVal) : 1;

    const priceRaw = r.querySelector('.bom-price')?.value || '0';
    const cleanPrice = parseFloat(priceRaw.replace(/[^0-9.]/g, '')) || 0;
    const lineTotal = qty * cleanPrice;
    grandTotal += lineTotal;

    const lineTotalEl = r.querySelector('.bom-line-total');
    if (lineTotalEl) {
      lineTotalEl.textContent = '$' + formatNumber(lineTotal);
    }

    if (desc || cleanPrice > 0) {
      items.push({
        category: cat,
        description: desc,
        brand: brand,
        model: model,
        brand_model: isDev ? (brand + ' ' + model).trim() : '',
        serial: serial,
        qty: qty,
        unit_price: cleanPrice,
        total: lineTotal
      });
    }
  });

  // Update Grand Total Display
  const grandTotalEl = document.getElementById('bomGrandTotalDisplay');
  if (grandTotalEl) {
    grandTotalEl.textContent = '$' + formatNumber(grandTotal);
  }

  // Update hidden JSON input
  const jsonInput = document.getElementById('nrcBreakdownJson');
  if (jsonInput) {
    jsonInput.value = items.length > 0 ? JSON.stringify(items) : '';
  }

  // Auto-sync with Revised NRC input field
  const revisedNrcInput = document.querySelector('input[name="revised_nrc"]');
  if (revisedNrcInput && items.length > 0) {
    revisedNrcInput.value = formatNumber(grandTotal);
    revisedNrcInput.dispatchEvent(new Event('input', { bubbles: true }));
  }

  // Auto-populate justification if empty or previously auto-generated
  const justInput = document.querySelector('input[name="nrc_justification"]');
  if (justInput && items.length > 0) {
    const isAuto = !justInput.value || justInput.dataset.autoGen === '1' || justInput.value.startsWith('BoM:');
    if (isAuto) {
      const summaryParts = items.map(it => {
        if (it.category === 'Device') {
          const devLabel = [it.description, it.brand, it.model].filter(Boolean).join(' ');
          return `${devLabel} x${it.qty}`;
        }
        if (it.category === 'Material') {
          return `${it.description} x${it.qty}`;
        }
        return `${it.description || 'Service'}`;
      });
      justInput.value = 'BoM: ' + summaryParts.join(', ');
      justInput.dataset.autoGen = '1';
    }
  }
};

function formatNumber(num) {
  return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

