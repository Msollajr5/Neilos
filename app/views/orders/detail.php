<?php
// ============================================================
// Order Detail View — v1.0
// Role-specific action panels for each lifecycle status
// ============================================================
require_once APP_DIR . '/helpers/sla.php';

$docs             = $docs ?? [];
$evidence         = $evidence ?? [];
$progressUpdates  = $progressUpdates ?? [];
$contractorList   = $contractorList ?? [];
$contractorTeams  = $contractorTeams ?? [];
$orderReturns     = $orderReturns ?? [];
$assignment       = $assignment ?? null;
$priceAudit       = $priceAudit ?? [];

$status = $order['status'] ?? 'Feasibility Review';
$isPartner = isPartnerUser();
$isBSA = hasRole('BSA');
$isKAM = hasRole('KAM');
$isMgmt = hasRole('Management');
$isPM   = hasRole('Project Manager');
$isContractor = isContractorUser();
$isAdminUser = isAdmin();
$isPMOnly = ($isPM && !$isAdminUser && !$isMgmt && !$isKAM && !$isBSA);
$canEditOrder = ($isAdminUser || $isMgmt || $isKAM || hasPermission('orders.edit'));
$isEnterpriseOrder = (
    ($order['partner_category'] ?? '') === 'Enterprise Client' ||
    ($order['partner_type'] ?? '') === 'Enterprise' ||
    ($order['partner_type'] ?? '') === 'Government'
);

// Effective MRC (management override → revised → standard)
$effectiveMrc = $order['management_approved_price'] ?? $order['revised_mrc'] ?? $order['base_mrc'] ?? 0;
$effectiveNrc = $order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd'] ?? 0;

$uatInfo = getUatCountdownInfo($order);

