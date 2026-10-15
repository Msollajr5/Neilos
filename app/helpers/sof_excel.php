<?php
/**
 * Neilos SOF Excel Generator
 * Populates a copy of the master Neilos SOF.xlsx template with dynamic order data.
 */

function generateSOFExcel(array $order, ?array $partnerKyc = null): string {
    // Resolve authoritative partner KYC if not provided
    if ($partnerKyc === null) {
        $partnerKyc = getAuthoritativePartnerKyc(getDB(), (int)$order['partner_id']);
    }

    $isEnterprise = (
        ($order['partner_category'] ?? '') === 'Enterprise Client' ||
        ($order['partner_type'] ?? '') === 'Enterprise' ||
        ($order['partner_type'] ?? '') === 'Government' ||
        (isset($partnerKyc['raw']['partner_category']) && $partnerKyc['raw']['partner_category'] === 'Enterprise Client') ||
        (isset($partnerKyc['raw']['partner_type']) && in_array($partnerKyc['raw']['partner_type'], ['Enterprise', 'Government']))
    );

    $enterpriseTemplate = dirname(__DIR__, 2) . '/SOF -Template  for Enterprise Clients.xlsx';
    $standardTemplate   = dirname(__DIR__, 2) . '/Neilos  SOF.xlsx';

    $templatePath = ($isEnterprise && file_exists($enterpriseTemplate)) ? $enterpriseTemplate : $standardTemplate;
    if (!file_exists($templatePath)) {
        $templatePath = file_exists($enterpriseTemplate) ? $enterpriseTemplate : (file_exists($standardTemplate) ? $standardTemplate : '');
        if (!$templatePath) {
            throw new RuntimeException("Master SOF template not found.");
        }
    }

    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/sof';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = 'SOF-' . preg_replace('/[^A-Za-z0-9\-]/', '', $order['order_number']) . '.xlsx';
    $targetPath = $uploadDir . '/' . $filename;

    // 1. Create a copy of the master template
    if (!copy($templatePath, $targetPath)) {
        throw new RuntimeException("Failed to copy master SOF template.");
    }

    // Commercial calculation source of truth
    $hasRevNrc = ($order['revised_nrc'] !== null && $order['revised_nrc'] !== '');
    $stdNrc    = (float)($order['standard_nrc'] ?? $order['base_nrc_usd'] ?? 0);
    $baseNrc   = $hasRevNrc ? (float)$order['revised_nrc'] : $stdNrc;
    $rhNrc     = (float)($order['remote_hands_nrc_usd'] ?? 0);
    $nrcSub    = $baseNrc + $rhNrc;
    $vatNrc    = round($nrcSub * 0.18, 2);
    $totNrc    = round($nrcSub + $vatNrc, 2);

    $hasMgmtPrice = ($order['management_approved_price'] !== null && $order['management_approved_price'] !== '');
    $hasRevMrc    = ($order['revised_mrc'] !== null && $order['revised_mrc'] !== '');
    $stdMrc       = (float)($order['standard_mrc'] ?? $order['base_mrc'] ?? 0);
    $mrcVal       = $hasMgmtPrice ? (float)$order['management_approved_price'] : ($hasRevMrc ? (float)$order['revised_mrc'] : $stdMrc);
    $vatMrc       = round($mrcVal * 0.18, 2);
    $totMrc       = round($mrcVal + $vatMrc, 2);

    // Open copied zip file and modify sharedStrings.xml & sheet1.xml if ZipArchive available
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($targetPath) === true) {
            $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
            $sheetXmlContent  = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sharedXmlContent && $sheetXmlContent) {
                $sharedXml = simplexml_load_string($sharedXmlContent);

                // Helper to get string index or add string
                $getStringIndex = function($text) use (&$sharedXml) {
                    $idx = 0;
                    foreach ($sharedXml->si as $si) {
                        $t = isset($si->t) ? (string)$si->t : '';
                        if (!$t && isset($si->r)) {
                            foreach ($si->r as $r) { $t .= (string)$r->t; }
                        }
                        if ($t === $text) return $idx;
                        $idx++;
                    }
                    // Append new string
                    $si = $sharedXml->addChild('si');
                    $si->addChild('t', htmlspecialchars($text));
                    return count($sharedXml->si) - 1;
                };

                // Perform string replacements in existing sharedStrings array
                $companyName = $partnerKyc['company_name'] ?? ($order['partner_name'] ?? '');
                $exchangeRate = !empty($order['usd_tzs_rate']) && (float)$order['usd_tzs_rate'] > 0 ? (float)$order['usd_tzs_rate'] : (defined('USD_TZS_RATE') ? (float)USD_TZS_RATE : 2700);
                $customerName = $order['customer_name'] ?? '';
                if ($isEnterprise) {
                    $sofNumber = !empty($order['sof_number']) ? $order['sof_number'] : ('Sav\\SOF\\' . str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT));
                    $replacements = [
                        'SMARTCODES LTD' => strtoupper($customerName ?: $companyName),
                        'Sav\\SOF\\0015' => $sofNumber,
                    ];
                } else {
                    $replacements = [
                        'SOF Number'                 => 'SOF Number: ' . $order['order_number'],
                        'Customer Name'              => 'Customer Name: ' . $customerName,
                        'Company  Name (KYC)'        => 'Company Name: ' . $companyName,
                        "NEILOS NETWORK Feasibility" => 'Tool Feasibility',
                    ];
                }

                foreach ($sharedXml->si as $si) {
                    if (isset($si->t)) {
                        $str = (string)$si->t;
                        if (isset($replacements[$str])) {
                            $si->t = $replacements[$str];
                        }
                    }
                }

                $zip->addFromString('xl/sharedStrings.xml', $sharedXml->asXML());
            }
            $zip->close();
        }
    }

    return $targetPath;
}
