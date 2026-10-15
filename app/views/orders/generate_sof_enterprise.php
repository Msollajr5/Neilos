<?php
/**
 * Service Order Form (SOF) — Savanna Business Enterprise
 * Produces the exact visual layout, colors, typography, borders, and structure
 * of "SOF -Template  for Enterprise Clients.xlsx" and "SOF_Savanna_Business_Enterprise.pdf".
 */

try {
    $db = getDB();
    $neilosInfo = $db->query("SELECT * FROM neilos_company_info WHERE id = 1")->fetch() ?: [];
} catch (Exception $e) {
    $neilosInfo = [];
}

// Ensure Authoritative Partner KYC
if (!isset($partnerKyc) || empty($partnerKyc)) {
    $partnerKyc = getAuthoritativePartnerKyc($db, (int)$order['partner_id']) ?: [];
}
$rawKyc = $partnerKyc['raw'] ?? [];

// SOF Number
$sofNumber = !empty($order['sof_number']) ? $order['sof_number'] : ('Sav\\SOF\\' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));
if (empty($order['sof_number']) && !empty($order['id'])) {
    try {
        $db->prepare("UPDATE orders SET sof_number = ? WHERE id = ? AND (sof_number IS NULL OR sof_number = '')")
           ->execute([$sofNumber, $order['id']]);
    } catch (Exception $e) {}
}

// Logo resolution — ALWAYS use the official Savanna Business Enterprise logo
$rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2);
$logoFile = $rootDir . '/public/assets/img/savanna_logo.png';
$logoSrc = '';
if (file_exists($logoFile)) {
    $mime = mime_content_type($logoFile) ?: 'image/png';
    $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
} else {
    $logoSrc = (defined('APP_URL') ? APP_URL : '') . '/assets/img/savanna_logo.png';
}

// Commercial calculations
$comm   = getOrderCommercialSummary($order);
$sofCurr      = $comm['currency'] ?: 'USD';
$mrcVal       = (float)$comm['mrc_val'];
$vatMrc       = (float)$comm['vat_mrc'];
$totMrc       = (float)$comm['total_mrc'];
$nrcSub       = (float)$comm['nrc_subtotal'];
$vatNrc       = (float)$comm['vat_nrc'];
$totNrc       = (float)$comm['total_nrc'];
$exchangeRate = (!empty($order['usd_tzs_rate']) && (float)$order['usd_tzs_rate'] > 0)
    ? (float)$order['usd_tzs_rate']
    : (defined('USD_TZS_RATE') ? (float)USD_TZS_RATE : 2700);
$isTZS = ($sofCurr === 'TZS');
$isUSD = ($sofCurr === 'USD');

// Customer / KYC Details
$customerName = trim($order['customer_name'] ?? '');
if (!$customerName) {
    $customerName = trim($partnerKyc['company_name'] ?? ($order['partner_name'] ?? ''));
}
$kycTechName  = trim($order['customer_contact_name'] ?: ($partnerKyc['tech_contact_name']  ?? ''));
$kycTechPhone = trim($order['customer_contact_phone'] ?: ($partnerKyc['tech_contact_phone'] ?? ''));
$kycTechEmail = trim($order['customer_contact_email'] ?: ($partnerKyc['tech_contact_email'] ?? ''));
$kycBillName  = trim($partnerKyc['billing_contact_name']  ?? '');
$kycBillPhone = trim($partnerKyc['billing_contact_phone'] ?? '');
$kycBillEmail = trim($partnerKyc['billing_contact_email'] ?? '');

$authSignName  = trim($rawKyc['auth_signatory_name']  ?? ($partnerKyc['auth_signatory_name']  ?? ''));
$authSignTitle = trim($rawKyc['auth_signatory_title'] ?? '');