$stDisplay = !empty($order['service_type']) ? $order['service_type'] : '';
if (!$stDisplay) {
    if (!empty($order['fttx_package'])) {
        $stDisplay = 'FTTH';
    } elseif (!empty($order['aggregate_capacity'])) {
        $stDisplay = 'Layer 2 ( last mile)';
    } elseif (!empty($order['bandwidth'])) {
        $stDisplay = 'BIA (Broadband Internet Access)';
    } elseif ((float)($order['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($order['base_nrc_usd'] ?? 0) == 80000) {
        $stDisplay = 'Remote Hands Only';
    } else {
        $stDisplay = 'Not specified';
    }
}
$partnerDisplayName = $order['partner_name'] ?? '';
?>
<div class="page-header" data-order-detail-id="<?= $order['id'] ?>">
  <div class="page-header-left">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <div class="page-title" style="margin:0"><?= e($order['order_number']) ?></div>
      <?php if ($uatInfo['is_uat']): ?>
        <?= $uatInfo['badge_html'] ?>
      <?php endif; ?>
    </div>
    <div class="page-subtitle"><?= e($stDisplay) ?> · <?= e($order['customer_name'] ?? '') ?><?= !empty($partnerDisplayName) ? ' · ' . e($partnerDisplayName) : '' ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary"><?= svgIcon('list') ?> All Orders</a>
    <?php if ($canEditOrder): ?>
      <button type="button" class="btn btn-warning" data-modal-open="editOrderModal"><?= svgIcon('edit') ?> Edit Order Details</button>
    <?php endif; ?>
    <?php if ($isAdminUser || $isMgmt || $isPM): ?>
      <button type="button" class="btn btn-secondary" data-modal-open="statusModal"><?= svgIcon('refresh') ?> Status Override</button>
      <?php if ($isAdminUser): ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=delete" style="display:inline" data-confirm="Permanently delete order <?= e($order['order_number']) ?>?">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete Order</button>
      </form>
      <?php endif; ?>
    <?php elseif ($status === 'Closed' && !$canEditOrder): ?>
      <span class="badge badge-secondary" style="font-size:0.8rem;padding:7px 12px;display:inline-flex;align-items:center;gap:4px">🔒 Closed &amp; Locked</span>
    <?php endif; ?>
  </div>
</div>

<div class="<?= $isPartner ? 'order-detail-single-col' : 'order-detail-grid' ?> mb-24">
  <div class="card" style="margin-bottom:0">
    <div class="card-header"><div class="card-title">Order Details</div></div>
    <div class="card-body">
      <div class="order-kv-grid <?= $isPartner ? 'grid-3col' : '' ?>">
        <div class="order-kv-item">
          <span class="order-kv-label">Status :</span>
          <div class="order-kv-value"><span class="badge <?= orderStatusClass($status) ?>" style="font-size:.82rem;padding:4px 12px"><?= e($status) ?></span></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Service Type :</span>
          <div class="order-kv-value"><span class="badge badge-primary" style="font-size:.82rem;padding:4px 12px"><?= e($stDisplay) ?></span></div>
        </div>
        <?php if (!empty($order['fttx_package'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">FTTx Package :</span>
          <div class="order-kv-value"><?= e($order['fttx_package']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['bandwidth'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Bandwidth :</span>
          <div class="order-kv-value"><?= e($order['bandwidth']) ?> Mbps</div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['aggregate_capacity'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Capacity :</span>
          <div class="order-kv-value"><?= e($order['aggregate_capacity']) ?> Mbps</div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['nni_location'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">NNI / Handoff :</span>
          <div class="order-kv-value"><?= e($order['nni_location']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['nni_port_size'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">NNI Port Size :</span>
          <div class="order-kv-value"><span class="badge badge-info"><?= e($order['nni_port_size']) ?></span></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['remote_hands_required']) || (float)($order['remote_hands_nrc_usd'] ?? 0) > 0): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Remote Hands :</span>
          <div class="order-kv-value"><span class="badge badge-warning">Required (+$<?= number_format((float)($order['remote_hands_nrc_usd'] ?? 35), 2) ?> USD)</span></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['contract_term'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Minimum Service Term :</span>
          <div class="order-kv-value"><?= e($order['contract_term']) ?></div>
        </div>
        <?php endif; ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Customer Name :</span>
          <div class="order-kv-value"><?= e($order['customer_name']) ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Contact Person :</span>
          <div class="order-kv-value"><?= e($order['customer_contact_name'] ?: '—') ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Contact Phone :</span>
          <div class="order-kv-value"><?= e($order['customer_contact_phone'] ?? '—') ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Contact Email :</span>
          <div class="order-kv-value"><?= e($order['customer_contact_email'] ?? '—') ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Location :</span>
          <div class="order-kv-value"><?= e($order['customer_location'] ?? '—') ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Pickup Location :</span>
          <div class="order-kv-value">
            <?php if (!empty($order['pickup_location'])): ?>
              <span style="font-weight:600;color:var(--primary)">📍 <?= e($order['pickup_location']) ?></span>
            <?php else: ?>
              <span class="text-muted" style="font-style:italic">not updated</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($order['site_category'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Site Category :</span>
          <div class="order-kv-value"><?= e($order['site_category']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['gps_coordinates'])): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">GPS :</span>
          <div class="order-kv-value" style="font-family:monospace;font-size:0.83rem"><?= e($order['gps_coordinates']) ?></div>
        </div>
        <?php endif; ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Partner :</span>
          <div class="order-kv-value"><?= e($order['partner_name'] ?? '—') ?></div>
        </div>
        <?php
          $hasProceededToKam = !empty($order['circuit_id']) || !in_array($order['status'] ?? '', ['Feasibility Review', 'Not Feasible', 'Cancelled']);
        ?>
        <?php if ($hasProceededToKam): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Circuit ID :</span>
          <div class="order-kv-value font-mono text-primary" style="font-weight:700"><?= e($order['circuit_id'] ?: ('CKT-' . $order['order_number'])) ?></div>
        </div>
        <?php endif; ?>
        <?php 
          $installDateVal = $order['installation_date'] ?? ($assignment['completed_at'] ?? ($order['activation_date'] ?? ($order['closed_date'] ?? null)));
          $showInstallDate = !empty($installDateVal) || in_array($order['status'] ?? '', ['Testing', 'UAT', 'Closed']);
        ?>
        <?php if ($showInstallDate && !empty($installDateVal)): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Installation Date :</span>
          <div class="order-kv-value font-600" style="color:var(--primary)"><?= fmtDate($installDateVal) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($order['closed_date']) || !empty($order['billing_start_date']) || $order['status'] === 'Closed'): ?>
        <div class="order-kv-item">
          <span class="order-kv-label">Closed Date :</span>
          <div class="order-kv-value text-success font-600"><?= fmtDate($order['closed_date'] ?? null) ?></div>
        </div>
        <div class="order-kv-item">
          <span class="order-kv-label">Billing Start Date :</span>
          <div class="order-kv-value text-success font-600"><?= fmtDate($order['billing_start_date'] ?? null) ?></div>
        </div>
        <div class="order-kv-item" style="align-items:center">
          <span class="order-kv-label">Service Active Status :</span>
          <div class="order-kv-value" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <?php if (isOrderActive($order)): ?>
              <span class="badge badge-success" style="font-size:0.85rem;padding:4px 10px">● Active</span>
              <?php if ($canEditOrder || $isAdminUser || $isMgmt || $isPM || isKamUser()): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=toggle_active" style="display:inline" data-confirm="Are you sure you want to DEACTIVATE the service for customer <?= e($order['customer_name']) ?> (Order #<?= e($order['order_number']) ?>)?" data-confirm-title="Deactivate Client Service" data-confirm-btn="Deactivate" data-confirm-class="btn-danger">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="is_active" value="0">
                <input type="hidden" name="redirect_url" value="<?= APP_URL ?>/?page=order_detail&id=<?= $order['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;padding:4px 12px;font-weight:600">
                  <?= svgIcon('x-circle', 14) ?> Deactivate Client
                </button>
              </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-secondary" style="font-size:0.85rem;padding:4px 10px">● Inactive</span>
              <?php if ($canEditOrder || $isAdminUser || $isMgmt || $isPM || isKamUser()): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=toggle_active" style="display:inline" data-confirm="Are you sure you want to ACTIVATE the service for customer <?= e($order['customer_name']) ?> (Order #<?= e($order['order_number']) ?>)?" data-confirm-title="Activate Client Service" data-confirm-btn="Activate" data-confirm-class="btn-success">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="is_active" value="1">
                <input type="hidden" name="redirect_url" value="<?= APP_URL ?>/?page=order_detail&id=<?= $order['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm" style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;padding:4px 12px;font-weight:600;background:#10b981;border-color:#10b981">
                  <?= svgIcon('check-circle', 14) ?> Activate Client
                </button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($order['special_requirements'])): ?>
      <div class="divider" style="margin:16px 0 12px"></div>
      <div class="order-kv-item" style="display:block;border-bottom:none">
        <span class="order-kv-label" style="margin-bottom:6px;display:inline-flex">Special Requirements :</span>
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;font-size:.875rem;white-space:pre-wrap"><?= e($order['special_requirements']) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!$isPartner && !empty($order['bsa_technical_result'])): ?>
      <div class="divider" style="margin:16px 0 12px"></div>
      <div class="order-kv-item" style="border-bottom:none">
        <span class="order-kv-label">Technical Assessment :</span>
        <div class="order-kv-value">
          <span class="badge <?= $order['bsa_technical_result'] === 'Technically Feasible' ? 'badge-success' : 'badge-danger' ?>"><?= e($order['bsa_technical_result']) ?></span>
          <?php if (!empty($order['bsa_special_conditions'])): ?><span class="text-muted font-sm" style="margin-left:8px"><?= e($order['bsa_special_conditions']) ?></span><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!$isPartner && $order['return_reason']): ?>
      <div class="divider" style="margin:16px 0 12px"></div>
      <div class="alert alert-warning" style="margin-bottom:0">
        <strong>Returned:</strong> <?= e($order['return_reason']) ?> — <?= e($order['return_remarks']) ?>
        <?php if ($order['returned_at']): ?><div class="font-sm text-muted"><?= fmtDateTime($order['returned_at']) ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

<?php if ($isPMOnly): ?>
  <!-- Materials & Devices Used Card (PM View — Commercial pricing hidden) -->
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:0">
    <div class="card-header" style="background:var(--accent-pale);padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">
        <?= svgIcon('server', 18) ?> Materials &amp; Devices Used
      </div>
      <span class="badge badge-primary" style="font-size:0.75rem;padding:3px 8px">Technical Scope</span>
    </div>
    
    <div class="card-body" style="padding:18px">
      <?php 
        $nrcBreakdownList = !empty($order['nrc_breakdown']) ? json_decode($order['nrc_breakdown'], true) : null;
      ?>
      <div style="margin-bottom:16px">
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span>Bill of Materials &amp; Equipment</span>
          <span class="badge badge-secondary" style="font-size:0.72rem"><?= (!empty($nrcBreakdownList) && is_array($nrcBreakdownList)) ? count($nrcBreakdownList) . ' Item(s)' : 'Standard Package' ?></span>
        </div>

        <?php if (!empty($nrcBreakdownList) && is_array($nrcBreakdownList)): ?>
        <div style="background:var(--surface-2);border-radius:6px;border:1px solid var(--border);overflow:hidden">
          <table style="width:100%;font-size:0.8rem;border-collapse:collapse">
            <thead>
              <tr style="border-bottom:1px solid var(--border);background:rgba(0,0,0,0.02);color:var(--text-muted);text-align:left">
                <th style="padding:8px 10px">Category</th>
                <th style="padding:8px 10px">Item / Hardware Description</th>
                <th style="padding:8px 10px;text-align:center;width:45px">Qty</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($nrcBreakdownList as $bom): ?>
              <?php 
                $cat = $bom['category'] ?? 'Device';
                $isDev = ($cat === 'Device');
                $isMat = ($cat === 'Material' || $cat === 'Materials');
                $brandModelParts = array_filter([$bom['brand'] ?? '', $bom['model'] ?? '', $bom['brand_model'] ?? '']);
                $brandModelStr = implode(' ', array_unique($brandModelParts));
              ?>
              <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:8px 10px;vertical-align:middle">
                  <?php if ($isDev): ?>
                    <span class="badge badge-primary" style="font-size:0.68rem;padding:2px 6px">Device</span>
                  <?php elseif ($isMat): ?>
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.68rem;padding:2px 6px">Material</span>
                  <?php else: ?>
                    <span class="badge badge-secondary" style="font-size:0.68rem;padding:2px 6px">Service</span>
                  <?php endif; ?>
                </td>
                <td style="padding:8px 10px;vertical-align:middle">
                  <div style="font-weight:600;color:var(--text-primary)"><?= e($bom['description'] ?? 'Item') ?></div>
                  <?php if ($brandModelStr): ?>
                    <div class="text-muted" style="font-size:0.75rem"><?= e($brandModelStr) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($bom['serial'])): ?>
                    <div style="font-size:0.72rem;margin-top:2px">SN: <code><?= e($bom['serial']) ?></code></div>
                  <?php endif; ?>
                </td>
                <td style="padding:8px 10px;text-align:center;vertical-align:middle;font-weight:700"><?= (int)($bom['qty'] ?? 1) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div style="padding:12px;background:var(--surface-2);border-radius:6px;border:1px solid var(--border);font-size:0.85rem;color:var(--text-secondary)">
          Standard installation materials and customer-premises equipment (ONT/ONU/CPE) allocated according to <strong><?= e($stDisplay) ?></strong> specifications.
        </div>
        <?php endif; ?>
      </div>

      <!-- Technical Specifications Summary -->
      <div style="padding-top:12px;border-top:1px dashed var(--border);display:flex;flex-direction:column;gap:8px;font-size:0.85rem">
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-secondary)">Service Profile:</span>
          <span style="font-weight:600;color:var(--text-primary)"><?= e($stDisplay) ?></span>
        </div>
        <?php if (!empty($order['fttx_package']) || !empty($order['bandwidth']) || !empty($order['aggregate_capacity'])): ?>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-secondary)">Capacity / Plan:</span>
          <span style="font-weight:600;color:var(--text-primary)"><?= e($order['fttx_package'] ?: ($order['bandwidth'] ? $order['bandwidth'].' Mbps' : ($order['aggregate_capacity'] ? $order['aggregate_capacity'].' Mbps' : 'Standard'))) ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-secondary)">Installation Location:</span>
          <span style="font-weight:600;color:var(--text-primary);text-align:right"><?= e($order['customer_location'] ?: '—') ?></span>
        </div>
        <div style="display:flex;justify-content:space-between">
          <span style="color:var(--text-secondary)">Confirmed Pickup Site:</span>
          <span style="font-weight:600;color:var(--primary);text-align:right">
            <?= !empty($order['pickup_location']) ? '📍 ' . e($order['pickup_location']) : '<em class="text-muted">Pending confirmation</em>' ?>
          </span>
        </div>
      </div>
    </div>
  </div>
<?php elseif (!$isPartner && $status !== 'Management Approval' && !$isPMOnly): ?>
  <!-- Commercial Summary Card (Internal Only — BSA, KAM, Mgmt, Admin) -->
  <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:0">
    <div class="card-header" style="background:var(--accent-pale);padding:14px 20px;border-bottom:1px solid var(--border)">
      <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">
        <?= svgIcon('chart', 18) ?> Commercial Summary
      </div>
    </div>
    
    <div class="card-body" style="padding:18px">
      <?php 
        $comm = getOrderCommercialSummary($order);
        $mrcCurr = $order['mrc_currency'] ?: 'USD';
        $currSymbol = ($mrcCurr === 'USD') ? '$' : $mrcCurr . ' ';
      ?>
      
      <!-- 1. NRC — One-Time Charges -->
      <div style="margin-bottom:18px;padding-bottom:16px;border-bottom:1px dashed var(--border)">
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span>NRC — One-Time Charges</span>
          <span style="font-size:0.72rem;font-weight:500;text-transform:none">Non-Recurring</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;font-size:0.88rem">
          <?php if (!$comm['has_rev_nrc'] && $comm['std_nrc'] > 0): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Standard NRC</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right"><?= $currSymbol ?><?= money($comm['std_nrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($comm['has_rev_nrc']): ?>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-muted);text-decoration:line-through">Standard NRC (<?= $currSymbol ?><?= money($comm['std_nrc']) ?>)</span>
              <span class="badge badge-secondary" style="font-size:0.7rem">Replaced</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-primary);font-weight:600">Revised NRC</span>
              <span style="font-weight:700;color:var(--accent);text-align:right"><?= $currSymbol ?><?= money($comm['rev_nrc']) ?></span>
            </div>
            <?php if (!empty($order['nrc_justification'])): ?>
            <div style="font-size:0.78rem;color:var(--text-muted);background:var(--surface-2);padding:4px 8px;border-radius:4px;margin-top:2px">
              <strong>Justification:</strong> <?= e($order['nrc_justification']) ?>
            </div>
            <?php endif; ?>
            <?php 
              $nrcBreakdownList = !empty($order['nrc_breakdown']) ? json_decode($order['nrc_breakdown'], true) : null;
              if (!empty($nrcBreakdownList) && is_array($nrcBreakdownList)):
            ?>
            <div style="margin-top:8px;padding:8px 10px;background:var(--surface-2);border-radius:6px;border:1px solid var(--border)">
              <div style="font-weight:700;font-size:0.78rem;color:var(--text-primary);margin-bottom:6px;display:flex;align-items:center;justify-content:space-between">
                <span><?= svgIcon('server', 12) ?> BoM Itemized Breakdown</span>
                <span class="badge badge-primary" style="font-size:0.68rem;padding:2px 6px"><?= count($nrcBreakdownList) ?> Items</span>
              </div>
              <table style="width:100%;font-size:0.75rem;border-collapse:collapse">
                <thead>
                  <tr style="border-bottom:1px solid var(--border);color:var(--text-muted);text-align:left">
                    <th style="padding:3px 2px">Item / Material / Service Details</th>
                    <th style="padding:3px 2px;text-align:center;width:35px">Qty</th>
                    <th style="padding:3px 2px;text-align:right">Unit Price</th>
                    <th style="padding:3px 2px;text-align:right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($nrcBreakdownList as $bom): ?>
                  <?php 
                    $cat = $bom['category'] ?? 'Device';
                    $isDev = ($cat === 'Device');
                    $isMat = ($cat === 'Material' || $cat === 'Materials');
                    $isSvc = (!$isDev && !$isMat);
                    $brandModelParts = array_filter([$bom['brand'] ?? '', $bom['model'] ?? '', $bom['brand_model'] ?? '']);
                    $brandModelStr = implode(' ', array_unique($brandModelParts));
                  ?>
                  <tr style="border-bottom:1px solid rgba(0,0,0,0.04)">
                    <td style="padding:4px 2px">
                      <?php if ($isDev): ?>
                        <span class="badge badge-primary" style="font-size:0.65rem;padding:1px 5px;margin-right:3px">Device</span>
                      <?php elseif ($isMat): ?>
                        <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:0.65rem;padding:1px 5px;margin-right:3px">Material</span>
                      <?php else: ?>
                        <span class="badge badge-secondary" style="font-size:0.65rem;padding:1px 5px;margin-right:3px">Service</span>
                      <?php endif; ?>
                      <span style="font-weight:600;color:var(--text-primary)"><?= e($bom['description'] ?? 'Item') ?></span>
                      <?php if ($isDev && $brandModelStr): ?>
                        <span class="text-muted" style="font-size:0.72rem">(<?= e($brandModelStr) ?>)</span>
                      <?php endif; ?>
                      <?php if ($isDev && !empty($bom['serial'])): ?>
                        <div class="text-muted" style="font-size:0.7rem;margin-left:2px">SN: <code><?= e($bom['serial']) ?></code></div>
                      <?php endif; ?>
                    </td>
                    <td style="padding:4px 2px;text-align:center"><?= (int)($bom['qty'] ?? 1) ?></td>
                    <td style="padding:4px 2px;text-align:right"><?= $currSymbol ?><?= number_format((float)($bom['unit_price'] ?? 0), 2) ?></td>
                    <td style="padding:4px 2px;text-align:right;font-weight:600"><?= $currSymbol ?><?= number_format((float)($bom['total'] ?? 0), 2) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($comm['rh_nrc'] > 0): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Remote Hands NRC</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right"><?= $currSymbol ?><?= money($comm['rh_nrc']) ?></span>
          </div>
          <?php endif; ?>

          <!-- Total NRC (One-Time) -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:rgba(16, 185, 129, 0.08);border:1px solid rgba(16, 185, 129, 0.3);border-radius:var(--radius-md);margin-top:6px">
            <span style="font-weight:700;color:#065F46">Total NRC (One-Time)</span>
            <span style="font-weight:800;font-size:1.05rem;color:#065F46;text-align:right"><?= $currSymbol ?><?= money($comm['total_nrc_net']) ?></span>
          </div>
        </div>
      </div>

      <!-- 2. MRC — Recurring Charges -->
      <div>
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span>MRC — Recurring Charges</span>
          <span style="font-size:0.72rem;font-weight:500;text-transform:none">Monthly</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;font-size:0.88rem">
          <?php if (!$comm['has_rev_mrc'] && !$comm['has_mgmt_mrc']): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Standard MRC (<?= e($mrcCurr) ?>)</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right"><?= $currSymbol ?><?= money($comm['std_mrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($comm['has_rev_mrc'] && !$comm['has_mgmt_mrc']): ?>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-muted);text-decoration:line-through">Standard MRC (<?= $currSymbol ?><?= money($comm['std_mrc']) ?>)</span>
              <span class="badge badge-secondary" style="font-size:0.7rem">Replaced</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-primary);font-weight:600">Proposed Exception MRC</span>
              <span style="font-weight:700;color:var(--accent);text-align:right"><?= $currSymbol ?><?= money($comm['rev_mrc']) ?></span>
            </div>
          <?php endif; ?>

          <?php if ($comm['has_mgmt_mrc']): ?>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-muted);text-decoration:line-through">Standard MRC (<?= $currSymbol ?><?= money($comm['std_mrc']) ?>)</span>
              <span class="badge badge-secondary" style="font-size:0.7rem">Overridden</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="color:var(--text-primary);font-weight:600">Management Approved Price</span>
              <span style="font-weight:700;color:#10B981;text-align:right"><?= $currSymbol ?><?= money($comm['mgmt_mrc']) ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($order['mrc_justification'])): ?>
          <div style="font-size:0.78rem;color:var(--text-muted);background:var(--surface-2);padding:4px 8px;border-radius:4px;margin-top:2px">
            <strong>MRC Justification:</strong> <?= e($order['mrc_justification']) ?>
          </div>
          <?php endif; ?>

          <?php if ($order['management_remarks'] && $order['management_remarks_visible']): ?>
          <div style="font-size:0.78rem;background:var(--surface-2);border-radius:4px;padding:6px 8px;margin-top:2px">
            <strong>Management note:</strong> <?= e($order['management_remarks']) ?>
          </div>
          <?php endif; ?>

          <!-- Total Monthly MRC -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:rgba(15, 76, 129, 0.08);border:1px solid rgba(15, 76, 129, 0.3);border-radius:var(--radius-md);margin-top:6px">
            <span style="font-weight:700;color:#0F4C81">Total Monthly MRC</span>
            <span style="font-weight:800;font-size:1.05rem;color:#0F4C81;text-align:right"><?= $currSymbol ?><?= money($comm['total_mrc_net']) ?></span>
          </div>
        </div>
      </div>

      <!-- Combined Contract Total -->
      <div style="margin-top:16px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.85rem;font-weight:700;color:var(--text-primary)">Total First Payment (NRC + 1st Month MRC)</span>
        <span style="font-size:1.05rem;font-weight:800;color:var(--text-primary);text-align:right"><?= $currSymbol ?><?= money($comm['total_first_payment_net']) ?></span>
      </div>

    </div>
  </div>
<?php endif; // !$isPartner ?>
</div>

<?php
// Authoritative Partner KYC for Billing Information
$orderPartnerKyc = getAuthoritativePartnerKyc($db, (int)$order['partner_id']) ?: [];
?>
<!-- Dedicated Billing Information & Billing Cycle Card -->
<div class="card mb-24" style="border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden">
  <div class="card-header" style="background:var(--accent-pale);padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
    <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">
      <?= svgIcon('credit-card', 18) ?> Billing Information &amp; Billing Cycle
    </div>
    <span class="badge badge-info" style="font-size:0.75rem;padding:3px 8px">USD ($) Standard</span>
  </div>
  <div class="card-body" style="padding:18px">
    <div class="order-kv-grid grid-3col">
      <div class="order-kv-item">
        <span class="order-kv-label">Billing Cycle :</span>
        <div class="order-kv-value font-600">Monthly in Advance</div>
      </div>
      <div class="order-kv-item">
        <span class="order-kv-label">Payment Terms :</span>
        <div class="order-kv-value font-600">Net 30 Days</div>
      </div>
      <div class="order-kv-item">
        <span class="order-kv-label">Billing Currency :</span>
        <div class="order-kv-value font-700 text-primary">USD ($)</div>
      </div>
      <div class="order-kv-item">
        <span class="order-kv-label">Billing Contact Person :</span>
        <div class="order-kv-value"><?= e($orderPartnerKyc['billing_contact_name'] ?? ($order['customer_contact_name'] ?: '—')) ?></div>
      </div>
      <div class="order-kv-item">
        <span class="order-kv-label">Billing Contact Email :</span>
        <div class="order-kv-value font-sm"><?= e($orderPartnerKyc['billing_contact_email'] ?? ($order['customer_contact_email'] ?: '—')) ?></div>
      </div>
      <div class="order-kv-item">
        <span class="order-kv-label">Billing Start Date :</span>
        <div class="order-kv-value">
          <?php if (!empty($order['billing_start_date'])): ?>
            <span class="text-success font-700">🗓️ <?= fmtDate($order['billing_start_date']) ?></span>
          <?php else: ?>
            <span class="text-muted font-sm" style="font-style:italic">Effective upon UAT completion</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php // ============================================================
// STATUS PROGRESS BAR (LIFECYCLE PIPELINE)
// ============================================================ ?>
<?php
$statusSteps = ['Feasibility Review','Await Commercial Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed'];
$currentIdx = array_search($status, $statusSteps);
?>
<div class="card mb-24">
  <div class="card-header" style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2)">
    <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
      <?= svgIcon('activity', 15) ?> Lifecycle Fulfillment Pipeline
    </div>
    <span class="badge <?= orderStatusClass($status) ?>" style="font-size:0.76rem;padding:3px 8px"><?= e($status) ?></span>
  </div>
  <div class="card-body" style="padding:18px 20px;overflow-x:auto">
    <div class="order-steps-scroll-wrapper">
      <?php foreach ($statusSteps as $idx => $step): ?>
      <?php
        if ($status === 'Closed') {
            $isDone    = true;
            $isCurrent = false;
        } else {
            $isDone    = $currentIdx !== false && $idx < $currentIdx;
            $isCurrent = $currentIdx !== false && $idx === $currentIdx;
        }
        $cls = $isDone ? 'step-done' : ($isCurrent ? 'step-current' : 'step-pending');
      ?>
      <div class="order-step <?= $cls ?>">
        <div class="order-step-dot"><?= $isDone ? svgIcon('check', 14) : ($idx + 1) ?></div>
        <div class="order-step-label"><?= e($step) ?></div>
      </div>
      <?php if ($idx < count($statusSteps) - 1): ?>
      <div class="order-step-connector <?= ($status === 'Closed' || ($currentIdx !== false && $idx < $currentIdx)) ? 'done' : '' ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($order['sla_paused'])): ?>
    <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div>
        <strong>⏸ SLA Clock Paused:</strong> A contractor blocker has been posted on this order. (Paused since <?= fmtDateTime($order['sla_paused_at']) ?> · Accumulated: <?= (float)$order['sla_paused_hours'] ?> hrs)
      </div>
      <span class="badge badge-warning" style="font-size:0.8rem">SLA Clock Paused</span>
    </div>
    <?php endif; ?>
    <?php if ($status === 'Not Feasible'): ?>
    <div class="alert alert-danger" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('x') ?> <strong>Not Feasible:</strong> <?= e($order['bsa_not_feasible_reason']) ?>
    </div>
    <?php elseif ($status === 'Management Approval'): ?>
    <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('users') ?> This order is awaiting <strong>Management Exception Approval</strong> (KAM Proposed NRC: <?= $order['kam_proposed_nrc'] !== null ? '$' . number_format((float)$order['kam_proposed_nrc'], 2) : 'Standard' ?>, MRC: <?= $order['kam_proposed_mrc'] !== null ? '$' . number_format((float)$order['kam_proposed_mrc'], 2) : 'Standard' ?>).
    </div>
    <?php endif; ?>
  </div>
</div>

<?php // ============================================================
// ROLE-SPECIFIC ACTION PANELS
// ============================================================ ?>

<?php // === PARTNER: INFORMATIVE PANELS FOR EARLY STAGES === ?>
<?php if ($status === 'Feasibility Review' && $isPartner): ?>
<div class="card" style="margin-bottom:24px;border:1px solid var(--border)">
  <div class="card-header" style="background:rgba(var(--primary-rgb,99,102,241),.06)">
    <div class="card-title"><?= svgIcon('search') ?> Technical Feasibility in Progress</div>
    <div class="card-subtitle">Our engineering team is assessing site coordinates, fibre path availability, and infrastructure feasibility.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:0">
      <?= svgIcon('info') ?> Initial site survey and route verification are underway. Once finalized, confirmed NRC and technical feasibility status will be updated here.
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (in_array($status, ['Await Commercial Approval', 'Management Approval']) && $isPartner): ?>
<div class="card" style="margin-bottom:24px;border:1px solid var(--border)">
  <div class="card-header" style="background:rgba(var(--warning-rgb,245,158,11),.08)">
    <div class="card-title"><?= svgIcon('dollar') ?> Commercial Review in Progress</div>
    <div class="card-subtitle">Technical feasibility is confirmed. Your account manager is preparing and finalizing commercial terms.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:0">
      <?= svgIcon('clock') ?> Your order is under commercial review. As soon as pricing is confirmed, you will be prompted here to generate and sign your Service Order Form (SOF).
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($status === 'Testing' && $isPartner): ?>
<div class="card" style="margin-bottom:24px;border:1px solid var(--border)">
  <div class="card-header" style="background:rgba(var(--primary-rgb,99,102,241),.06)">
    <div class="card-title"><?= svgIcon('check') ?> Testing &amp; Quality Verification</div>
    <div class="card-subtitle">Installation work is complete and line testing is underway.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:0">
      <?= svgIcon('info') ?> IP routing and line parameters are being tested by our NOC. You will be notified as soon as the link is released for your UAT (User Acceptance Testing) sign-off.
    </div>
  </div>
</div>
<?php endif; ?>

<?php // ============================================================
// ROLE-SPECIFIC ACTION PANELS
// ============================================================ ?>

<?php // === BSA / PM: FEASIBILITY REVIEW PANEL === ?>
<?php if ($status === 'Feasibility Review' && ($isBSA || $isAdminUser || $isPM)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning)">
  <div class="card-header" style="background:rgba(var(--warning-rgb,255,171,64),.12);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div class="card-title"><?= svgIcon('search') ?> BSA &amp; Project Feasibility Review</div>
      <div class="card-subtitle">Review technical feasibility, dispatch field site surveys, and confirm NRC.</div>
    </div>
    <a href="<?= APP_URL ?>/?page=surveys&tab=surveys" class="btn btn-sm btn-primary" style="font-weight:700">
      🛰️ Assign Field Survey to Team
    </a>
  </div>
  <div class="card-body">
    <?php if (!empty($orderSurveys)): ?>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:14px;margin-bottom:18px">
      <div style="font-weight:700;color:#0369a1;font-size:0.9rem;display:flex;align-items:center;gap:6px;margin-bottom:8px">
        <span>🛰️</span> Field Site Surveys Allocated for this Order (<?= count($orderSurveys) ?>)
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($orderSurveys as $os): ?>
        <div style="background:#fff;border:1px solid #e0f2fe;border-radius:6px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div>
            <div style="font-weight:700;font-size:0.875rem">
              <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $os['id'] ?>" style="color:var(--primary);text-decoration:none">
                <?= e($os['survey_number']) ?>
              </a>
              · Team: <strong>👷 <?= e($os['team_name']) ?></strong> (Lead: <?= e($os['leader_name'] ?: 'N/A') ?>)
            </div>
            <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:2px">
              Status: <span class="badge" style="font-size:0.7rem"><?= e($os['status']) ?></span>
              <?php if (!empty($os['feasibility_result'])): ?>
                · Verdict: <strong><?= e($os['feasibility_result']) ?></strong>
                <?php if ($os['fiber_distance_meters']): ?> · Distance: <?= number_format($os['fiber_distance_meters'], 1) ?>m<?php endif; ?>
                <?php if ($os['nearest_dp_or_splitter']): ?> · DP: <?= e($os['nearest_dp_or_splitter']) ?><?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <a href="<?= APP_URL ?>/?page=surveys&action=detail&id=<?= $os['id'] ?>" class="btn btn-secondary btn-sm" style="font-weight:600">
            <?= svgIcon('eye', 12) ?> View Survey Findings
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($order['return_remarks']): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
      <strong>Partner concern:</strong> <?= e($order['return_remarks']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_feasible" id="bsaFeasibilityForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Standard NRC (USD)</label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">$</span>
            <input type="text" class="form-control" value="<?= number_format((float)($order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?>" disabled style="opacity:.7">
          </div>
        </div>
        <div class="form-group">
          <label>Revised NRC (USD) <span class="text-muted font-sm">Calculated from BoM or direct USD</span></label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">$</span>
            <input type="text" name="revised_nrc" class="form-control currency-input" data-currency="USD" value="<?= $order['revised_nrc'] !== null ? number_format((float)$order['revised_nrc'], 2) : '' ?>" placeholder="e.g. 60.00">
          </div>
        </div>
        <div class="form-group">
          <label>NRC Justification <span class="required">*</span> <span class="text-muted font-sm">Mandatory if NRC changes</span></label>
          <input type="text" name="nrc_justification" class="form-control" value="<?= e($order['nrc_justification'] ?? '') ?>" placeholder="e.g. Extended fibre route 350m / BoM breakdown">
        </div>
      </div>

      <!-- Bill of Materials (BoM) / Itemized Cost Breakdown -->
      <div style="margin:20px 0 16px 0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;background:var(--surface)">
        <div style="padding:12px 16px;background:var(--surface-2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <div>
            <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
              <?= svgIcon('server', 15) ?> Itemized Cost Breakdown / Bill of Materials (BoM)
            </div>
            <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:2px">
              Click <strong>+ Add Device</strong> (equipment with brand/model/serial), <strong>+ Add Material</strong> (materials with qty &amp; price), or <strong>+ Add Service</strong> (service fee).
            </div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="btn btn-sm btn-primary" onclick="addNrcBoMRow({category:'Device',description:'',brand:'FiberHome',model:'',serial:'',qty:1,unit_price:0})" style="font-weight:600">
              <?= svgIcon('plus', 14) ?> Add Device
            </button>
            <button type="button" class="btn btn-sm btn-info" onclick="addNrcBoMRow({category:'Material',description:'',brand:'',model:'',serial:'',qty:1,unit_price:0})" style="font-weight:600;background:#0284c7;color:#fff;border-color:#0284c7">
              <?= svgIcon('plus', 14) ?> Add Material
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addNrcBoMRow({category:'Service',description:'',brand:'',model:'',serial:'',qty:1,unit_price:0})" style="font-weight:600">
              <?= svgIcon('plus', 14) ?> Add Service
            </button>
          </div>
        </div>

        <div style="overflow-x:auto;padding:8px">
          <table class="data-table" style="width:100%;min-width:780px;font-size:0.85rem">
            <thead>
              <tr style="background:var(--surface-hover)">
                <th style="width:125px">Category <span class="text-danger">*</span></th>
                <th>Item / Material / Service Name <span class="text-danger">*</span></th>
                <th style="width:130px">Brand</th>
                <th style="width:120px">Model</th>
                <th style="width:130px">Serial #</th>
                <th style="width:65px;text-align:center">Qty</th>
                <th style="width:135px;text-align:right">Price ($)</th>
                <th style="width:130px;text-align:right">Total ($)</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="bomTableBody">
              <tr id="bomEmptyRow">
                <td colspan="9" style="text-align:center;padding:22px 16px;color:var(--text-muted);background:var(--surface-2)">
                  <div style="font-size:1.1rem;margin-bottom:4px">📦</div>
                  <div style="font-weight:600;font-size:0.86rem;color:var(--text-secondary)">No Breakdown Items Added Yet</div>
                  <div style="font-size:0.78rem;margin-top:2px">Click <strong>+ Add Device</strong>, <strong>+ Add Material</strong>, or <strong>+ Add Service</strong> to build your itemized cost breakdown.</div>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr style="font-weight:700;background:var(--surface-2);border-top:2px solid var(--border)">
                <td colspan="7" style="text-align:right;padding:10px 12px;font-size:0.9rem">
                  Calculated BoM Total (Auto-applied to Revised NRC):
                </td>
                <td style="text-align:right;padding:10px 12px;font-size:0.95rem;color:var(--accent)" id="bomGrandTotalDisplay">
                  $0.00
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <input type="hidden" name="nrc_breakdown_json" id="nrcBreakdownJson" value="<?= e($order['nrc_breakdown'] ?? '') ?>">

      <script>
      (function() {
        function escapeHtml(text) {
          if (!text) return '';
          return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        function formatNumber(num) {
          return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

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
          const unitPrice = preset?.unit_price !== undefined ? preset.unit_price : 0;
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
                brand_model: isDev ? trim(brand + ' ' + model) : '',
                serial: serial,
                qty: qty,
                unit_price: cleanPrice,
                total: lineTotal
              });
            }
          });

          function trim(s) { return s.trim(); }

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

          // Auto-populate justification if empty or auto-generated
          const justInput = document.querySelector('input[name="nrc_justification"]');
          if (justInput && items.length > 0) {
            const isAuto = !justInput.value || justInput.dataset.autoGen === '1' || justInput.value.startsWith('BoM:');
            if (isAuto) {
              const summaryParts = items.map(it => {
                if (it.category === 'Device') {
                  const devLabel = [it.description, it.brand, it.model].filter(Boolean).join(' ');
                  return `${devLabel} x${it.qty}`;
                } else if (it.category === 'Material') {
                  return `${it.description || 'Material'} x${it.qty}`;
                }
                return `${it.description || 'Service'}`;
              });
              justInput.value = 'BoM: ' + summaryParts.join(', ');
              justInput.dataset.autoGen = '1';
            }
          }
        };

        // Initialize preloaded data or blank state
        document.addEventListener('DOMContentLoaded', function() {
          try {
            const rawJson = document.getElementById('nrcBreakdownJson')?.value;
            if (rawJson) {
              const preloaded = JSON.parse(rawJson);
              if (Array.isArray(preloaded) && preloaded.length > 0) {
                preloaded.forEach(item => addNrcBoMRow(item));
              }
            }
          } catch (e) {
            console.error("Error preloading BoM:", e);
          }
        });
      })();
      </script>

      <div class="form-group" style="margin-top:8px">
        <label>Technical Remarks <span class="required">*</span></label>
        <textarea name="technical_remarks" id="bsa_technical_remarks" class="form-control" rows="3" placeholder="Describe build requirement, distance, infrastructure conditions..." required><?= e($order['bsa_special_conditions'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="button" class="btn btn-secondary" onclick="previewBsaSubmission()"><?= svgIcon('eye') ?> Preview Feasibility</button>
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Mark Technically Feasible &amp; Proceed to KAM</button>
      </div>
    </form>

    <div class="divider" style="margin:16px 0"></div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_not_feasible" id="bsaNotFeasibleForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-group">
        <label>Not Feasible Reason <span class="required">*</span></label>
        <textarea name="not_feasible_reason" id="not_feasible_reason_input" class="form-control" rows="2" placeholder="Mandatory reason why this is not technically feasible..."></textarea>
      </div>
      <button type="button" class="btn btn-danger" id="bsaNotFeasibleBtn"><?= svgIcon('x') ?> Technically Not Feasible</button>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const notFeasibleBtn = document.getElementById('bsaNotFeasibleBtn');
      const notFeasibleForm = document.getElementById('bsaNotFeasibleForm');
      const reasonInput = document.getElementById('not_feasible_reason_input');

      if (notFeasibleBtn && notFeasibleForm) {
        notFeasibleBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const reason = reasonInput ? reasonInput.value.trim() : '';
          if (!reason) {
            neilosAlert('Please enter a mandatory reason why this order is technically not feasible.', 'Reason Required', '⚠️');
            reasonInput?.focus();
            return;
          }

          neilosConfirm(
            'This will notify the partner that this order has been marked as technically not feasible.',
            'Mark as Technically Not Feasible?',
            'Mark as Not Feasible',
            'btn-danger',
            '🚫'
          ).then(function(confirmed) {
            if (confirmed) {
              notFeasibleForm.submit();
            }
          });
        });
      }
    });
    </script>
  </div>
</div>
<?php endif; ?>

<?php // === KAM: COMMERCIAL APPROVAL PANEL === ?>
<?php if ($status === 'Await Commercial Approval' && ($isKAM || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning)">
  <div class="card-header" style="background:rgba(var(--warning-rgb,255,171,64),.12)">
    <div class="card-title"><?= svgIcon('dollar') ?> KAM Commercial Review</div>
    <div class="card-subtitle">Define customer-specific currency exchange rate (TZS/USD), verify pricing, or propose commercial exception for Management approval.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:16px">
      <?= svgIcon('info') ?> <strong>NRC confirmed by BSA:</strong> $<?= number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?>
      <?php if (!empty($order['management_return_remarks'])): ?>
        <br><strong>⚠️ Management Return Note:</strong> <?= e($order['management_return_remarks']) ?>
      <?php endif; ?>
      <?php if (!empty($order['sof_return_comments'])): ?>
        <br><strong>⚠️ Partner Return Note:</strong> <?= e($order['sof_return_comments']) ?>
      <?php endif; ?>
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=kam_approve" id="kamApproveForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

      <div class="form-grid form-grid-2" style="margin-bottom:16px">
        <div class="form-group">
          <label>Exchange Rate (TZS per 1 USD) <span class="required">*</span></label>
          <div class="input-group currency-input-group">
            <span class="input-group-text" style="font-weight:700;font-size:0.8rem;background:var(--surface-2)">TZS</span>
            <input type="number" step="0.01" min="1" name="usd_tzs_rate" id="usd_tzs_rate_input" class="form-control" value="<?= htmlspecialchars((string)($order['usd_tzs_rate'] ?? '2600.00')) ?>" placeholder="e.g. 2600.00" required>
          </div>
          <div class="form-hint" style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Customer-specific conversion rate for this order (forwarded to Management).</div>
        </div>
        <div class="form-group">
          <label>Proposed Exception MRC (USD) <span class="text-muted font-sm">Leave blank for standard</span></label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">$</span>
            <input type="text" name="kam_proposed_mrc" id="kam_proposed_mrc_input" class="form-control currency-input" data-currency="USD" value="<?= $order['kam_proposed_mrc'] !== null ? number_format((float)$order['kam_proposed_mrc'], 2) : '' ?>" placeholder="e.g. 15.00">
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Proposed Exception NRC (USD) <span class="text-muted font-sm">Only if proposing different from BSA's NRC</span></label>
        <div class="input-group currency-input-group">
          <span class="input-group-text currency-badge">$</span>
          <input type="text" name="kam_proposed_nrc" id="kam_proposed_nrc_input" class="form-control currency-input" data-currency="USD" value="<?= $order['kam_proposed_nrc'] !== null ? number_format((float)$order['kam_proposed_nrc'], 2) : '' ?>" placeholder="Leave blank to use BSA confirmed NRC">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Commercial Justification <span class="text-muted font-sm">Mandatory if proposing pricing exception</span></label>
        <input type="text" name="kam_commercial_justification" id="kam_commercial_justification_input" class="form-control" value="<?= e($order['kam_commercial_justification'] ?? '') ?>" placeholder="e.g. Volume discount for 50-site deal approved by sales head">
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>General KAM Remarks</label>
        <textarea name="kam_remarks" id="kam_remarks_input" class="form-control" rows="2" placeholder="Optional notes..."><?= e($order['kam_remarks'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button type="button" class="btn btn-secondary" onclick="previewKamSubmission()"><?= svgIcon('eye') ?> Preview Decision</button>
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Submit Commercial Decision</button>
      </div>
      <div class="form-hint" style="margin-top:8px">
        <strong>Note:</strong> Standard pricing moves order directly to <strong>Pending SOF</strong>. Discounted pricing is automatically routed to <strong>Management Approval</strong>.
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === MANAGEMENT APPROVAL PANEL (3-Option Decision Model) === ?>
<?php if ($status === 'Management Approval' && ($isMgmt || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--danger)">
  <div class="card-header" style="background:rgba(220,53,69,.1)">
    <div class="card-title"><?= svgIcon('users') ?> Management Approval — Pricing Exception Review</div>
    <div class="card-subtitle">Review KAM's pricing exception proposal and make an authoritative determination.</div>
  </div>
  <div class="card-body">
    <div class="grid-2col" style="gap:16px;margin-bottom:16px;padding:12px;background:var(--surface-2);border-radius:6px">
      <div>
        <div style="font-weight:700;margin-bottom:6px">Price Comparison (USD)</div>
        <div style="font-size:0.875rem;line-height:1.6">
          <strong>Currency Exchange Rate:</strong> <span class="font-600" style="color:var(--primary)">TZS <?= number_format((float)($order['usd_tzs_rate'] ?? 2600), 2) ?> / USD</span><br>
          <strong>BSA Confirmed NRC:</strong> $<?= number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?><br>
          <strong>KAM Proposed NRC:</strong> <span class="text-danger font-600"><?= $order['kam_proposed_nrc'] !== null ? '$' . number_format((float)$order['kam_proposed_nrc'], 2) : '$' . number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?></span><br>
          <strong>KAM Proposed MRC:</strong> <span class="text-danger font-600"><?= $order['kam_proposed_mrc'] !== null ? '$' . number_format((float)$order['kam_proposed_mrc'], 2) : '$' . number_format((float)($order['standard_mrc'] ?? $order['base_mrc']), 2) ?></span>
        </div>
      </div>
      <div>
        <div style="font-weight:700;margin-bottom:6px">Exception Justification</div>
        <div style="font-size:0.875rem;color:var(--text-secondary)">
          <?= e($order['kam_commercial_justification'] ?: ($order['mrc_justification'] ?: 'No justification provided.')) ?>
        </div>
      </div>
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=management_decide" id="mgmtDecisionForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label" style="font-weight:700">Select Management Decision <span class="required">*</span></label>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:6px">
          <!-- Option 1 -->
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:6px;background:var(--surface)">
            <input type="radio" name="management_decision" value="Approve as Requested" required onchange="toggleMgmtFields(this.value)">
            <div>
              <strong>1. Approve as Requested</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Accept KAM's proposed exception pricing and proceed to Pending SOF.</div>
            </div>
          </label>

          <!-- Option 2 & Nested Override Inputs -->
          <div style="border:1px solid var(--border);border-radius:6px;background:var(--surface);overflow:hidden">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;margin:0">
              <input type="radio" name="management_decision" value="Approve with Revised Price" required onchange="toggleMgmtFields(this.value)">
              <div>
                <strong>2. Approve with Revised Price</strong>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Set custom management-approved NRC and/or MRC and proceed to Pending SOF.</div>
              </div>
            </label>
            <div id="mgmtRevisedPriceFields" style="display:none;padding:14px 16px;background:#f0fdf4;border-top:1px solid #86efac">
              <div style="font-weight:700;margin-bottom:8px;color:#166534">Management Final Price Override (USD)</div>
              <div class="grid-2col" style="gap:12px">
                <div class="form-group" style="margin:0">
                  <label>Final NRC (USD)</label>
                  <div class="input-group currency-input-group">
                    <span class="input-group-text currency-badge">$</span>
                    <input type="text" name="management_final_nrc" id="mgmt_final_nrc_input" class="form-control currency-input" data-currency="USD" value="<?= number_format((float)($order['kam_proposed_nrc'] ?? $order['revised_nrc'] ?? $order['standard_nrc'] ?? 0), 2) ?>" placeholder="e.g. 50.00">
                  </div>
                </div>
                <div class="form-group" style="margin:0">
                  <label>Final MRC (USD)</label>
                  <div class="input-group currency-input-group">
                    <span class="input-group-text currency-badge">$</span>
                    <input type="text" name="management_final_mrc" id="mgmt_final_mrc_input" class="form-control currency-input" data-currency="USD" value="<?= number_format((float)($order['kam_proposed_mrc'] ?? $order['standard_mrc'] ?? 0), 2) ?>" placeholder="e.g. 18.00">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Option 3 & Nested Return Remarks -->
          <div style="border:1px solid var(--border);border-radius:6px;background:var(--surface);overflow:hidden">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;margin:0">
              <input type="radio" name="management_decision" value="Return to KAM" required onchange="toggleMgmtFields(this.value)">
              <div>
                <strong>3. Return to Account Manager (KAM)</strong>
                <div style="font-size:0.8rem;color:var(--text-secondary)">Send back to Commercial stage for revision with mandatory remarks.</div>
              </div>
            </label>
            <div id="mgmtReturnRemarksField" style="display:none;padding:14px 16px;background:#fef2f2;border-top:1px solid #fecaca">
              <div class="form-group" style="margin:0">
                <label style="color:#991b1b;font-weight:700">Return Remarks for KAM <span class="required">*</span></label>
                <textarea name="management_return_remarks" id="mgmt_return_remarks_input" class="form-control" rows="2" placeholder="Explain what the KAM should revise..."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Management Decision Notes</label>
        <textarea name="management_remarks" id="mgmt_decision_notes_input" class="form-control" rows="2" placeholder="Optional audit comments..."><?= e($order['management_remarks'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button type="button" class="btn btn-secondary btn-lg" onclick="previewMgmtSubmission()"><?= svgIcon('eye') ?> Preview Decision</button>
        <button type="submit" class="btn btn-primary btn-lg"><?= svgIcon('check') ?> Confirm Management Decision</button>
      </div>
    </form>

    <script>
    function toggleMgmtFields(val) {
      document.getElementById('mgmtRevisedPriceFields').style.display = (val === 'Approve with Revised Price') ? 'block' : 'none';
      document.getElementById('mgmtReturnRemarksField').style.display = (val === 'Return to KAM') ? 'block' : 'none';
    }
    </script>
  </div>
</div>
<?php endif; ?>

<?php // === PARTNER / INTERNAL STAFF: PENDING SOF PANEL === ?>
<?php if ($status === 'Pending SOF' && ($isPartner || $isAdminUser || $isKAM || $isMgmt || $isPM)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header" style="background:rgba(var(--primary-rgb,99,102,241),.1)">
    <div class="card-title"><?= svgIcon('document') ?> Pending SOF — Action Required</div>
    <div class="card-subtitle"><?= $isPartner ? 'Your service is feasible and commercially approved. Please generate, sign, and upload the Service Order Form.' : 'Service is feasible and commercially approved. Generate the SOF and upload the signed document on behalf of the customer or await partner submission.' ?></div>
  </div>
  <div class="card-body">
    <?php if ($order['sof_return_comments']): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">
      <?= svgIcon('info') ?> <strong>SOF returned for correction:</strong> <?= e($order['sof_return_comments']) ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      <div style="flex:1;min-width:280px">
        <div class="card" style="background:var(--surface-2)">
            <?php
              $isEnterpriseOrder = (
                  ($order['partner_category'] ?? '') === 'Enterprise Client' || 
                  ($order['partner_type'] ?? '') === 'Enterprise' || 
                  ($order['partner_type'] ?? '') === 'Government'
              );
            ?>
            <div style="font-weight:600;margin-bottom:8px;display:flex;align-items:center;gap:6px">
              Step 1 — Generate SOF
              <?php if ($isEnterpriseOrder): ?>
                <span class="badge badge-info" style="font-size:0.75rem;padding:2px 8px">🏛️ Enterprise SOF Template</span>
              <?php endif; ?>
            </div>
            <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px"><?= $isEnterpriseOrder ? 'Download and print the Enterprise Client Service Order Form pre-filled with corporate client identity and approved terms.' : 'Download and print the Service Order Form pre-filled with all approved terms.' ?></p>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <button type="button" class="btn btn-primary" onclick="viewSystemFile('<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>', 'SOF_<?= e($order['order_number']) ?>.pdf', '<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>', {doc_type:'<?= $isEnterpriseOrder ? 'Enterprise Service Order Form' : 'Service Order Form' ?>'})"><?= svgIcon('document') ?> Generate &amp; View <?= $isEnterpriseOrder ? 'Enterprise SOF' : 'SOF' ?></button>
              <a href="<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary" title="Open full printable <?= $isEnterpriseOrder ? 'Enterprise ' : '' ?>SOF in a new tab"><?= svgIcon('eye') ?> Full Page</a>
              <a href="<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>&format=excel" class="btn btn-secondary" title="Download Excel SOF"><?= svgIcon('download') ?> Excel SOF</a>
            </div>
          </div>
        </div>
      </div>
      <div style="flex:1;min-width:280px">
        <div class="card" style="background:var(--surface-2)">
          <div class="card-body" style="padding:16px">
            <div style="font-weight:600;margin-bottom:8px">Step 2 — Upload Signed SOF (PDF Only)</div>
            <?php if (!empty($order['sof_signed_file'])): ?>
              <div class="alert alert-success" style="padding:10px;margin-bottom:10px;font-size:0.85rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <div><?= svgIcon('check') ?> Signed SOF uploaded: <strong><?= e($order['sof_signed_filename']) ?></strong></div>
                <?= renderFileActions(['file_url' => $order['sof_signed_file'], 'file_name' => $order['sof_signed_filename'] ?: 'Signed SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=sof_signed_file&id=' . $order['id']]) ?>
              </div>
              <div style="border-top:1px dashed var(--border);padding-top:10px;margin-top:10px">
                <div style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px">Upload replacement signed SOF (PDF only):</div>
                <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_signed_sof" enctype="multipart/form-data" class="order-action-form" data-action-title="Upload Replacement Signed SOF" data-action-role="<?= $isPartner ? 'Partner' : 'KAM / Admin' ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <div class="form-group" style="margin-bottom:8px">
                    <input type="file" name="signed_sof" class="form-control" accept=".pdf,application/pdf" required>
                  </div>
                  <button type="submit" class="btn btn-success btn-sm"><?= svgIcon('upload', 13) ?> Upload Replacement &amp; Submit Order</button>
                </form>
              </div>
            <?php else: ?>
              <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px"><?= $isPartner ? 'Sign the SOF (stamp + authorized signature) and upload it here (.PDF only).' : 'Upload the partner/customer signed copy of the SOF (.PDF only) once received.' ?></p>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_signed_sof" enctype="multipart/form-data" class="order-action-form" data-action-title="Upload Signed Service Order Form" data-action-role="<?= $isPartner ? 'Partner' : 'KAM / Admin' ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <div class="form-group" style="margin-bottom:8px">
                  <input type="file" name="signed_sof" class="form-control" accept=".pdf,application/pdf" required>
                </div>
                <button type="submit" class="btn btn-success"><?= svgIcon('upload') ?> Upload Signed SOF &amp; Submit Order</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if ($isPartner || $isAdminUser): ?>
    <div class="divider" style="margin:20px 0"></div>

    <div>
      <div style="font-weight:600;margin-bottom:10px;color:var(--text-secondary)">Not satisfied with the terms?</div>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_feasibility" class="order-action-form" data-action-title="Return Order to Account Manager" data-action-role="Partner">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group" style="margin-bottom:12px">
          <label>Reason &amp; Remarks for Account Manager <span class="required">*</span></label>
          <input type="text" name="return_remarks" class="form-control" placeholder="Describe your concern (e.g. request higher bandwidth discount or revision)..." required>
        </div>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return to Account Manager (KAM)</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>


<?php // === PARTNER: SOF REVIEW PANEL (partner can still re-upload replacement) === ?>
<?php if ($status === 'SOF Review' && ($isPartner || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning,#f59e0b)">
  <div class="card-header" style="background:rgba(245,158,11,.08)">
    <div class="card-title"><?= svgIcon('document') ?> SOF Under Review</div>
    <div class="card-subtitle">Your signed SOF has been submitted and is under review by Neilos. If you need to update the file, you can upload a replacement below (PDF only).</div>
  </div>
  <div class="card-body">
    <?php if (!empty($order['sof_signed_file'])): ?>
    <div class="alert alert-success" style="padding:10px;margin-bottom:14px;font-size:0.85rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div><?= svgIcon('check') ?> Signed SOF submitted: <strong><?= e($order['sof_signed_filename']) ?></strong></div>
      <?= renderFileActions(['file_url' => $order['sof_signed_file'], 'file_name' => $order['sof_signed_filename'] ?: 'Signed SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=sof_signed_file&id=' . $order['id']]) ?>
    </div>
    <div style="border-top:1px dashed var(--border);padding-top:10px;margin-top:10px">
      <div style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px">Upload replacement signed SOF (.PDF only):</div>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_signed_sof" enctype="multipart/form-data" class="order-action-form" data-action-title="Upload Replacement Signed SOF" data-action-role="Partner">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group" style="margin-bottom:8px">
          <input type="file" name="signed_sof" class="form-control" accept=".pdf,application/pdf" required>
        </div>
        <button type="submit" class="btn btn-success btn-sm"><?= svgIcon('upload', 13) ?> Upload Replacement Signed SOF</button>
      </form>
    </div>
    <?php else: ?>
    <p style="font-size:.875rem;color:var(--text-secondary)">No signed SOF found. Please contact your account manager.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>


<?php // === SOF REVIEW PANEL (KAM & Management Only) === ?>
<?php if ($status === 'SOF Review' && !$isPartner): ?>
  <?php if ($isKAM || $isMgmt || $isAdminUser): ?>
  <div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
    <div class="card-header"><div class="card-title"><?= svgIcon('edit') ?> SOF Review &amp; Countersignature</div></div>
    <div class="card-body">
      <?php if ($order['sof_signed_file']): ?>
      <div class="alert alert-success" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div><?= svgIcon('check') ?> Signed SOF uploaded by partner: <strong><?= e($order['sof_signed_filename']) ?></strong></div>
        <?= renderFileActions(['file_url' => $order['sof_signed_file'], 'file_name' => $order['sof_signed_filename'] ?: 'Signed SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=sof_signed_file&id=' . $order['id']]) ?>
      </div>
      <?php endif; ?>

      <div class="form-grid form-grid-2" style="margin-bottom:16px">
        <div>
          <div style="font-weight:600;margin-bottom:8px">Step 1 — Upload Countersigned SOF (PDF Only)</div>
          <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Upload the Neilos-countersigned copy. This becomes the official contractual document (.PDF only).</p>
          <?php if (!$order['countersigned_sof_file']): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_countersigned_sof" enctype="multipart/form-data" class="order-action-form" data-action-title="Upload Countersigned SOF" data-action-role="KAM / Management">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div class="form-group" style="margin-bottom:8px">
              <input type="file" name="countersigned_sof" class="form-control" accept=".pdf,application/pdf" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= svgIcon('upload') ?> Upload Countersigned SOF</button>
          </form>
          <?php else: ?>
          <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <div><?= svgIcon('check') ?> Countersigned SOF uploaded: <strong><?= e($order['countersigned_sof_filename']) ?></strong> on <?= fmtDate($order['countersigned_sof_at']) ?></div>
            <?= renderFileActions(['file_url' => $order['countersigned_sof_file'], 'file_name' => $order['countersigned_sof_filename'] ?: 'Countersigned SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=countersigned_sof_file&id=' . $order['id']]) ?>
          </div>
          <?php endif; ?>
        </div>
        <div>
          <div style="font-weight:600;margin-bottom:8px">Step 2 — Proceed to Project</div>
          <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Only available after countersigned SOF is uploaded. This releases the order to Project/Installation.</p>
          <form method="POST" action="<?= APP_URL ?>/?page=orders&action=proceed_to_project" class="order-action-form" data-action-title="Proceed to Project Execution" data-action-role="KAM / Management">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <button type="submit" class="btn btn-success" <?= !$order['countersigned_sof_file'] ? 'disabled title="Upload countersigned SOF first"' : '' ?>>
              <?= svgIcon('project') ?> Proceed to Project
            </button>
          </form>
        </div>
      </div>

      <div class="divider" style="margin:16px 0"></div>

      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_sof" class="order-action-form" data-action-title="Return SOF for Partner Correction" data-action-role="KAM / Management">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group">
          <label>Return SOF for Correction — Comments <span class="required">*</span></label>
          <textarea name="sof_return_comments" class="form-control" rows="2" placeholder="Describe what the partner needs to correct..." required></textarea>
        </div>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return SOF to Partner</button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-header"><div class="card-title"><?= svgIcon('document') ?> SOF Review &amp; Countersignature</div></div>
    <div class="card-body">
      <div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div><?= svgIcon('info') ?> Signed SOF submitted by partner. Awaiting review and countersignature by <strong>KAM</strong> or <strong>Management</strong>.</div>
        <?php if ($order['sof_signed_file']): ?>
          <?= renderFileActions(['file_url' => $order['sof_signed_file'], 'file_name' => $order['sof_signed_filename'] ?: 'Signed SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=sof_signed_file&id=' . $order['id']]) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php endif; ?>

<?php // === INSTALLATION PANEL === ?>
<?php 
$additionalCostEvidences = array_filter($evidence ?? [], function($ev) {
    return (stripos($ev['evidence_type'] ?? '', 'Additional Cost') !== false);
});
?>
<?php if ($status === 'Installation'): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><?= svgIcon('project') ?> Installation</div>
    <div class="card-subtitle">Project is in progress. Contractor will update and submit evidence.</div>
  </div>
  <div class="card-body">
    <?php if (!$isPartner && ($isPM || $isAdminUser)): ?>
    <?php if ($assignment): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
      Assigned to <strong><?= e($assignment['contractor_name']) ?></strong>
      <?php if (!empty($assignment['contractor_team_name']) || !empty($order['contractor_team_name'])): ?>
        (Team: <strong>👷 <?= e($assignment['contractor_team_name'] ?: $order['contractor_team_name']) ?></strong>)
      <?php elseif ($assignment['contractor_user_name']): ?>
        (<?= e($assignment['contractor_user_name']) ?>)
      <?php endif; ?>
      · Status: <span class="badge badge-primary"><?= e($assignment['status']) ?></span>
      <?php if ($assignment['target_date']): ?> · Due: <?= fmtDate($assignment['target_date']) ?><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-bottom:16px"><?= svgIcon('info') ?> No contractor assigned yet.</div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=assign_contractor" data-no-lookup="true">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Assign Contractor <span class="required">*</span></label>
          <select name="contractor_partner_id" class="form-control" data-no-lookup="true" required>
            <option value="">Select contractor...</option>
            <?php foreach ($contractorList as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Installation Target Date <span class="required">*</span></label>
          <input type="date" name="target_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Work Order Notes</label>
          <input type="text" name="work_order_notes" class="form-control" placeholder="Any special instructions...">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><?= svgIcon('users') ?> Assign Contractor</button>
    <?php endif; ?>

    <!-- Contractor Additional Cost Claims / Forms Alert -->
    <?php if (!empty($additionalCostEvidences)): ?>
    <div style="margin-top:20px;border:2px solid #f59e0b;background:#fffbeb;border-radius:var(--radius-md);overflow:hidden">
      <div style="background:#fef3c7;padding:12px 18px;border-bottom:1px solid #fde68a;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#92400e;font-size:0.92rem">
          <span>📄</span> Contractor Additional Cost Form(s) Uploaded
          <span class="badge" style="background:#f59e0b;color:#fff;font-size:0.75rem"><?= count($additionalCostEvidences) ?> Form(s)</span>
        </div>
        <span style="font-size:0.78rem;font-weight:600;color:#b45309">Review Required by PM</span>
      </div>
      <div style="padding:14px 18px">
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($additionalCostEvidences as $ace): ?>
          <div style="background:#fff;border:1px solid #fde68a;border-radius:6px;padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-weight:700;font-size:0.75rem">Additional Cost Claim</span>
                <?php if (!empty($ace['cost_amount'])): ?>
                  <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-weight:800;font-size:0.82rem">$<?= number_format((float)$ace['cost_amount'], 2) ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($ace['notes'])): ?>
                <div style="font-size:0.86rem;color:var(--text-primary);font-weight:500;margin-bottom:4px"><?= e($ace['notes']) ?></div>
              <?php endif; ?>
              <div style="font-size:0.75rem;color:var(--text-muted)">
                Submitted by <strong><?= e($ace['full_name'] ?: 'Contractor') ?></strong> on <?= fmtDateTime($ace['uploaded_at']) ?>
                <?php if (!empty($ace['pickup_location'])): ?>
                  · 📍 Site: <?= e($ace['pickup_location']) ?>
                <?php endif; ?>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <?php if (!empty($ace['file_path'])): ?>
                <?= renderFileActions([
                  'file_url' => $ace['file_path'],
                  'file_name' => $ace['file_name'] ?: 'Additional Cost Form',
                  'download_url' => APP_URL . '/?page=download&table=contractor_evidence&id=' . $ace['id']
                ]) ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- PM Supplemental Project Updates & Challenges -->
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <div class="card-subtitle" style="font-weight:700;color:var(--primary);margin-bottom:12px"><?= svgIcon('upload') ?> Project Manager — Installation Challenges &amp; Site Photos</div>
      <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=upload_evidence" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?? 0 ?>">
        <input type="hidden" name="redirect_to" value="<?= APP_URL ?>/?page=order_detail&id=<?= $order['id'] ?>">
        
        <div class="form-group" style="margin:0">
          <label class="font-sm" style="font-weight:600">Pickup Location <span class="text-muted" style="font-weight:normal">(The place in which installation was done)</span></label>
          <div style="position:relative">
            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-secondary)">📍</span>
            <input type="text" name="pickup_location" class="form-control" style="padding-left:32px" value="<?= e($order['pickup_location'] ?? '') ?>" placeholder="Enter physical place / site address where installation was done...">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end">
          <div class="form-group" style="margin:0">
            <label class="font-sm">Evidence Type / Category</label>
            <select name="evidence_type" class="form-control" required>
              <option value="Site Photo">1. Site Photo</option>
              <option value="Signal Test">2. Signal Test</option>
              <option value="Serial Number">3. Serial Number</option>
              <option value="Speed Test">4. Speed Test</option>
              <option value="Ping Test">5. Ping Test</option>
              <option value="UAT">6. UAT</option>
              <option value="Additional Cost Form">7. Additional Cost Form</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="font-sm">Notes / Challenge Details</label>
            <input type="text" name="notes" class="form-control" placeholder="Describe progress, challenge, or photo details...">
          </div>
          <div class="form-group" style="margin:0">
            <label class="font-sm">Upload Photo(s) / Document</label>
            <input type="file" name="evidence_files[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx" multiple>
          </div>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="align-self: flex-start; margin-top: 4px">
          <?= svgIcon('upload') ?> Upload Supplemental Evidence / Notes
        </button>
      </form>

      <!-- Uploaded Installation Evidence & Photos Gallery -->
      <div style="margin-top:20px;padding-top:14px;border-top:1px dashed var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <div style="font-weight:700;font-size:0.9rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
            <?= svgIcon('image') ?> Uploaded Installation Evidence &amp; Photos 
            <span class="badge badge-primary" style="font-size:0.75rem"><?= count($evidence) ?></span>
          </div>
        </div>
        <?= renderEvidenceGallery($evidence, [
          'can_delete'    => (!$isOrderClosed && ($isPM || $isAdminUser || $isBSA)),
          'order_id'      => $order['id'],
          'assignment_id' => $assignment['id'] ?? 0,
          'show_empty'    => true,
          'redirect_to'   => APP_URL . '/?page=order_detail&id=' . $order['id']
        ]) ?>
      </div>
    </div>

    <!-- PM Complete Installation / Move to Testing -->
    <div style="margin-top:20px;padding:16px 20px;border-top:1px solid var(--border);background:var(--surface-2);border-radius:var(--radius-sm)">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">
            <?= svgIcon('check') ?> Submit Job as Complete
          </div>
          <div style="font-size:0.84rem;color:var(--text-secondary);margin-top:2px">
            Installation work is finished. Move the order to <strong>Testing — Internal Review</strong> for PM/BSA testing approval.
          </div>
        </div>
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=submit_installation_complete" data-confirm="Submit job as complete? This will transition the order to Testing — Internal Review." style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <input type="hidden" name="pickup_location" value="<?= e($order['pickup_location'] ?? '') ?>">
          <button type="submit" class="btn btn-success">
            <?= svgIcon('check') ?> Submit Job as Complete — Move to Testing
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($isPartner): ?>
    <div class="alert alert-info">
      <?= svgIcon('info') ?> Your order is currently with our installation team. You will be notified once installation is complete and ready for your UAT acceptance.
    </div>
    <?php endif; ?>

    <?php if ($order['uat_return_reason']): ?>
    <div class="alert alert-warning" style="margin-top:12px">
      <strong>Returned from UAT:</strong> <?= e($order['uat_return_reason']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php // === TESTING PANEL (PM/BSA review) === ?>
<?php if ($status === 'Testing' && !$isPartner && !$isContractor): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
      <div class="card-title"><?= svgIcon('check') ?> Testing — Internal Review</div>
      <div class="card-subtitle">Contractor has submitted work. Review site photos &amp; evidence before sending to partner for UAT.</div>
    </div>
    <span class="badge badge-primary" style="padding:4px 10px;font-size:0.8rem">PM Review Stage</span>
  </div>
  <div class="card-body">
    
    <!-- Contractor Additional Cost Claims / Forms Alert in Testing -->
    <?php if (!empty($additionalCostEvidences)): ?>
    <div style="margin-bottom:20px;border:2px solid #f59e0b;background:#fffbeb;border-radius:var(--radius-md);overflow:hidden">
      <div style="background:#fef3c7;padding:12px 18px;border-bottom:1px solid #fde68a;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#92400e;font-size:0.92rem">
          <span>📄</span> Contractor Additional Cost Form(s) Uploaded
          <span class="badge" style="background:#f59e0b;color:#fff;font-size:0.75rem"><?= count($additionalCostEvidences) ?> Form(s)</span>
        </div>
        <span style="font-size:0.78rem;font-weight:600;color:#b45309">Requires PM Review</span>
      </div>
      <div style="padding:14px 18px">
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($additionalCostEvidences as $ace): ?>
          <div style="background:#fff;border:1px solid #fde68a;border-radius:6px;padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-weight:700;font-size:0.75rem">Additional Cost Claim</span>
                <?php if (!empty($ace['cost_amount'])): ?>
                  <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-weight:800;font-size:0.82rem">$<?= number_format((float)$ace['cost_amount'], 2) ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($ace['notes'])): ?>
                <div style="font-size:0.86rem;color:var(--text-primary);font-weight:500;margin-bottom:4px"><?= e($ace['notes']) ?></div>
              <?php endif; ?>
              <div style="font-size:0.75rem;color:var(--text-muted)">
                Submitted by <strong><?= e($ace['full_name'] ?: 'Contractor') ?></strong> on <?= fmtDateTime($ace['uploaded_at']) ?>
                <?php if (!empty($ace['pickup_location'])): ?>
                  · 📍 Site: <?= e($ace['pickup_location']) ?>
                <?php endif; ?>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <?php if (!empty($ace['file_path'])): ?>
                <?= renderFileActions([
                  'file_url' => $ace['file_path'],
                  'file_name' => $ace['file_name'] ?: 'Additional Cost Form',
                  'download_url' => APP_URL . '/?page=download&table=contractor_evidence&id=' . $ace['id']
                ]) ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Uploaded Installation Evidence & Photos Gallery -->
    <div style="margin-bottom:20px">
      <div style="font-weight:700;font-size:0.9rem;color:var(--text-primary);margin-bottom:8px;display:flex;align-items:center;gap:6px">
        <?= svgIcon('image') ?> Installation Evidence &amp; Site Photos
        <span class="badge badge-primary" style="font-size:0.75rem"><?= count($evidence) ?></span>
      </div>
      <?= renderEvidenceGallery($evidence, [
        'can_delete'    => (!$isOrderClosed && ($isPM || $isAdminUser || $isBSA)),
        'order_id'      => $order['id'],
        'assignment_id' => $assignment['id'] ?? 0,
        'show_empty'    => true,
        'redirect_to'   => APP_URL . '/?page=order_detail&id=' . $order['id']
      ]) ?>
    </div>

    <!-- PM Review & Confirm Pickup Location -->
    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 18px;margin-bottom:20px">
      <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:6px;display:flex;align-items:center;gap:6px">
        <span>📍</span> Confirm Pickup / Installation Site Location (Review with Site Photos)
      </div>
      <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:10px">
        Verify the physical place / room / pole where installation was completed against the uploaded photos:
      </div>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_pickup_location" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div style="flex:1;min-width:260px;position:relative">
          <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-secondary)">📍</span>
          <input type="text" name="pickup_location" id="testing_pickup_location_input" class="form-control" style="padding-left:32px" value="<?= e($order['pickup_location'] ?? '') ?>" placeholder="Enter confirmed physical place / site address where installation was done..." oninput="document.getElementById('approve_testing_pickup_loc').value = this.value;">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap">
          <?= svgIcon('save', 14) ?> Save Pickup Location
        </button>
      </form>
    </div>

    <?php if ($isEnterpriseOrder): ?>
    <!-- Enterprise UAT Upload & Routing Section for Project Manager in Testing -->
    <div style="background:var(--surface-2);border:2px solid var(--primary);border-radius:var(--radius-md);padding:18px 20px;margin-bottom:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px">
        <div style="font-weight:700;font-size:0.95rem;color:var(--primary);display:flex;align-items:center;gap:8px">
          <span>📋</span> Enterprise Client — UAT Phase Management
        </div>
        <span class="badge badge-primary">Enterprise Workflow</span>
      </div>
      <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:14px;line-height:1.5">
        Once service installation and testing have been completed, upload the completed UAT form to route the order to the <strong>Key Account Manager (KAM)</strong> for review and acceptance. Alternatively, if testing fails or issues are identified, return the order to Installation.
      </p>

      <?php if ($isPM || $isBSA || $isAdminUser): ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_uat_form" enctype="multipart/form-data" style="margin-bottom:8px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:14px;margin-bottom:14px">
          <div>
            <label style="display:block;font-weight:600;font-size:0.84rem;margin-bottom:5px">
              Upload Signed UAT Form <span class="required">*</span>
            </label>
            <input type="file" name="uat_form" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" required>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:3px">Supported formats: PDF, Images, Word (.doc, .docx)</div>
          </div>
          <div>
            <label style="display:block;font-weight:600;font-size:0.84rem;margin-bottom:5px">
              Testing &amp; Handover Remarks (Optional)
            </label>
            <input type="text" name="uat_remarks" class="form-control" placeholder="e.g. Service tested at full bandwidth, latency &amp; packet loss verified...">
          </div>
        </div>
        
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <button type="submit" class="btn btn-success" style="font-weight:600">
            <?= svgIcon('upload') ?> Upload UAT Form &amp; Route to KAM
          </button>
          <button type="button" class="btn btn-danger" data-modal-open="returnInstallationModal">
            <?= svgIcon('refresh') ?> Return to Installation
          </button>
        </div>
      </form>
      <?php else: ?>
      <div class="alert alert-info" style="margin-bottom:12px">
        Awaiting Project Manager to complete testing and upload the signed UAT Form.
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <?php if (!$isEnterpriseOrder): ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=approve_testing">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="pickup_location" id="approve_testing_pickup_loc" value="<?= e($order['pickup_location'] ?? '') ?>">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Approve Testing — Move to UAT</button>
      </form>
      <?php else: ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=approve_testing">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="pickup_location" id="approve_testing_pickup_loc" value="<?= e($order['pickup_location'] ?? '') ?>">
        <button type="submit" class="btn btn-secondary" title="Advance to UAT phase if uploading UAT form subsequently"><?= svgIcon('arrow-right', 14) ?> Advance to UAT (Upload Later)</button>
      </form>
      <button type="button" class="btn btn-danger" data-modal-open="returnInstallationModal"><?= svgIcon('refresh') ?> Return to Installation</button>
      <?php endif; ?>
      <button class="btn btn-warning" data-modal-open="returnContractorModal"><?= svgIcon('refresh') ?> Return to Contractor</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="returnContractorModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Return to Contractor</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_contractor">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Comments for Contractor <span class="required">*</span></label>
          <textarea name="return_comments" class="form-control" rows="4" placeholder="What needs to be corrected or re-submitted?" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return to Contractor</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="returnInstallationModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Return Order to Installation</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_installation">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <p style="font-size:0.86rem;color:var(--text-secondary);margin-bottom:12px">
          Return this order to the Installation phase for correction or physical rework by the installation team/contractor.
        </p>
        <div class="form-group">
          <label>Reason / Remarks for Returning to Installation <span class="required">*</span></label>
          <textarea name="return_reason" class="form-control" rows="4" placeholder="Detail the technical or physical defects that must be resolved..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger"><?= svgIcon('refresh') ?> Return to Installation</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === UAT PANEL === ?>
<?php if ($status === 'UAT'): ?>
<?php
  // Locate UAT Signoff Document if attached
  $uatDoc = null;
  foreach ($docs as $d) {
      if (($d['doc_type'] ?? '') === 'uat_signoff') {
          $uatDoc = $d;
          break;
      }
  }
  $hasUatFile = !empty($order['uat_signoff_file']) || !empty($uatDoc);
  $uatDocPath = !empty($order['uat_signoff_file']) ? $order['uat_signoff_file'] : ($uatDoc['file_path'] ?? '');
  $uatDocName = !empty($order['uat_signoff_filename']) ? $order['uat_signoff_filename'] : ($uatDoc['file_name'] ?? 'UAT_Signoff_Form.pdf');
  $uatDownloadUrl = !empty($uatDoc['id']) ? (APP_URL . '/?page=download&table=order_documents&id=' . $uatDoc['id']) : (APP_URL . '/?page=download&table=orders&column=uat_signoff_file&id=' . $order['id']);
?>
<div class="card" style="margin-bottom:24px;border:2px solid <?= $isEnterpriseOrder ? 'var(--primary)' : 'var(--success)' ?>">
  <div class="card-header" style="background:<?= $isEnterpriseOrder ? 'rgba(var(--primary-rgb,99,102,241),.08)' : 'rgba(var(--success-rgb,34,197,94),.1)' ?>">
    <div class="card-title"><?= svgIcon('check') ?> <?= $isEnterpriseOrder ? 'Enterprise UAT — User Acceptance Testing' : 'UAT — User Acceptance Testing' ?></div>
    <div class="card-subtitle">
      <?php if ($isEnterpriseOrder): ?>
        <?= ($isKAM || $isMgmt || $isAdminUser) ? 'Review the UAT form uploaded by the Project Manager and accept service to close order, or return to installation.' : 'UAT form routed to Key Account Manager (' . e($order['kam_name'] ?: 'KAM') . ') for review and acceptance.' ?>
      <?php else: ?>
        <?= $isPartner ? 'Please review the installation evidence and accept or return the service.' : 'Awaiting partner UAT acceptance.' ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">

    <?php if ($isEnterpriseOrder): ?>
      <!-- Enterprise Order Status Banner -->
      <?php if ($hasUatFile): ?>
      <div class="alert alert-info" style="margin-bottom:18px;display:flex;align-items:center;gap:8px">
        <?= svgIcon('info') ?>
        <div>
          UAT Form uploaded by <strong><?= e($order['uat_uploaded_by_name'] ?? 'Project Manager') ?></strong> on <strong><?= fmtDateTime($order['uat_uploaded_at'] ?? $order['uat_submitted_at'] ?? $order['updated_at']) ?></strong>. Routed to Key Account Manager (<strong><?= e($order['kam_name'] ?: 'KAM') ?></strong>) for review and acceptance.
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-warning" style="margin-bottom:18px;display:flex;align-items:center;gap:8px">
        <?= svgIcon('alert-triangle') ?>
        <div>
          <strong>UAT Form Pending:</strong> The Project Manager has not yet uploaded the completed UAT Form into the system.
        </div>
      </div>
      <?php endif; ?>

      <!-- Uploaded UAT Document Display Card -->
      <?php if ($hasUatFile): ?>
      <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:var(--radius-md);padding:16px 20px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:12px;min-width:260px">
          <div style="width:44px;height:44px;border-radius:8px;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
            📄
          </div>
          <div>
            <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)"><?= e($uatDocName) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted)">
              Uploaded by <strong><?= e($order['uat_uploaded_by_name'] ?? 'Project Manager') ?></strong> · <?= fmtDateTime($order['uat_uploaded_at'] ?? $order['uat_submitted_at'] ?? $order['updated_at']) ?>
            </div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <?= renderFileActions([
            'file_url'     => $uatDocPath,
            'file_name'    => $uatDocName,
            'download_url' => $uatDownloadUrl
          ]) ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- PM Upload / Re-upload Section in UAT -->
      <?php if ($isPM || $isBSA || $isAdminUser): ?>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:22px">
        <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary);margin-bottom:6px;display:flex;align-items:center;gap:6px">
          <span>📤</span> <?= $hasUatFile ? 'Re-upload / Update UAT Form (Project Manager)' : 'Upload Completed UAT Form (Project Manager)' ?>
        </div>
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_uat_form" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:10px">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div style="flex:1;min-width:240px">
            <input type="file" name="uat_form" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" required>
          </div>
          <div style="flex:1;min-width:220px">
            <input type="text" name="uat_remarks" class="form-control" placeholder="Optional notes / update remarks...">
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">
            <?= svgIcon('upload', 14) ?> <?= $hasUatFile ? 'Update UAT Form' : 'Upload UAT Form' ?>
          </button>
        </form>
      </div>
      <?php endif; ?>

      <!-- KAM Acceptance & Return Actions -->
      <?php if ($isKAM || $isMgmt || $isAdminUser): ?>
      <div style="margin-bottom:20px">
        <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);margin-bottom:12px;display:flex;align-items:center;gap:6px">
          <span>⚖️</span> KAM Service Acceptance Actions
        </div>
        <div style="display:flex;gap:16px;flex-wrap:wrap">
          <!-- 1. Accept Service Card -->
          <div style="flex:1;min-width:260px">
            <form method="POST" action="<?= APP_URL ?>/?page=orders&action=uat_accept" onsubmit="return confirm('Accept service and mark this order as Closed? Billing start date will be set to today.');">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <div style="background:rgba(var(--success-rgb,34,197,94),.08);border:1.5px solid var(--success);border-radius:var(--radius);padding:20px;height:100%;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                  <div style="font-weight:700;font-size:1rem;color:#15803d;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                    ✅ Accept Service (Close Order)
                  </div>
                  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:14px;line-height:1.5">
                    Confirm service acceptance based on the verified UAT form. The order will be immediately <strong>marked as Closed</strong>, active service record provisioned, and billing will start today (<strong><?= date('d M Y') ?></strong>).
                  </p>
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;font-weight:700">
                  <?= svgIcon('check') ?> Accept Service — Mark Closed
                </button>
              </div>
            </form>
          </div>

          <!-- 2. Return to Installation Card -->
          <div style="flex:1;min-width:260px">
            <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_installation">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <div style="background:rgba(220,53,69,.06);border:1.5px solid var(--danger);border-radius:var(--radius);padding:20px;height:100%;display:flex;flex-direction:column;justify-content:space-between">
                <div>
                  <div style="font-weight:700;font-size:1rem;color:#b91c1c;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                    🔄 Return to Installation
                  </div>
                  <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:10px;line-height:1.4">
                    If testing or client feedback indicates deficiencies requiring physical correction, return the order to Installation.
                  </p>
                  <div class="form-group" style="margin-bottom:12px">
                    <textarea name="return_reason" class="form-control" rows="3" placeholder="Describe the issues requiring correction in installation..." required></textarea>
                  </div>
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%;font-weight:700">
                  <?= svgIcon('refresh') ?> Return to Installation
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php elseif ($isPM): ?>
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div>
          <div style="font-weight:700;font-size:0.92rem;color:var(--text-primary);margin-bottom:4px">
            📋 Project Manager UAT Stage Options
          </div>
          <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">
            Order is currently awaiting KAM review and acceptance. If site issues occur, you may return this order to Installation.
          </p>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm" data-modal-open="returnInstallationModal">
          <?= svgIcon('refresh') ?> Return to Installation
        </button>
      </div>
      <?php else: ?>
      <div class="alert alert-info">Order is in UAT stage. UAT Form is under review by Key Account Management (KAM).</div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Standard Partner UAT Section -->
      <?php if ($order['uat_notified_at']): ?>
      <div class="alert alert-info" style="margin-bottom:16px">
        <?= svgIcon('clock') ?> Partner notified: <?= fmtDateTime($order['uat_notified_at']) ?>
        <?php if ($order['uat_deadline']): ?>
        · Deadline: <?= fmtDateTime($order['uat_deadline']) ?>
        <strong> · <?= strtotime($order['uat_deadline']) > time() ? round((strtotime($order['uat_deadline']) - time()) / 3600) . ' hrs remaining' : '⚠️ EXPIRED — Auto-accept will trigger' ?></strong>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($isPartner || $isAdminUser): ?>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:240px">
          <form method="POST" action="<?= APP_URL ?>/?page=orders&action=uat_accept">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div style="background:rgba(var(--success-rgb,34,197,94),.08);border:1px solid var(--success);border-radius:var(--radius);padding:20px">
              <div style="font-weight:600;font-size:.95rem;margin-bottom:8px">✅ Accept Service</div>
              <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px">Service is working correctly. Accept and close the order. Billing start date will be set to today.</p>
              <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Accept Service — Close Order</button>
            </div>
          </form>
        </div>
        <div style="flex:1;min-width:240px">
          <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_installation">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div style="background:rgba(220,53,69,.06);border:1px solid var(--danger);border-radius:var(--radius);padding:20px">
              <div style="font-weight:600;font-size:.95rem;margin-bottom:8px">🔄 Return to Installation</div>
              <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px">Service requires correction. Please describe the issue.</p>
              <div class="form-group" style="margin-bottom:10px">
                <textarea name="return_reason" class="form-control" rows="3" placeholder="Describe what needs to be fixed..." required></textarea>
              </div>
              <button type="submit" class="btn btn-danger"><?= svgIcon('refresh') ?> Return to Installation</button>
            </div>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-info">Partner is reviewing installation. Awaiting acceptance or return.</div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($evidence)): ?>
    <div class="divider" style="margin:20px 0"></div>
    <div style="font-weight:700;font-size:0.9rem;margin-bottom:8px;display:flex;align-items:center;gap:6px">
      <?= svgIcon('image') ?> Installation Evidence &amp; Test Results
      <span class="badge badge-primary" style="font-size:0.75rem"><?= count($evidence) ?></span>
    </div>
    <?= renderEvidenceGallery($evidence, [
      'can_delete'    => false,
      'order_id'      => $order['id'],
      'assignment_id' => $assignment['id'] ?? 0,
      'show_empty'    => false,
      'redirect_to'   => APP_URL . '/?page=order_detail&id=' . $order['id']
    ]) ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php // === CLOSED PANEL === ?>
<?php if ($status === 'Closed'): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--success)">
  <div class="card-header" style="background:rgba(var(--success-rgb,34,197,94),.1)">
    <div class="card-title"><?= svgIcon('check') ?> Order Closed</div>
  </div>
  <div class="card-body">
    <div class="form-grid form-grid-4">
      <div class="form-group"><label>Installation Date</label><div class="font-600 text-primary"><?= fmtDate($order['installation_date'] ?? ($assignment['completed_at'] ?? ($order['activation_date'] ?? ($order['closed_date'] ?? null)))) ?></div></div>
      <div class="form-group"><label>Closed Date</label><div class="font-600 text-success"><?= fmtDate($order['closed_date']) ?></div></div>
      <div class="form-group"><label>Billing Start Date</label><div class="font-600 text-success"><?= fmtDate($order['billing_start_date']) ?></div></div>
      <div class="form-group"><label>Circuit ID</label><div class="font-600 font-mono text-primary"><?= e($order['circuit_id'] ?: ('CKT-' . $order['order_number'])) ?></div></div>
    </div>

    <?php
      $closedUatDoc = null;
      foreach ($docs as $d) {
          if (($d['doc_type'] ?? '') === 'uat_signoff') {
              $closedUatDoc = $d;
              break;
          }
      }
      $hasClosedUat = !empty($order['uat_signoff_file']) || !empty($closedUatDoc);
    ?>
    <?php if ($hasClosedUat): ?>
    <div style="margin-top:16px;padding:12px 16px;background:rgba(var(--success-rgb,34,197,94),.06);border:1px solid rgba(var(--success-rgb,34,197,94),.25);border-radius:var(--radius);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:1.3rem">📄</span>
        <div>
          <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary)">
            <?= e($order['uat_signoff_filename'] ?? ($closedUatDoc['file_name'] ?? 'UAT_Signoff_Form.pdf')) ?>
          </div>
          <div style="font-size:0.75rem;color:var(--text-muted)">
            Verified UAT Form · Accepted on <?= fmtDate($order['closed_date']) ?>
          </div>
        </div>
      </div>
      <div>
        <?= renderFileActions([
          'file_url'     => !empty($order['uat_signoff_file']) ? $order['uat_signoff_file'] : ($closedUatDoc['file_path'] ?? ''),
          'file_name'    => $order['uat_signoff_filename'] ?? ($closedUatDoc['file_name'] ?? 'UAT_Signoff_Form.pdf'),
          'download_url' => !empty($closedUatDoc['id']) ? (APP_URL . '/?page=download&table=order_documents&id=' . $closedUatDoc['id']) : (APP_URL . '/?page=download&table=orders&column=uat_signoff_file&id=' . $order['id'])
        ]) ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($evidence)): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <div style="font-weight:700;font-size:0.9rem;color:var(--text-primary);margin-bottom:8px;display:flex;align-items:center;gap:6px">
        <?= svgIcon('image') ?> Completed Installation Photos &amp; Test Evidence
        <span class="badge badge-success" style="font-size:0.75rem"><?= count($evidence) ?></span>
      </div>
      <?= renderEvidenceGallery($evidence, [
        'can_delete'    => false,
        'order_id'      => $order['id'],
        'assignment_id' => $assignment['id'] ?? 0,
        'show_empty'    => false,
        'redirect_to'   => APP_URL . '/?page=order_detail&id=' . $order['id']
      ]) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php // ============================================================
// COLLAPSIBLE ACCORDION SECTIONS: Timeline, Documents, Evidence, Contractor Updates, Returns, Price Audit
// (By default closed / collapsible to save vertical screen space)
// ============================================================ ?>
<div class="order-collapsible-sections" style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px">

  <!-- 0. Order Lifecycle & Modification Audit Trail Section -->
  <?php if (!empty($timeline) && !$isPartner): ?>
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_timeline')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('activity', 18) ?>
        <span>Order Lifecycle &amp; Modification Audit Trail</span>
        <span class="badge badge-primary" style="font-size:0.75rem"><?= count($timeline) ?> Entries</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to expand</span>
        <span id="chevron_order_section_timeline" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▼</span>
      </div>
    </div>
    <div id="order_section_timeline" class="order-accordion-body" style="display:none;border-top:1px solid var(--border)">
      <div class="card-body" style="padding:20px">
        <div class="timeline">
          <?php foreach ($timeline as $t): ?>
          <div class="timeline-item" style="padding-bottom:16px">
            <div class="timeline-dot"></div>
            <div class="timeline-time" style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:4px">
              <?= fmtDateTime($t['changed_at']) ?> &middot; Modified by <strong><?= e($t['full_name'] ?: 'System') ?></strong>
            </div>
            <div class="timeline-label" style="margin-bottom:6px">
              <span class="badge <?= orderStatusClass($t['status']) ?>" style="font-size:0.8rem"><?= e($t['status']) ?></span>
            </div>
            <?php if (!empty($t['note'])): ?>
            <div class="timeline-note" style="margin-top:6px;padding:10px 14px;background:var(--surface-2);border-radius:6px;border-left:3px solid var(--primary);font-size:0.875rem;line-height:1.5;white-space:pre-wrap;font-family:inherit"><?= e($t['note']) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 1. Documents Section -->
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_documents')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('document', 18) ?>
        <span>Order Documents &amp; SOF</span>
        <span class="badge badge-primary" style="font-size:0.75rem"><?= count($docs) ?> Files</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to expand</span>
        <span id="chevron_order_section_documents" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▼</span>
      </div>
    </div>
    <div id="order_section_documents" class="order-accordion-body" style="display:none;border-top:1px solid var(--border)">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($docs)): ?>
            <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No documents uploaded</div></div></td></tr>
            <?php else: ?>
            <?php foreach ($docs as $d): ?>
            <tr>
              <td class="font-600"><?= e($d['file_name']) ?></td>
              <td>
                <?php
                $isCountersigned = (($d['doc_type'] ?? '') === 'countersigned_sof' || $d['document_type'] === 'Countersigned SOF');
                $isSigned = (($d['doc_type'] ?? '') === 'sof' || $d['document_type'] === 'Signed SOF');
                $badgeClass = $isCountersigned ? 'badge-success' : ($isSigned ? 'badge-primary' : 'badge-secondary');
                ?>
                <span class="badge <?= $badgeClass ?>"><?= e($d['document_type']) ?></span>
              </td>
              <td class="font-sm"><?= formatBytes($d['file_size']) ?></td>
              <td class="font-sm"><?= e($d['full_name'] ?: '—') ?></td>
              <td class="text-muted font-sm"><?= fmtDate($d['uploaded_at']) ?></td>
              <td><?= renderFileActions(['file_url' => $d['file_path'], 'file_name' => $d['file_name'] ?: 'Document', 'download_url' => APP_URL . '/?page=download&table=order_documents&id=' . $d['id']]) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 3. Evidence & Installation Photos Section -->
  <?php if (!empty($evidence)): ?>
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_evidence')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('image', 18) ?>
        <span>Installation Evidence &amp; Photos Gallery</span>
        <span class="badge badge-success" style="font-size:0.75rem"><?= count($evidence) ?> Photos/Files</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to toggle</span>
        <span id="chevron_order_section_evidence" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▲</span>
      </div>
    </div>
    <div id="order_section_evidence" class="order-accordion-body" style="display:block;border-top:1px solid var(--border)">
      <div class="card-body" style="padding:20px">
        <?= renderEvidenceGallery($evidence, [
          'can_delete'    => (!$isOrderClosed && ($isPM || $isAdminUser || $isBSA)),
          'order_id'      => $order['id'],
          'assignment_id' => $assignment['id'] ?? 0,
          'show_empty'    => true,
          'redirect_to'   => APP_URL . '/?page=order_detail&id=' . $order['id']
        ]) ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 4. Contractor Progress Updates Section -->
  <?php if (!empty($progressUpdates) && !$isPartner): ?>
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_progress')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('users', 18) ?>
        <span>Contractor Field Updates &amp; Blockers</span>
        <span class="badge badge-secondary" style="font-size:0.75rem"><?= count($progressUpdates) ?> Updates</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to expand</span>
        <span id="chevron_order_section_progress" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▼</span>
      </div>
    </div>
    <div id="order_section_progress" class="order-accordion-body" style="display:none;border-top:1px solid var(--border)">
      <div class="card-body" style="padding:20px">
        <div class="timeline">
          <?php foreach ($progressUpdates as $pu): ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-time"><?= fmtDateTime($pu['created_at']) ?> by <?= e($pu['full_name'] ?: '—') ?></div>
            <div class="timeline-label"><span class="badge badge-secondary"><?= e($pu['progress_status']) ?></span><?php if ($pu['delay_reason']): ?> <span class="badge badge-warning"><?= e($pu['delay_reason']) ?></span><?php endif; ?></div>
            <div class="timeline-note"><?= e($pu['notes']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 5. Return Audit Trail Section -->
  <?php if (!empty($orderReturns) && !$isPartner): ?>
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_returns')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('refresh', 18) ?>
        <span>Return &amp; Correction Audit Trail</span>
        <span class="badge badge-warning" style="font-size:0.75rem"><?= count($orderReturns) ?> Returns</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to expand</span>
        <span id="chevron_order_section_returns" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▼</span>
      </div>
    </div>
    <div id="order_section_returns" class="order-accordion-body" style="display:none;border-top:1px solid var(--border)">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>Date</th><th>By</th><th>From</th><th>To</th><th>Routed To</th><th>Reason</th><th>Remarks</th></tr></thead>
          <tbody>
            <?php foreach ($orderReturns as $r): ?>
            <tr>
              <td class="font-sm"><?= fmtDate($r['returned_at']) ?></td>
              <td class="font-sm"><?= e($r['full_name'] ?: '—') ?></td>
              <td><span class="badge <?= orderStatusClass($r['from_status']) ?>"><?= e($r['from_status']) ?></span></td>
              <td><span class="badge <?= orderStatusClass($r['to_status']) ?>"><?= e($r['to_status']) ?></span></td>
              <td><span class="badge badge-secondary"><?= e($r['routed_to']) ?></span></td>
              <td><?= e($r['return_reason']) ?></td>
              <td><?= e($r['return_remarks'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 6. Commercial Pricing Modification Audit Trail Section -->
  <?php if (!empty($priceAudit) && !$isPartner): ?>
  <div class="card order-accordion-card" style="margin-bottom:0;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:var(--shadow-sm)">
    <div class="order-accordion-header" onclick="toggleOrderSection('order_section_price_audit')" style="cursor:pointer;padding:14px 20px;background:var(--surface-2);display:flex;align-items:center;justify-content:space-between;user-select:none;transition:background 0.2s ease">
      <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:10px">
        <?= svgIcon('dollar', 18) ?>
        <span>Commercial Pricing Modification Audit Trail</span>
        <span class="badge badge-secondary" style="font-size:0.75rem"><?= count($priceAudit) ?> Revisions</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="text-muted font-sm" style="font-size:0.75rem">Click to expand</span>
        <span id="chevron_order_section_price_audit" style="transition:transform 0.25s ease;display:inline-block;font-size:0.85rem">▼</span>
      </div>
    </div>
    <div id="order_section_price_audit" class="order-accordion-body" style="display:none;border-top:1px solid var(--border)">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date &amp; Time</th>
              <th>Field Modified</th>
              <th>Previous Value</th>
              <th>New Value</th>
              <th>Modified By</th>
              <th>Lifecycle Stage</th>
              <th>Justification / Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($priceAudit as $pa): ?>
            <tr>
              <td class="font-sm" style="white-space:nowrap"><?= fmtDateTime($pa['changed_at']) ?></td>
              <td><strong><?= e(priceFieldLabel($pa['field_name'])) ?></strong></td>
              <td class="font-sm text-muted"><?= $pa['old_value'] !== null ? '$' . number_format((float)$pa['old_value'], 2) : '—' ?></td>
              <td class="font-sm font-600" style="color:var(--primary)"><?= $pa['new_value'] !== null ? '$' . number_format((float)$pa['new_value'], 2) : '—' ?></td>
              <td class="font-sm"><?= e($pa['changed_by_name'] ?: 'System') ?></td>
              <td><span class="badge badge-secondary"><?= e($pa['stage']) ?></span></td>
              <td style="max-width:300px;font-size:0.85rem;color:var(--text-secondary)"><?= e($pa['justification'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleOrderSection(sectionId) {
  const body = document.getElementById(sectionId);
  const chevron = document.getElementById('chevron_' + sectionId);
  if (!body) return;
  const isHidden = (body.style.display === 'none' || !body.style.display);
  body.style.display = isHidden ? 'block' : 'none';
  if (chevron) {
    chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
  }
}
</script>

<?php // Edit Order Details Modal (Admin, Management, KAM with mandatory audit history) ?>
<?php if ($canEditOrder): ?>
<div class="modal-backdrop" id="editOrderModal">
  <div class="modal" style="max-width:880px;width:95%">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:8px">
        <div class="modal-title"><?= svgIcon('edit') ?> Edit Order Details — <?= e($order['order_number']) ?></div>
        <span class="badge <?= orderStatusClass($status) ?>" style="font-size:0.75rem"><?= e($status) ?></span>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=edit_order">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body" style="max-height:calc(85vh - 130px);overflow-y:auto;padding:20px">
        
        <div class="alert alert-warning" style="margin-bottom:20px;font-size:0.85rem">
          <strong>⚠️ Complete Audit Compliance:</strong> Any changes made to client information, technical configurations, commercial terms, or lifecycle dates will be strictly compared with previous values and permanently recorded with before-and-after change history ("changed from this to this").
        </div>

        <!-- 1. Client & Contact Information -->
        <div style="font-weight:700;font-size:0.9rem;color:var(--primary);margin-bottom:12px;border-bottom:2px solid var(--border);padding-bottom:6px;display:flex;align-items:center;gap:6px">
          <?= svgIcon('user') ?> 1. Client &amp; Contact Information
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Customer / Company Name <span class="text-danger">*</span></label>
            <input type="text" name="customer_name" class="form-control" value="<?= e($order['customer_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Contact Person Name</label>
            <input type="text" name="customer_contact_name" class="form-control" value="<?= e($order['customer_contact_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Contact Phone</label>
            <input type="text" name="customer_contact_phone" class="form-control" value="<?= e($order['customer_contact_phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="customer_contact_email" class="form-control" value="<?= e($order['customer_contact_email'] ?? '') ?>">
          </div>
        </div>

        <!-- 2. Location & Installation Site Details -->
        <div style="font-weight:700;font-size:0.9rem;color:var(--primary);margin-top:20px;margin-bottom:12px;border-bottom:2px solid var(--border);padding-bottom:6px;display:flex;align-items:center;gap:6px">
          <?= svgIcon('map-pin') ?> 2. Location &amp; Installation Site Details
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Customer Location / Address</label>
            <input type="text" name="customer_location" class="form-control" value="<?= e($order['customer_location'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Pickup Location / Room <span class="text-muted font-sm">(Where equipment/installation is placed)</span></label>
            <input type="text" name="pickup_location" class="form-control" value="<?= e($order['pickup_location'] ?? '') ?>" placeholder="e.g. Server Room 4B, Rack 02">
          </div>
          <div class="form-group">
            <label>Building Name</label>
            <input type="text" name="building_name" class="form-control" value="<?= e($order['building_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Floor Number</label>
            <input type="text" name="floor_number" class="form-control" value="<?= e($order['floor_number'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Apartment / Suite / Room</label>
            <input type="text" name="apartment_number" class="form-control" value="<?= e($order['apartment_number'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>City / Region</label>
            <input type="text" name="city_region" class="form-control" value="<?= e($order['city_region'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>GPS Coordinates</label>
            <input type="text" name="gps_coordinates" class="form-control" value="<?= e($order['gps_coordinates'] ?? '') ?>" placeholder="-6.7924, 39.2083">
          </div>
          <div class="form-group">
            <label>Site Category</label>
            <input type="text" name="site_category" class="form-control" value="<?= e($order['site_category'] ?? '') ?>" placeholder="Enterprise, Residential, SME...">
          </div>
        </div>

        <!-- 3. Service & Technical Specifications -->
        <div style="font-weight:700;font-size:0.9rem;color:var(--primary);margin-top:20px;margin-bottom:12px;border-bottom:2px solid var(--border);padding-bottom:6px;display:flex;align-items:center;gap:6px">
          <?= svgIcon('cpu') ?> 3. Service &amp; Technical Specifications
        </div>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label>Service Type</label>
            <input type="text" name="service_type" class="form-control" value="<?= e($order['service_type'] ?? '') ?>" placeholder="FTTH, BIA, Layer 2...">
          </div>
          <div class="form-group">
            <label>Bandwidth / Package</label>
            <input type="text" name="bandwidth" class="form-control" value="<?= e($order['bandwidth'] ?: ($order['fttx_package'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label>FTTX Package</label>
            <input type="text" name="fttx_package" class="form-control" value="<?= e($order['fttx_package'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Capacity</label>
            <input type="number" name="capacity" class="form-control" value="<?= e($order['capacity'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Capacity Unit</label>
            <select name="capacity_unit" class="form-control">
              <option value="Mbps" <?= (($order['capacity_unit'] ?? '') === 'Mbps') ? 'selected' : '' ?>>Mbps</option>
              <option value="Gbps" <?= (($order['capacity_unit'] ?? '') === 'Gbps') ? 'selected' : '' ?>>Gbps</option>
            </select>
          </div>
          <div class="form-group">
            <label>POP Location</label>
            <input type="text" name="pop_location" class="form-control" value="<?= e($order['pop_location'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>NNI Location</label>
            <input type="text" name="nni_location" class="form-control" value="<?= e($order['nni_location'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>NNI Port Size</label>
            <select name="nni_port_size" class="form-control">
              <option value="1G" <?= (($order['nni_port_size'] ?? '') === '1G') ? 'selected' : '' ?>>1G</option>
              <option value="10G" <?= (($order['nni_port_size'] ?? '') === '10G' || empty($order['nni_port_size'])) ? 'selected' : '' ?>>10G</option>
              <option value="40G" <?= (($order['nni_port_size'] ?? '') === '40G') ? 'selected' : '' ?>>40G</option>
            </select>
          </div>
          <div class="form-group">
            <label>Aggregate Capacity</label>
            <input type="text" name="aggregate_capacity" class="form-control" value="<?= e($order['aggregate_capacity'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Circuit ID</label>
            <input type="text" name="circuit_id" class="form-control" value="<?= e($order['circuit_id'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Service ID</label>
            <input type="text" name="service_id" class="form-control" value="<?= e($order['service_id'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>SOF Number</label>
            <input type="text" name="sof_number" class="form-control" value="<?= e($order['sof_number'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Contract Term</label>
            <input type="text" name="contract_term" class="form-control" value="<?= e($order['contract_term'] ?? '') ?>" placeholder="12 Months, 24 Months...">
          </div>
          <div class="form-group">
            <label>Interface Type</label>
            <input type="text" name="interface_type" class="form-control" value="<?= e($order['interface_type'] ?? '') ?>" placeholder="1GE, 10GE...">
          </div>
          <div class="form-group">
            <label>Connector Type</label>
            <input type="text" name="connector_type" class="form-control" value="<?= e($order['connector_type'] ?? '') ?>" placeholder="LC, SC, RJ45...">
          </div>
          <div class="form-group">
            <label>Media Type</label>
            <input type="text" name="media_type" class="form-control" value="<?= e($order['media_type'] ?? '') ?>" placeholder="Single-Mode Fiber...">
          </div>
          <div class="form-group">
            <label>Power Type</label>
            <input type="text" name="power_type" class="form-control" value="<?= e($order['power_type'] ?? '') ?>" placeholder="AC, DC...">
          </div>
          <div class="form-group">
            <label>Rack Space</label>
            <input type="text" name="rack_space" class="form-control" value="<?= e($order['rack_space'] ?? '') ?>" placeholder="1U, 2U...">
          </div>
        </div>

        <!-- 4. Commercial & Pricing Details -->
        <div style="font-weight:700;font-size:0.9rem;color:var(--primary);margin-top:20px;margin-bottom:12px;border-bottom:2px solid var(--border);padding-bottom:6px;display:flex;align-items:center;gap:6px">
          <?= svgIcon('dollar') ?> 4. Commercial &amp; Pricing Details
        </div>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label>Standard MRC</label>
            <input type="number" step="0.01" name="standard_mrc" class="form-control" value="<?= e($order['standard_mrc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Standard NRC</label>
            <input type="number" step="0.01" name="standard_nrc" class="form-control" value="<?= e($order['standard_nrc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Revised MRC</label>
            <input type="number" step="0.01" name="revised_mrc" class="form-control" value="<?= e($order['revised_mrc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Revised NRC</label>
            <input type="number" step="0.01" name="revised_nrc" class="form-control" value="<?= e($order['revised_nrc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Currency</label>
            <select name="mrc_currency" class="form-control">
              <option value="USD" <?= (($order['mrc_currency'] ?? 'USD') === 'USD') ? 'selected' : '' ?>>USD ($)</option>
              <option value="TZS" <?= (($order['mrc_currency'] ?? '') === 'TZS') ? 'selected' : '' ?>>TZS (TSh)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Discount %</label>
            <input type="number" step="0.01" name="discount_pct" class="form-control" value="<?= e($order['discount_pct'] ?? '') ?>">
          </div>
        </div>

        <!-- 5. Lifecycle Dates & Service Status -->
        <div style="font-weight:700;font-size:0.9rem;color:var(--primary);margin-top:20px;margin-bottom:12px;border-bottom:2px solid var(--border);padding-bottom:6px;display:flex;align-items:center;gap:6px">
          <?= svgIcon('calendar') ?> 5. Lifecycle Dates &amp; Service State
        </div>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label>Installation Date</label>
            <input type="date" name="installation_date" class="form-control" value="<?= e($order['installation_date'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Activation Date</label>
            <input type="date" name="activation_date" class="form-control" value="<?= e($order['activation_date'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Billing Start Date</label>
            <input type="date" name="billing_start_date" class="form-control" value="<?= e($order['billing_start_date'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Closed Date</label>
            <input type="date" name="closed_date" class="form-control" value="<?= e($order['closed_date'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Service Active Status</label>
            <select name="is_active" class="form-control">
              <option value="1" <?= (!isset($order['is_active']) || (int)$order['is_active'] === 1) ? 'selected' : '' ?>>Active (Billing / In-Service)</option>
              <option value="0" <?= (isset($order['is_active']) && (int)$order['is_active'] === 0) ? 'selected' : '' ?>>Inactive (Suspended / Deactivated)</option>
            </select>
          </div>
        </div>

        <!-- 6. Special Requirements & Audit Reason -->
        <div class="form-group" style="margin-top:20px">
          <label style="font-weight:700">Special Requirements / Technical Notes</label>
          <textarea name="special_requirements" class="form-control" rows="2" placeholder="Any special notes or customer instructions..."><?= e($order['special_requirements'] ?? '') ?></textarea>
        </div>

        <div class="form-group" style="margin-top:20px;padding:16px;background:var(--surface-2);border-radius:6px;border:1px solid var(--border)">
          <label style="color:var(--text-primary);font-weight:700;font-size:0.95rem">Audit Reason for Edit <span class="text-danger">*</span></label>
          <div class="text-muted font-sm" style="margin-bottom:8px">Explain why this order/client detail is being edited. This explanation will be permanently recorded alongside all field diffs in the audit trail.</div>
          <textarea name="audit_reason" class="form-control" rows="3" required placeholder="State operational/business reason for modifying these details (e.g., Customer contact number updated per client request; Corrected circuit ID allocation)..."></textarea>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-warning"><?= svgIcon('check') ?> Save Changes (Audit Logged)</button>
      </div>
    </form>
  </div>
</div>
<!-- Backward compatibility alias if any modal triggers editClosedOrderModal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-modal-open="editClosedOrderModal"]').forEach(function(btn) {
    btn.setAttribute('data-modal-open', 'editOrderModal');
  });
});
</script>
<?php endif; ?>

<?php // Admin / Management / PM Status Override Modal ?>
<?php if ($isAdminUser || $isMgmt || $isPM): ?>
<?php
  $lifecycleMap = [
    'Feasibility Review'        => ['label' => 'Feasibility Review', 'desc' => 'BSA Technical Feasibility Assessment', 'role' => 'BSA'],
    'Await Commercial Approval' => ['label' => 'Await Commercial Approval', 'desc' => 'KAM Commercial Pricing & Margin Review', 'role' => 'KAM'],
    'Management Approval'       => ['label' => 'Management Approval', 'desc' => 'Management Margin / Price Exception Approval', 'role' => 'Management'],
    'Pending SOF'               => ['label' => 'Pending SOF', 'desc' => 'Partner SOF Sign & Upload', 'role' => 'Partner'],
    'SOF Review'                => ['label' => 'SOF Review', 'desc' => 'Neilos Countersignature & Execution', 'role' => 'KAM / Mgmt'],
    'Installation'              => ['label' => 'Installation', 'desc' => 'Contractor Assignment & Field Delivery', 'role' => 'Project Manager'],
    'Testing'                   => ['label' => 'Testing', 'desc' => 'Testing Evidence & NOC IP Configuration', 'role' => 'Project Manager / BSA'],
    'UAT'                       => ['label' => 'UAT', 'desc' => $isEnterpriseOrder ? 'Enterprise UAT & KAM Acceptance' : 'Partner User Acceptance Testing Signoff', 'role' => $isEnterpriseOrder ? 'KAM / PM' : 'Partner'],
    'Closed'                    => ['label' => 'Closed', 'desc' => 'Live Service & Active Billing Triggered', 'role' => 'System / Finance'],
  ];
  $orderedKeys = array_keys($lifecycleMap);
  $currIdx = array_search($status, $orderedKeys);

  if ($currIdx === false) {
      $prevStagesList = $orderedKeys;
      $nextStagesList = [];
  } else {
      $prevStagesList = array_slice($orderedKeys, 0, $currIdx);
      $nextStagesList = array_slice($orderedKeys, $currIdx + 1);
  }
?>
<div class="modal-backdrop" id="statusModal" style="z-index:99999">
  <div class="modal" style="max-width:680px;width:95%;border-radius:var(--radius-lg,12px);box-shadow:var(--shadow-lg);background:var(--surface,#fff);overflow:hidden;border:1px solid var(--border);padding:0">
    <div class="modal-header" style="background:var(--accent-pale,#f1f5f9);padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(15,76,129,0.12);color:var(--accent,#0f4c81);display:flex;align-items:center;justify-content:center;font-size:1.25rem">
          🔄
        </div>
        <div>
          <h3 class="modal-title" style="margin:0;font-size:1.1rem;font-weight:700;color:var(--text-primary)">Lifecycle Status Override</h3>
          <div style="font-size:0.82rem;color:var(--text-secondary)">
            Order <strong>#<?= e($order['order_number']) ?></strong> · Current Stage: <span class="badge <?= orderStatusClass($status) ?>" style="font-size:0.75rem;padding:2px 8px"><?= e($status) ?></span>
          </div>
        </div>
      </div>
      <button class="modal-close" data-modal-close style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <!-- Override Mode Tabs Navigation -->
    <div style="display:flex;border-bottom:1px solid var(--border);background:var(--surface-2,#f8fafc);padding:0 16px;gap:8px">
      <button type="button" class="override-tab-btn active" id="overrideTabBtn_return" onclick="switchOverrideTab('return')" style="padding:12px 16px;background:none;border:none;border-bottom:2px solid var(--primary,#0f4c81);font-weight:600;font-size:0.88rem;color:var(--primary,#0f4c81);cursor:pointer;display:inline-flex;align-items:center;gap:6px">
        ⏪ Return to Previous Stage
      </button>
      <button type="button" class="override-tab-btn" id="overrideTabBtn_skip" onclick="switchOverrideTab('skip')" style="padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;font-weight:500;font-size:0.88rem;color:var(--text-secondary);cursor:pointer;display:inline-flex;align-items:center;gap:6px">
        ⏩ Skip to Next Stage
      </button>
      <button type="button" class="override-tab-btn" id="overrideTabBtn_direct" onclick="switchOverrideTab('direct')" style="padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;font-weight:500;font-size:0.88rem;color:var(--text-secondary);cursor:pointer;display:inline-flex;align-items:center;gap:6px">
        ⚙️ Direct Override
      </button>
    </div>

    <div class="modal-body" style="padding:20px;max-height:70vh;overflow-y:auto">
      
      <!-- ============================================================
           TAB 1: RETURN TO PREVIOUS STAGE
           ============================================================ -->
      <div class="override-tab-content" id="overrideTabContent_return" style="display:block">
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <input type="hidden" name="override_type" value="return">

          <div style="background:rgba(217,119,6,0.08);border:1px solid rgba(217,119,6,0.25);border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.85rem;color:#b45309;display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:1.1rem;line-height:1">⏪</span>
            <div>
              <strong>Return / Rollback Workflow:</strong> Select any preceding stage to roll back this order. Downstream stages will be reset, a permanent return audit record will be logged, and the responsible department will be notified.
            </div>
          </div>

          <?php if (empty($prevStagesList)): ?>
          <div class="alert alert-info" style="font-size:0.88rem">
            This order is currently at the initial stage (<strong><?= e($status) ?></strong>). There are no preceding stages to return to.
          </div>
          <?php else: ?>
          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Select Previous Stage to Return to <span class="text-danger">*</span></label>
            <select name="return_to_status" class="form-control" required style="font-size:0.9rem;padding:9px 12px">
              <option value="">— Select Target Previous Stage —</option>
              <?php foreach (array_reverse($prevStagesList) as $pst): 
                $info = $lifecycleMap[$pst] ?? null;
              ?>
              <option value="<?= e($pst) ?>">
                <?= e($pst) ?><?= $info ? ' (' . e($info['desc']) . ' — ' . e($info['role']) . ')' : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Return Reason Category <span class="text-danger">*</span></label>
            <select name="return_reason_preset" class="form-control" required style="font-size:0.88rem;padding:8px 12px">
              <option value="Client Requested Revision / Modification">Client Requested Revision / Modification</option>
              <option value="Feasibility Re-assessment Required">Feasibility Re-assessment Required</option>
              <option value="Commercial Pricing / NRC / MRC Adjustment">Commercial Pricing / NRC / MRC Adjustment</option>
              <option value="SOF / Service Document Correction">SOF / Service Document Correction</option>
              <option value="Field Delivery / Site Challenges / Material Correction">Field Delivery / Site Challenges / Material Correction</option>
              <option value="Testing / Quality Standards Non-Compliance">Testing / Quality Standards Non-Compliance</option>
              <option value="Administrative Workflow Correction">Administrative Workflow Correction</option>
            </select>
          </div>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Detailed Return Remarks &amp; Instructions <span class="text-danger">*</span></label>
            <textarea name="return_remarks" class="form-control" rows="3" required placeholder="Explain why this order is being returned and what corrections or actions the target team must take..." style="font-size:0.88rem"></textarea>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;border-top:1px solid var(--border);padding-top:14px">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-warning" style="display:inline-flex;align-items:center;gap:6px">
              ⏪ Return Order to Selected Stage
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ============================================================
           TAB 2: FAST-FORWARD / SKIP TO NEXT STAGE
           ============================================================ -->
      <div class="override-tab-content" id="overrideTabContent_skip" style="display:none">
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <input type="hidden" name="override_type" value="skip">

          <div style="background:rgba(15,76,129,0.08);border:1px solid rgba(15,76,129,0.25);border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.85rem;color:var(--primary,#0f4c81);display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:1.1rem;line-height:1">⏩</span>
            <div>
              <strong>Fast-Forward &amp; Auto-Approval:</strong> Skipping ahead to a subsequent stage will automatically approve, satisfy, and record the prerequisite verification milestones (Feasibility, Commercial, SOF, Testing, etc.) for all bypassed intermediate stages.
            </div>
          </div>

          <?php if (empty($nextStagesList)): ?>
          <div class="alert alert-info" style="font-size:0.88rem">
            This order has reached the terminal stage (<strong><?= e($status) ?></strong>). There are no subsequent stages to skip to.
          </div>
          <?php else: ?>
          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Select Target Upcoming Stage <span class="text-danger">*</span></label>
            <select name="skip_to_status" class="form-control" required style="font-size:0.9rem;padding:9px 12px">
              <option value="">— Select Target Future Stage —</option>
              <?php foreach ($nextStagesList as $nst): 
                $info = $lifecycleMap[$nst] ?? null;
              ?>
              <option value="<?= e($nst) ?>">
                <?= e($nst) ?><?= $info ? ' (' . e($info['desc']) . ')' : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Fast-Forward Justification / Audit Reason <span class="text-danger">*</span></label>
            <textarea name="skip_reason" class="form-control" rows="3" required placeholder="State the operational justification for skipping intermediate stages (e.g., Pre-approved SLA fast-track, existing infrastructure verified, customer urgent deployment)..." style="font-size:0.88rem"></textarea>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;border-top:1px solid var(--border);padding-top:14px">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px">
              ⏩ Skip &amp; Advance to Target Stage
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ============================================================
           TAB 3: DIRECT STATUS & SERVICE TYPE OVERRIDE
           ============================================================ -->
      <div class="override-tab-content" id="overrideTabContent_direct" style="display:none">
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <input type="hidden" name="override_type" value="direct">

          <?php if ($status === 'Closed'): ?>
          <div class="alert alert-warning" style="margin-bottom:14px;font-size:0.85rem">
            <strong>⚠️ Notice:</strong> This order is currently <strong>Closed</strong>. An audit reason is mandatory for any status change.
          </div>
          <?php endif; ?>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Direct New Status <span class="text-danger">*</span></label>
            <select name="new_status" class="form-control" required style="font-size:0.9rem;padding:9px 12px">
              <?php foreach ($allStatuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Service Type Override (Optional)</label>
            <select name="new_service_type" class="form-control" style="font-size:0.88rem;padding:8px 12px">
              <option value="">— Keep Current (<?= e($order['service_type']) ?>) —</option>
              <?php 
                $allModalServiceTypes = function_exists('getActiveServiceTypes') ? getActiveServiceTypes($db) : ['FTTH', 'FTTB', 'DIA', 'Layer 2 ( last mile)', 'BIA (Broadband Internet Access)', 'Remote Hands Only'];
                if (!in_array('DIA', $allModalServiceTypes)) $allModalServiceTypes[] = 'DIA';
                foreach ($allModalServiceTypes as $stOpt):
              ?>
              <option value="<?= e($stOpt) ?>"><?= e($stOpt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group mb-16">
            <label style="font-weight:600;font-size:0.88rem;margin-bottom:6px;display:block">Audit Reason / Note <span class="text-danger">*</span></label>
            <textarea name="note" class="form-control" rows="3" required placeholder="Reason for direct status change..." style="font-size:0.88rem"></textarea>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;border-top:1px solid var(--border);padding-top:14px">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save Direct Status Change</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
function switchOverrideTab(tabKey) {
  document.querySelectorAll('.override-tab-btn').forEach(function(btn) {
    btn.style.borderBottomColor = 'transparent';
    btn.style.color = 'var(--text-secondary)';
    btn.classList.remove('active');
  });
  document.querySelectorAll('.override-tab-content').forEach(function(content) {
    content.style.display = 'none';
  });
  
  var activeBtn = document.getElementById('overrideTabBtn_' + tabKey);
  var activeContent = document.getElementById('overrideTabContent_' + tabKey);
  if (activeBtn) {
    activeBtn.style.borderBottomColor = 'var(--primary, #0f4c81)';
    activeBtn.style.color = 'var(--primary, #0f4c81)';
    activeBtn.classList.add('active');
  }
  if (activeContent) {
    activeContent.style.display = 'block';
  }
}
</script>
<?php endif; ?>

<!-- ============================================================
     ROLE-BASED ACTION PREVIEW MODAL
     (Allows BSA, KAM, Partner, PM, Management to review changes before submitting)
============================================================ -->
<div class="modal-backdrop" id="orderActionPreviewModal" style="display:none;z-index:999999">
  <div class="modal" style="max-width:760px;width:95%;border-radius:var(--radius-lg,12px);box-shadow:var(--shadow-lg);background:var(--surface,#fff);overflow:hidden;border:1px solid var(--border)">
    <div class="modal-header" style="background:var(--accent-pale,#f1f5f9);padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(15,76,129,0.12);color:var(--accent,#0f4c81);display:flex;align-items:center;justify-content:center;font-size:1.25rem" id="actionPreviewModalIcon">
          🔍
        </div>
        <div>
          <div style="display:flex;align-items:center;gap:8px">
            <h3 class="modal-title" id="actionPreviewModalTitle" style="font-size:1.05rem;font-weight:700;color:var(--text-primary);margin:0">Action Preview &amp; Verification</h3>
            <span class="badge badge-primary" id="actionPreviewModalRoleBadge" style="font-size:0.72rem;padding:2px 8px">Review</span>
          </div>
          <div class="font-sm text-muted" id="actionPreviewModalSubtitle">Order #<?= e($order['order_number']) ?> · <?= e($order['customer_name']) ?> (<?= e($order['service_type']) ?>)</div>
        </div>
      </div>
      <button type="button" class="modal-close" onclick="closeActionPreviewModal()" style="font-size:1.4rem;background:none;border:none;cursor:pointer;color:var(--text-muted)">&times;</button>
    </div>
    
    <div class="modal-body" id="actionPreviewModalBody" style="padding:20px;max-height:68vh;overflow-y:auto">
      <!-- Dynamic role-specific breakdown rendered here -->
    </div>

    <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border);background:var(--surface-2,#f8fafc);display:flex;align-items:center;justify-content:space-between;gap:10px">
      <button type="button" class="btn btn-secondary" onclick="closeActionPreviewModal()">
        ← Back to Edit
      </button>
      <button type="button" class="btn btn-success" id="actionPreviewConfirmBtn" onclick="confirmActionPreviewSubmission()">
        <?= svgIcon('check') ?> <span id="actionPreviewConfirmBtnText">Confirm &amp; Proceed</span>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  let pendingSubmissionForm = null;

  window.openActionPreviewModal = function(config) {
    const modal = document.getElementById('orderActionPreviewModal');
    if (!modal) return;

    pendingSubmissionForm = config.form || null;

    document.getElementById('actionPreviewModalIcon').innerHTML = config.icon || '🔍';
    document.getElementById('actionPreviewModalTitle').textContent = config.title || 'Action Preview';
    document.getElementById('actionPreviewModalRoleBadge').textContent = config.role || 'Review';
    if (config.subtitle) {
      document.getElementById('actionPreviewModalSubtitle').textContent = config.subtitle;
    }
    document.getElementById('actionPreviewConfirmBtnText').textContent = config.confirmText || 'Confirm & Proceed';
    document.getElementById('actionPreviewModalBody').innerHTML = config.html || '';

    modal.classList.add('open');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.closeActionPreviewModal = function() {
    const modal = document.getElementById('orderActionPreviewModal');
    if (modal) {
      modal.classList.remove('open');
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
    pendingSubmissionForm = null;
  };

  window.confirmActionPreviewSubmission = function() {
    if (pendingSubmissionForm) {
      const form = pendingSubmissionForm;
      form.dataset.previewConfirmed = '1';
      closeActionPreviewModal();
      form.submit();
    } else {
      closeActionPreviewModal();
    }
  };

  // 1. BSA Feasibility Preview
  window.previewBsaSubmission = function() {
    const form = document.getElementById('bsaFeasibilityForm');
    if (!form) return;

    const remarks = document.getElementById('bsa_technical_remarks')?.value.trim();
    if (!remarks) {
      neilosAlert('Please enter technical remarks describing infrastructure requirements.', 'Remarks Required', '⚠️');
      document.getElementById('bsa_technical_remarks')?.focus();
      return;
    }

    const revisedNrc = form.querySelector('input[name="revised_nrc"]')?.value.trim();
    const justification = form.querySelector('input[name="nrc_justification"]')?.value.trim();
    const rawJson = document.getElementById('nrcBreakdownJson')?.value;
    let bomList = [];
    try {
      if (rawJson) bomList = JSON.parse(rawJson);
    } catch(e) {}

    let bomRowsHtml = '';
    let bomTotal = 0;
    if (Array.isArray(bomList) && bomList.length > 0) {
      bomList.forEach(item => {
        const cat = item.category || 'Device';
        const isDev = (cat === 'Device');
        const isMat = (cat === 'Material');
        const badgeCls = isDev ? 'badge-primary' : (isMat ? 'badge-info' : 'badge-secondary');
        const badgeStyle = isMat ? 'style="background:#0284c7;color:#fff"' : '';
        const qty = item.qty || 1;
        const price = parseFloat(item.unit_price) || 0;
        const lineTot = parseFloat(item.total) || (qty * price);
        bomTotal += lineTot;

        const devDetails = isDev ? [item.brand, item.model, item.serial ? `SN: ${item.serial}` : ''].filter(Boolean).join(' · ') : '';

        bomRowsHtml += `
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:6px 8px">
              <span class="badge ${badgeCls}" ${badgeStyle} style="font-size:0.68rem;padding:2px 6px">${cat}</span>
              <strong style="margin-left:4px">${escapeHtml(item.description || 'Item')}</strong>
              ${devDetails ? `<div class="font-sm text-muted" style="font-size:0.75rem">${escapeHtml(devDetails)}</div>` : ''}
            </td>
            <td style="padding:6px 8px;text-align:center">${qty}</td>
            <td style="padding:6px 8px;text-align:right">$${price.toFixed(2)}</td>
            <td style="padding:6px 8px;text-align:right;font-weight:600">$${lineTot.toFixed(2)}</td>
          </tr>
        `;
      });
    }

    const html = `
      <div style="display:flex;flex-direction:column;gap:16px">
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px 16px">
          <div style="font-weight:700;font-size:0.9rem;margin-bottom:8px;color:var(--text-primary)">
            📐 Feasibility Summary &amp; Proposed NRC
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span style="color:var(--text-secondary)">Standard Service NRC:</span>
            <span style="font-weight:600">$<?= number_format((float)($order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between">
            <span style="color:var(--text-secondary)">BSA Confirmed / Revised NRC:</span>
            <span style="font-weight:700;color:var(--accent);font-size:1.05rem">$${revisedNrc ? revisedNrc : '<?= number_format((float)($order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?>'}</span>
          </div>
          ${justification ? `<div style="margin-top:8px;font-size:0.83rem;color:var(--text-muted)"><strong>Justification:</strong> ${escapeHtml(justification)}</div>` : ''}
        </div>

        ${bomRowsHtml ? `
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px 14px">
          <div style="font-weight:700;font-size:0.85rem;margin-bottom:6px;display:flex;justify-content:space-between">
            <span>Bill of Materials (BoM) Items</span>
            <span class="badge badge-primary">${bomList.length} Items</span>
          </div>
          <table style="width:100%;font-size:0.82rem;border-collapse:collapse">
            <thead>
              <tr style="background:var(--surface-1);color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left">
                <th style="padding:6px 8px">Item / Material / Service</th>
                <th style="padding:6px 8px;text-align:center;width:45px">Qty</th>
                <th style="padding:6px 8px;text-align:right">Unit Price</th>
                <th style="padding:6px 8px;text-align:right">Total</th>
              </tr>
            </thead>
            <tbody>${bomRowsHtml}</tbody>
          </table>
        </div>` : ''}

        <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:12px 16px">
          <div style="font-weight:700;font-size:0.85rem;color:#334155;margin-bottom:4px">Technical Remarks &amp; Special Conditions:</div>
          <div style="font-size:0.875rem;color:#0f172a;white-space:pre-wrap">${escapeHtml(remarks)}</div>
        </div>

        <div class="alert alert-info" style="margin:0;font-size:0.83rem">
          ℹ️ Submitting will mark this order as <strong>Technically Feasible</strong> and route it to the <strong>Account Manager (KAM)</strong> for commercial review.
        </div>
      </div>
    `;

    openActionPreviewModal({
      form: form,
      title: 'BSA Feasibility Review — Final Verification',
      role: 'BSA Architect',
      icon: '📐',
      confirmText: 'Mark Feasible & Proceed to KAM',
      html: html
    });
  };

  // 2. KAM Commercial Review Preview
  window.previewKamSubmission = function() {
    const form = document.getElementById('kamApproveForm');
    if (!form) return;

    const usdTzsRate  = document.getElementById('usd_tzs_rate_input')?.value.trim() || '2600.00';
    const proposedMrc = document.getElementById('kam_proposed_mrc_input')?.value.trim();
    const proposedNrc = document.getElementById('kam_proposed_nrc_input')?.value.trim();
    const justification = document.getElementById('kam_commercial_justification_input')?.value.trim();
    const remarks = document.getElementById('kam_remarks_input')?.value.trim();

    const isException = (proposedMrc !== '' || proposedNrc !== '');
    if (isException && !justification) {
      neilosAlert('Commercial justification is mandatory when proposing exception pricing.', 'Justification Required', '⚠️');
      document.getElementById('kam_commercial_justification_input')?.focus();
      return;
    }

    const html = `
      <div style="display:flex;flex-direction:column;gap:16px">
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px 16px">
          <div style="font-weight:700;font-size:0.9rem;margin-bottom:10px;color:var(--text-primary)">
            💼 Commercial Pricing Summary
          </div>
          <table style="width:100%;font-size:0.875rem;border-collapse:collapse">
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:6px 0;color:var(--text-secondary)">Exchange Rate (TZS/USD)</td>
              <td style="padding:6px 0;text-align:right;font-weight:700;color:var(--primary)">TZS ${escapeHtml(usdTzsRate)} / USD</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:6px 0;color:var(--text-secondary)">Standard MRC (Price Book)</td>
              <td style="padding:6px 0;text-align:right;font-weight:600">$<?= number_format((float)($order['standard_mrc'] ?? $order['base_mrc']), 2) ?></td>
            </tr>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:6px 0;color:var(--text-secondary)">KAM Proposed MRC</td>
              <td style="padding:6px 0;text-align:right;font-weight:700;color:${proposedMrc ? 'var(--accent)' : 'inherit'}">${proposedMrc ? '$' + proposedMrc : 'Standard Rate'}</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:6px 0;color:var(--text-secondary)">BSA Confirmed NRC</td>
              <td style="padding:6px 0;text-align:right;font-weight:600">$<?= number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 2) ?></td>
            </tr>
            <tr>
              <td style="padding:6px 0;color:var(--text-secondary)">KAM Proposed NRC Exception</td>
              <td style="padding:6px 0;text-align:right;font-weight:700;color:${proposedNrc ? 'var(--accent)' : 'inherit'}">${proposedNrc ? '$' + proposedNrc : 'Standard / BSA Confirmed'}</td>
            </tr>
          </table>
        </div>

        ${justification ? `
        <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:8px;padding:12px 16px">
          <div style="font-weight:700;font-size:0.85rem;color:#92400e;margin-bottom:4px">Commercial Justification:</div>
          <div style="font-size:0.875rem;color:#78350f">${escapeHtml(justification)}</div>
        </div>` : ''}

        ${remarks ? `
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px 16px">
          <div style="font-weight:700;font-size:0.85rem;color:var(--text-primary);margin-bottom:4px">KAM Remarks:</div>
          <div style="font-size:0.875rem;color:var(--text-secondary)">${escapeHtml(remarks)}</div>
        </div>` : ''}

        <div class="alert ${isException ? 'alert-warning' : 'alert-success'}" style="margin:0;font-size:0.83rem">
          ${isException ? '⚠️ Discount / exception proposed: This order will be routed to <strong>Management Approval</strong>.' : '✅ Standard terms confirmed: Order will move directly to <strong>Pending SOF</strong> for Partner generation and signing.'}
        </div>
      </div>
    `;

    openActionPreviewModal({
      form: form,
      title: 'KAM Commercial Decision — Final Verification',
      role: 'Account Manager (KAM)',
      icon: '💼',
      confirmText: 'Submit Commercial Decision',
      html: html
    });
  };

  // 3. Management Decision Preview
  window.previewMgmtSubmission = function() {
    const form = document.getElementById('mgmtDecisionForm');
    if (!form) return;

    const decision = form.querySelector('input[name="management_decision"]:checked')?.value;
    if (!decision) {
      neilosAlert('Please select a management decision option.', 'Decision Required', '⚠️');
      return;
    }

    const returnRemarks = document.getElementById('mgmt_return_remarks_input')?.value.trim();
    if (decision === 'Return to KAM' && !returnRemarks) {
      neilosAlert('Return remarks are mandatory when returning an order to KAM.', 'Remarks Required', '⚠️');
      document.getElementById('mgmt_return_remarks_input')?.focus();
      return;
    }

    const finalNrc = document.getElementById('mgmt_final_nrc_input')?.value.trim();
    const finalMrc = document.getElementById('mgmt_final_mrc_input')?.value.trim();
    const notes = document.getElementById('mgmt_decision_notes_input')?.value.trim();

    const html = `
      <div style="display:flex;flex-direction:column;gap:16px">
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px 16px">
          <div style="font-weight:700;font-size:0.9rem;margin-bottom:10px;color:var(--text-primary)">
            🏛️ Management Determination
          </div>
          <div style="font-size:1.05rem;font-weight:800;color:var(--primary);margin-bottom:8px">
            ${escapeHtml(decision)}
          </div>
          ${decision === 'Approve with Revised Price' ? `
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border)">
            <div><strong>Final Approved NRC:</strong> <span style="font-weight:700;color:#166534">$${finalNrc || '0.00'}</span></div>
            <div><strong>Final Approved MRC:</strong> <span style="font-weight:700;color:#166534">$${finalMrc || '0.00'}</span></div>
          </div>` : ''}
        </div>

        ${decision === 'Return to KAM' && returnRemarks ? `
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px">
          <div style="font-weight:700;font-size:0.85rem;color:#991b1b;margin-bottom:4px">Return Remarks for KAM:</div>
          <div style="font-size:0.875rem;color:#7f1d1d">${escapeHtml(returnRemarks)}</div>
        </div>` : ''}

        ${notes ? `
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px 16px">
          <div style="font-weight:700;font-size:0.85rem;color:var(--text-primary);margin-bottom:4px">Audit Notes:</div>
          <div style="font-size:0.875rem;color:var(--text-secondary)">${escapeHtml(notes)}</div>
        </div>` : ''}

        <div class="alert alert-info" style="margin:0;font-size:0.83rem">
          ℹ️ Your authoritative determination will be logged in the price audit trail and stakeholders will be notified.
        </div>
      </div>
    `;

    openActionPreviewModal({
      form: form,
      title: 'Management Determination — Final Verification',
      role: 'Management Review',
      icon: '🏛️',
      confirmText: 'Confirm Management Decision',
      html: html
    });
  };

  // Intercept all forms marked with .order-action-form
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form.order-action-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        // If already confirmed inside preview modal, skip intercept
        if (form.dataset.previewConfirmed === '1') {
          return true;
        }

        e.preventDefault();
        const actionTitle = form.dataset.actionTitle || 'Confirm Order Action';
        const actionRole = form.dataset.actionRole || 'Order Workflow';

        // Check for file inputs
        const fileInputs = form.querySelectorAll('input[type="file"]');
        let fileDetailsHtml = '';
        fileInputs.forEach(fi => {
          if (fi.files && fi.files.length > 0) {
            fileDetailsHtml += '<div style="margin-top:8px;font-size:0.85rem"><strong>Selected File(s):</strong><ul style="margin:4px 0 0 16px">';
            for (let i = 0; i < fi.files.length; i++) {
              const file = fi.files[i];
              fileDetailsHtml += `<li><code>${escapeHtml(file.name)}</code> (${(file.size / 1024).toFixed(1)} KB)</li>`;
            }
            fileDetailsHtml += '</ul></div>';
          }
        });

        // Check for text inputs or textareas
        const textInputs = form.querySelectorAll('input[type="text"], textarea, select');
        let fieldListHtml = '';
        textInputs.forEach(inp => {
          if (inp.name && !inp.name.includes('csrf_token') && !inp.name.includes('order_id') && inp.value) {
            const label = inp.closest('.form-group')?.querySelector('label')?.textContent.replace('*', '').trim() || inp.name;
            fieldListHtml += `
              <div style="margin-bottom:8px">
                <span style="color:var(--text-secondary);font-size:0.82rem">${escapeHtml(label)}:</span>
                <div style="font-weight:600;font-size:0.88rem;color:var(--text-primary)">${escapeHtml(inp.value)}</div>
              </div>
            `;
          }
        });

        const html = `
          <div style="display:flex;flex-direction:column;gap:14px">
            <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px 16px">
              <div style="font-weight:700;font-size:0.9rem;margin-bottom:10px;color:var(--text-primary)">
                📝 Submission Details
              </div>
              ${fieldListHtml || '<div style="font-size:0.85rem;color:var(--text-secondary)">No text parameters to review.</div>'}
              ${fileDetailsHtml}
            </div>
            <div class="alert alert-info" style="margin:0;font-size:0.83rem">
              ℹ️ Please verify that all information entered is accurate before proceeding.
            </div>
          </div>
        `;

        openActionPreviewModal({
          form: form,
          title: actionTitle,
          role: actionRole,
          icon: '📝',
          confirmText: 'Confirm & Submit',
          html: html
        });
      });
    });
  });

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})();
</script>