// Technical / Order Details
$contractTerm = $order['contract_term'] ? preg_replace('/[^0-9]/', '', $order['contract_term']) : '12';
$aEndGps      = $order['gps_coordinates']   ?? '';
$aEndStreet   = $order['customer_location'] ?? '';
$bEndGps      = $order['b_end_gps']         ?? '';
$bEndStreet   = $order['b_end_street']      ?? '';
$nniPort      = !empty($order['nni_port_size']) ? $order['nni_port_size'] : '';
$bw           = $order['fttx_package'] ?: ($order['aggregate_capacity'] ? $order['aggregate_capacity'].' Mbps' : ($order['bandwidth'] ? $order['bandwidth'].' Mbps' : ''));

$st = $order['service_type'] ?? '';
$serviceTypeLabel = 'DIA';
if (str_contains($st, 'Layer 2') || str_contains($st, 'Dedicated Layer 2')) {
    $serviceTypeLabel = 'Layer 2';
} elseif (str_contains($st, 'Dark Fiber') || str_contains($st, 'FTTH') || str_contains($st, 'FTTB')) {
    $serviceTypeLabel = 'Dark Fiber';
} elseif (str_contains($st, 'SDWAN') || str_contains($st, 'SD-WAN')) {
    $serviceTypeLabel = 'SD-WAN';
} elseif (str_contains($st, 'Wi-Fi') || str_contains($st, 'WiFi')) {
    $serviceTypeLabel = 'Managed Wi-Fi';
} elseif (str_contains($st, 'MPLS')) {
    $serviceTypeLabel = 'MPLS';
}

// Commercial amounts (fallback to template values if order not priced yet)
$mrcFormatted    = $mrcVal > 0 ? number_format($mrcVal, 2) : '262.96';
$vatMrcFormatted = $vatMrc > 0 ? number_format($vatMrc, 2) : '47.33';
$totMrcFormatted = $totMrc > 0 ? number_format($totMrc, 2) : '310.29';
$nrcFormatted    = $nrcSub > 0 ? number_format($nrcSub, 2) : '-';
$vatNrcFormatted = $vatNrc > 0 ? number_format($vatNrc, 2) : '-';
$totNrcFormatted = $totNrc > 0 ? number_format($totNrc, 2) : '-';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SERVICE ORDER FORM (SOF) - SAVANNA BUSINESS ENTERPRISE</title>
  <style>
    @page {
      size: letter landscape;
      margin: 5mm 6mm;
    }
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Calibri, 'Segoe UI', Arial, sans-serif;
      font-size: 8pt;
      color: #000;
      background: #e2e8f0;
      line-height: 1.25;
    }
    .top-bar {
      position: sticky;
      top: 0;
      z-index: 9999;
      background: #1e293b;
      color: #fff;
      padding: 7px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .top-bar-left, .top-bar-right { display: flex; align-items: center; gap: 10px; }
    .btn-back { background: rgba(255,255,255,.14); color: #fff; border: 1px solid rgba(255,255,255,.3); padding: 5px 12px; border-radius: 4px; font-weight: 700; font-size: 8pt; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
    .btn-print { background: #ea580c; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; font-weight: 700; font-size: 8pt; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
    .top-bar-title { font-weight: 700; font-size: 8.5pt; }

    .sof-sheet-container {
      width: 100%;
      max-width: 1080px;
      margin: 15px auto 30px;
      padding: 0;
      background: #fff;
      box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }

    /* Master Table Grid */
    table.sof-grid {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      border: 2.5px solid #F79646;
      background: #fff;
      font-size: 7.5pt;
      color: #000;
    }

    table.sof-grid td, table.sof-grid th {
      border: 0.5pt solid #808080;
      padding: 2px 5px;
      vertical-align: middle;
      overflow: hidden;
      word-wrap: break-word;
    }

    /* Column Widths (matching Excel columns B to O) */
    .c-b { width: 18.0%; }
    .c-c { width: 8.2%; }
    .c-d { width: 2.1%; }
    .c-e { width: 5.4%; }
    .c-f { width: 2.1%; }
    .c-g { width: 12.0%; }
    .c-h { width: 17.9%; }
    .c-i { width: 9.6%; }
    .c-j { width: 2.0%; }
    .c-k { width: 6.9%; }
    .c-l { width: 2.0%; }
    .c-m { width: 6.2%; }
    .c-n { width: 2.3%; }
    .c-o { width: 5.3%; }

    /* Fills matching Excel Theme & Palettes */
    .bg-orange-banner {
      background-color: #F79646 !important;
      color: #FFFFFF !important;
      font-weight: 700;
      text-transform: uppercase;
    }
    .bg-kyc-orange {
      background-color: #FF9900 !important;
      color: #000000 !important;
      font-weight: 700;
    }
    .bg-peach-label {
      background-color: #FDE9D9 !important;
      color: #000000 !important;
      font-weight: 700;
    }
    .bg-terms {
      background-color: #FCD5B4 !important;
      color: #000000 !important;
    }
    .bg-gray-sep {
      background-color: #7F7F7F !important;
      height: 8px !important;
      padding: 0 !important;
    }
    .bg-white-sep {
      background-color: #FFFFFF !important;
      height: 5px !important;
      padding: 0 !important;
      border-left: 2.5px solid #F79646;
      border-right: 2.5px solid #F79646;
    }

    /* Alignments */
    .text-center { text-align: center; }
    .text-right  { text-align: right; }
    .text-left   { text-align: left; }
    .align-top   { vertical-align: top; }
    .font-bold   { font-weight: 700; }
    .font-normal { font-weight: normal; }

    /* Checkmark */
    .chk {
      font-family: Arial, sans-serif;
      font-weight: 700;
      font-size: 8.5pt;
      display: inline-block;
    }

    /* Print Setup */
    @media print {
      @page {
        size: letter landscape;
        margin: 4mm 6mm;
      }
      html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      .top-bar, .no-print { display: none !important; }
      .sof-sheet-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
      }
      table.sof-grid {
        width: 100% !important;
        border: 2.5px solid #F79646 !important;
      }
    }
  </style>
</head>
<body>

<div class="top-bar no-print">
  <div class="top-bar-left">
    <button type="button" onclick="goBack()" class="btn-back">&#8592; Go Back</button>
    <span class="top-bar-title">SERVICE ORDER FORM (SOF) - SAVANNA BUSINESS ENTERPRISE &mdash; <?= e($sofNumber) ?> (Order #<?= e($order['order_number']) ?>)</span>
  </div>
  <div class="top-bar-right">
    <button type="button" onclick="window.print()" class="btn-print">&#128438; Print / Save PDF</button>
    <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $order['id'] ?>" class="btn-back">Order Details</a>
  </div>
</div>

<div class="sof-sheet-container">
  <table class="sof-grid">
    <colgroup>
      <col class="c-b">
      <col class="c-c">
      <col class="c-d">
      <col class="c-e">
      <col class="c-f">
      <col class="c-g">
      <col class="c-h">
      <col class="c-i">
      <col class="c-j">
      <col class="c-k">
      <col class="c-l">
      <col class="c-m">
      <col class="c-n">
      <col class="c-o">
    </colgroup>

    <tbody>
      <!-- ROWS 2 & 3: TITLE & LOGO -->
      <tr>
        <td colspan="9" rowspan="2" class="text-center font-bold" style="font-size: 13.5pt; border-top: 2.5px solid #F79646; border-left: 2.5px solid #F79646; padding: 12px 6px;">
          SERVICE ORDER FORM (SOF) - SAVANNA BUSINESS ENTERPRISE
        </td>
        <td colspan="5" rowspan="4" class="text-center" style="border-top: 2.5px solid #F79646; border-right: 2.5px solid #F79646; background: #fff; padding: 4px;">
          <img src="<?= $logoSrc ?>" alt="Savanna Business Enterprise" style="height: 60px; width: auto; object-fit: contain; display: inline-block;">
        </td>
      </tr>
      <tr>
        <!-- Row 3 covered by rowspans -->
      </tr>

      <!-- ROW 4: SECTION 1 BANNERS -->
      <tr>
        <td colspan="6" class="bg-orange-banner" style="border-left: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          CUSTOMER INFORMATION
        </td>
        <td colspan="3" class="bg-orange-banner" style="font-size: 8pt; padding: 2.5px 6px;">
          SAVANNA BIUSINESS ENTERPRISE INFORMATION
        </td>
      </tr>

      <!-- ROW 5: COMPANY NAME & SOF NUMBER -->
      <tr>
        <td class="bg-kyc-orange" style="border-left: 2.5px solid #F79646;">Company  Name (KYC)</td>
        <td colspan="5" class="font-normal" style="font-weight:600;"><?= e($customerName) ?></td>
        <td class="bg-peach-label">SOF Number</td>
        <td colspan="2" class="font-bold"><?= e($sofNumber) ?></td>
      </tr>

      <!-- ROW 6: TECHNICAL CONTACT NAME -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Technical contact Name </td>
        <td colspan="5"><?= e($kycTechName) ?></td>
        <td class="bg-peach-label">Technical contact Name </td>
        <td colspan="7" class="font-bold" style="border-right: 2.5px solid #F79646;">Tony Koskei</td>
      </tr>

      <!-- ROW 7: PHONE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Phone</td>
        <td colspan="5"><?= e($kycTechPhone) ?></td>
        <td class="bg-peach-label">Phone</td>
        <td colspan="7" class="font-bold" style="border-right: 2.5px solid #F79646;">677050940</td>
      </tr>

      <!-- ROW 8: EMAIL -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Email</td>
        <td colspan="5"><?= e($kycTechEmail) ?></td>
        <td class="bg-peach-label">Email</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;">tonny.koskei@savannafibre.co.tz</td>
      </tr>

      <!-- ROW 9: SEPARATOR (GRAY) -->
      <tr>
        <td colspan="14" class="bg-gray-sep" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 10: BILLING CONTACT NAME -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Billing contact Name</td>
        <td colspan="5"><?= e($kycBillName) ?></td>
        <td class="bg-peach-label">Billing contact Name</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 11: BILLING PHONE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Phone</td>
        <td colspan="5"><?= e($kycBillPhone) ?></td>
        <td class="bg-peach-label">Phone</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 12: BILLING EMAIL -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Email</td>
        <td colspan="5"><?= e($kycBillEmail) ?></td>
        <td class="bg-peach-label">Email</td>
        <td colspan="7" class="font-bold" style="border-right: 2.5px solid #F79646;">financetz@savannafibre.co.tz</td>
      </tr>

      <!-- ROW 13: SEPARATOR (WHITE/HAIR) -->
      <tr>
        <td colspan="14" class="bg-white-sep"></td>
      </tr>

      <!-- ROW 14: SERVICE REQUEST & SITE DETAILS BANNERS -->
      <tr>
        <td colspan="6" class="bg-orange-banner" style="border-left: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          SERVICE REQUEST
        </td>
        <td colspan="8" class="bg-orange-banner" style="border-right: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          SITE DETAILS
        </td>
      </tr>

      <!-- ROW 15: BANDWIDTH & NNI PORT -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Bandwidth</td>
        <td colspan="5"><?= e($bw) ?></td>
        <td class="bg-peach-label">NNI Port Size <em>(1G, 10G, 40G)</em></td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"><?= e($nniPort) ?></td>
      </tr>

      <!-- ROW 16: SERVICE TYPE (ROWSPAN 4) & A-END GPS -->
      <tr>
        <td rowspan="4" class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Service Type</td>
        <td><?= e($serviceTypeLabel) ?></td>
        <td class="text-center font-bold"><span class="chk">&#10004;</span></td>
        <td colspan="3"></td>
        <td class="bg-peach-label">A-End Site GPS Cordiantes</td>
        <td colspan="5" class="text-center"><?= e($aEndGps ?: 'Dar Es Salaam') ?></td>
        <td colspan="2" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 17: A-END STREET & CITY (ROWSPAN 2) -->
      <tr>
        <td colspan="5"></td>
        <td rowspan="2" class="bg-peach-label">A-End Street name &amp; City</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"><?= e($aEndStreet ?: 'Mikocheni A Senga Road') ?></td>
      </tr>

      <!-- ROW 18: BLANK UNDER A-END STREET -->
      <tr>
        <td colspan="5"></td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 19: B-END SITE GPS -->
      <tr>
        <td colspan="5"></td>
        <td class="bg-peach-label">B-End Site GPS Cordiantes</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"><?= e($bEndGps) ?></td>
      </tr>

      <!-- ROW 20: INITIAL REMOTE HANDS & B-END STREET -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Initial Remote hands required</td>
        <td colspan="5">YES</td>
        <td class="bg-peach-label">B-end Street name &amp; City</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;"><?= e($bEndStreet) ?></td>
      </tr>

      <!-- ROW 21: NEW CROSS CONNECT & TOOL FEASIBILITY -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">New Cross Connect Requred</td>
        <td colspan="5">YES</td>
        <td class="bg-peach-label">Savanna's tool Feasibility</td>
        <td colspan="2" class="text-right">Feasible &nbsp;<span class="chk">&#10004;</span></td>
        <td colspan="2" class="text-right">Not-Feasible</td>
        <td colspan="3" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 22: SEPARATOR (WHITE/HAIR) -->
      <tr>
        <td colspan="14" class="bg-white-sep"></td>
      </tr>

      <!-- ROW 23: COMMERCIAL BANNER -->
      <tr>
        <td colspan="14" class="bg-orange-banner" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          COMMERCIAL
        </td>
      </tr>

      <!-- ROW 24: BILLING CYCLE & CURRENCY OF PAYMENT -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Billing Cycle</td>
        <td class="text-right">MONTHLY</td>
        <td class="text-center font-bold"><span class="chk">&#10004;</span></td>
        <td colspan="3"></td>
        <td class="bg-peach-label">&nbsp;Currency of Payment</td>
        <td class="text-right">TZS</td>
        <td class="text-center font-bold"><span class="chk"><?= $isTZS ? '&#10004;' : '' ?></span></td>
        <td class="text-right">USD</td>
        <td class="text-center font-bold"><span class="chk"><?= $isUSD ? '&#10004;' : '' ?></span></td>
        <td colspan="3" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 25: MINIMUM TERM & USD TO TZ RATE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Minimum Term (Months)</td>
        <td class="text-right font-bold"><?= e($contractTerm) ?></td>
        <td colspan="4"></td>
        <td class="bg-peach-label">USD to TZ rate</td>
        <td colspan="2" class="text-right font-bold"><?= number_format($exchangeRate, 2) ?></td>
        <td colspan="5" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 26: MONTHLY CHARGES & SET-UP CHARGES -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Monthly Charges </td>
        <td class="text-right font-bold"><?= $mrcFormatted ?></td>
        <td colspan="4"></td>
        <td class="bg-peach-label">Set-Up Charges </td>
        <td colspan="2" class="text-right font-bold"><?= $nrcFormatted ?></td>
        <td colspan="5" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 27: VAT 18% -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">VAT 18%</td>
        <td class="text-right font-bold"><?= $vatMrcFormatted ?></td>
        <td colspan="4"></td>
        <td class="bg-peach-label">VAT 18%</td>
        <td colspan="2" class="text-right font-bold"><?= $vatNrcFormatted ?></td>
        <td colspan="5" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 28: TOTAL MRC CHARGE & TOTAL NRC CHARGE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Total MRC Charge</td>
        <td class="text-right font-bold"><?= $totMrcFormatted ?></td>
        <td colspan="4"></td>
        <td class="bg-peach-label">Total NRC Charge</td>
        <td colspan="2" class="text-right font-bold"><?= $totNrcFormatted ?></td>
        <td colspan="5" style="border-right: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROWS 29 TO 42: TERMS AND CONDITIONS (PEACH BACKGROUND) -->
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; height: 6px; padding: 0;"></td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms font-bold" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7.5pt; padding: 1px 6px;">
          TERMS AND CONDITIONS
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          1. All Invoices shall be raise in TZS at the rate specified above
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          2. These Terms and Conditions constitute the agreement for services between Savanna Business Enterprise Tanzania and the Client.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          3. This agreement shall, subject to the applicable termination provisions in the Master Services Agreement, continue for the initial fixed term indicated in this Service Order Form and shall commence from the Service activation date at the Customer's premises.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          4. At the end of the initial term, the Service will automatically renew for successive renewal terms equal in length to the initial term unless and until
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          terminated by either Party in any given renewal term, with 30 days' termination notice. Such termination by the Customer shall be in writing and delivered to the Account Manager.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          5. Billing for the Services shall commence on the date that the respective Service has been activated by Savanna Business Enterprise.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          6. Savanna Business Enterprise will invoice the Customer monthly for payment in full within 30 days of the date of the invoice. Savanna Business Enterprise reserves the right to suspend the Service if the Customer defaults on payment and may, at its discretion, suspend services where charges have not been paid by the due date.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          7. If the customer terminates the service before the end of the initial term, Customer shall pay the Early Termination Fee and refund any discounts received.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          Payment shall be made within  (15) days of termination.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          8. By signing this Service Order Form, the Customer confirms the accuracy of the information provided, agrees to notify Savanna Business Enterprise of any changes, and acknowledges that this Form,.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-style: italic; font-size: 7pt; padding: 0.5px 6px;">
          9. The construction, validity and performance of these Terms and Conditions shall be governed by the Laws of the United Republic of Tanzania.
        </td>
      </tr>
      <tr>
        <td colspan="14" class="bg-terms" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; height: 6px; padding: 0;"></td>
      </tr>

      <!-- ROW 43: DECLARATION BANNER -->
      <tr>
        <td colspan="14" class="bg-orange-banner" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          DECLARATION 
        </td>
      </tr>

      <!-- ROW 44: DECLARATION TEXT -->
      <tr>
        <td colspan="14" class="align-top font-normal" style="border-left: 2.5px solid #F79646; border-right: 2.5px solid #F79646; font-size: 7pt; padding: 3px 6px; line-height: 1.3;">
          By signing below, the authorized representatives of both parties agree to the terms and conditions outlined in this Service Order Form and the referenced Master Service Agreement. Savanna  is authorized to proceed with the installation and provisioning of the service described herein.
        </td>
      </tr>

      <!-- ROW 45: SIGNATURE PARTIES (ORANGE BANNERS) -->
      <tr>
        <td style="border-left: 2.5px solid #F79646; background: #fff;"></td>
        <td colspan="5" class="bg-orange-banner" style="font-size: 8pt; padding: 2.5px 6px;">
          <?= strtoupper(e($customerName)) ?>
        </td>
        <td colspan="8" class="bg-orange-banner" style="border-right: 2.5px solid #F79646; font-size: 8pt; padding: 2.5px 6px;">
          Savanna Business Enterprise
        </td>
      </tr>

      <!-- ROW 46: NAME -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Name</td>
        <td colspan="5"><?= e($authSignName) ?></td>
        <td class="bg-peach-label">Name</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;">Tony Koskei</td>
      </tr>

      <!-- ROW 47: TITLE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Title</td>
        <td colspan="5"><?= e($authSignTitle) ?></td>
        <td class="bg-peach-label">Title</td>
        <td colspan="7" style="border-right: 2.5px solid #F79646;">Authorized Officer</td>
      </tr>

      <!-- ROW 48: SIGNATURE & DATE -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646;">Signature &amp; Date</td>
        <td colspan="5" rowspan="2" class="align-top" style="border-bottom: 2.5px solid #F79646;"></td>
        <td class="bg-peach-label">Signature &amp; Date</td>
        <td colspan="7" rowspan="2" class="align-top" style="border-right: 2.5px solid #F79646; border-bottom: 2.5px solid #F79646;"></td>
      </tr>

      <!-- ROW 49: STAMP -->
      <tr>
        <td class="bg-peach-label" style="border-left: 2.5px solid #F79646; border-bottom: 2.5px solid #F79646;">Stamp</td>
        <td class="bg-peach-label" style="border-bottom: 2.5px solid #F79646;">Stamp</td>
      </tr>
    </tbody>
  </table>
</div>

<script>
function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else if (window.opener && !window.opener.closed) {
    window.close();
  } else {
    window.location.href = '<?= APP_URL ?>/?page=order_detail&id=<?= (int)$order['id'] ?>';
  }
}
</script>
</body>
</html>