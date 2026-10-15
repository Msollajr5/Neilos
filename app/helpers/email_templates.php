<?php
// ============================================================
// Neilos Partner Portal — Modern Responsive HTML Email Templates
// ============================================================

if (!function_exists('getPortalBaseUrl')) {
    function getPortalBaseUrl(): string {
        $envUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
        if (!empty($envUrl)) return rtrim($envUrl, '/');
        
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$scheme}://{$host}/Neilos/public";
    }
}

/**
 * Base wrapper for consistent portal email layout
 */
function wrapEmailHtml(string $title, string $subtitle, string $contentHtml, string $ctaText = '', string $ctaUrl = '', string $accentColor = '#2563eb'): string {
    $baseUrl = getPortalBaseUrl();
    $ctaButton = '';
    if (!empty($ctaText) && !empty($ctaUrl)) {
        $ctaButton = "
        <div style=\"text-align:center;margin:32px 0 24px 0\">
            <a href=\"{$ctaUrl}\" style=\"display:inline-block;background:{$accentColor};color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:6px;box-shadow:0 4px 12px rgba(37,99,235,0.25);letter-spacing:0.3px\">
                {$ctaText} &rarr;
            </a>
        </div>";
    }

    return "
<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<title>{$title}</title>
</head>
<body style=\"margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;-webkit-font-smoothing:antialiased\">
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color:#f1f5f9;padding:30px 15px\">
  <tr>
    <td align=\"center\">
      <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width:620px;background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(15,23,42,0.08);border:1px solid #e2e8f0\">
        
        <!-- Header -->
        <tr>
          <td style=\"background:#0f172a;padding:26px 32px;text-align:left;border-bottom:3px solid {$accentColor}\">
            <div style=\"display:flex;align-items:center;justify-content:space-between\">
              <div>
                <span style=\"color:#ffffff;font-size:20px;font-weight:800;letter-spacing:0.5px\">NEILOS</span>
                <span style=\"color:#94a3b8;font-size:13px;margin-left:8px;font-weight:500;text-transform:uppercase;letter-spacing:1px\">Partner Portal</span>
              </div>
            </div>
            <div style=\"color:#f8fafc;font-size:18px;font-weight:700;margin-top:14px\">{$title}</div>
            " . (!empty($subtitle) ? "<div style=\"color:#94a3b8;font-size:13px;margin-top:4px\">{$subtitle}</div>" : "") . "
          </td>
        </tr>

        <!-- Main Body -->
        <tr>
          <td style=\"padding:32px 32px 24px 32px;line-height:1.6;font-size:14px;color:#334155\">
            {$contentHtml}
            {$ctaButton}
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style=\"background-color:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;line-height:1.5;text-align:center\">
            <div>This is an automated notification from the <strong>Neilos Partner Portal</strong>.</div>
            <div style=\"margin-top:4px\">If you have questions, please contact our Network Operations & Support team at <a href=\"mailto:support@neilosnetwork.co.tz\" style=\"color:{$accentColor};text-decoration:none\">support@neilosnetwork.co.tz</a>.</div>
            <div style=\"margin-top:8px;color:#94a3b8\">&copy; " . date('Y') . " Neilos Network Ltd. All rights reserved.</div>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>";
}

/**
 * 1. Workflow Action Required Email (e.g. Feasibility Review, Commercial Review, Management Approval, SOF Sign, Contractor Assignment, Testing)
 */
function renderWorkflowActionEmail(
    string $recipientName,
    string $actorName,
    string $actorRole,
    array $order,
    string $actionTitle,
    string $actionDescription,
    string $actionUrl = ''
): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($actionUrl) ? $actionUrl : ($baseUrl . '/?page=orders&action=detail&id=' . ($order['id'] ?? 0));
    $orderNum  = htmlspecialchars($order['order_number'] ?? 'N/A');
    $custName  = htmlspecialchars($order['customer_name'] ?? 'N/A');
    $svcType   = htmlspecialchars($order['service_type'] ?? 'N/A');
    $createdOn = !empty($order['created_at']) ? date('d M Y, H:i', strtotime($order['created_at'])) : date('d M Y');
    $status    = htmlspecialchars($order['status'] ?? 'Pending');
    $partner   = htmlspecialchars($order['partner_name'] ?? 'Partner');

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
    
    <div style=\"background:#eff6ff;border-left:4px solid #2563eb;padding:14px 16px;border-radius:4px;margin:18px 0\">
      <div style=\"font-weight:700;color:#1e40af;font-size:15px;margin-bottom:4px\">{$actionTitle}</div>
      <div style=\"color:#1e3a8a;font-size:13.5px\">{$actionDescription}</div>
      <div style=\"margin-top:8px;font-size:12.5px;color:#3b82f6\">
        <strong>Submitted by:</strong> " . htmlspecialchars($actorName) . " (" . htmlspecialchars($actorRole) . ")
      </div>
    </div>

    <div style=\"font-weight:700;color:#0f172a;font-size:14px;margin:20px 0 10px 0\">Order Summary Details:</div>
    <table border=\"0\" cellpadding=\"6\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;font-size:13.5px;margin-bottom:16px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0\">
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b;width:35%\">Order Number:</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#0f172a\">{$orderNum}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Customer / Site:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$custName}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Service Type:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$svcType}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Partner / Client:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$partner}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Current Status:</td>
        <td style=\"padding:8px 12px\"><span style=\"background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:4px;font-weight:600;font-size:12px\">{$status}</span></td>
      </tr>
      <tr>
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Created Date:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$createdOn}</td>
      </tr>
    </table>

    <p style=\"font-size:13.5px;color:#475569;margin-bottom:4px\">Please log into the Neilos Partner Portal to review the full details and submit your approval or feedback.</p>
    ";

    return wrapEmailHtml(
        "Action Required: {$orderNum}",
        "{$svcType} &bull; {$custName}",
        $content,
        "Login & Review Order",
        $targetUrl,
        '#2563eb'
    );
}

/**
 * 2. Multi-Recipient Order Closed Notification (Management, Partner, KAM, Finance)
 */
function renderOrderClosedEmail(
    string $recipientName,
    string $recipientRole,
    array $order,
    string $closedDate = '',
    string $activeServiceId = ''
): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = $baseUrl . '/?page=orders&action=detail&id=' . ($order['id'] ?? 0);
    $orderNum  = htmlspecialchars($order['order_number'] ?? 'N/A');
    $custName  = htmlspecialchars($order['customer_name'] ?? 'N/A');
    $svcType   = htmlspecialchars($order['service_type'] ?? 'N/A');
    $partner   = htmlspecialchars($order['partner_name'] ?? 'Partner');
    $createdOn = !empty($order['created_at']) ? date('d M Y', strtotime($order['created_at'])) : 'N/A';
    $closedOn  = !empty($closedDate) ? date('d M Y, H:i', strtotime($closedDate)) : date('d M Y, H:i');
    $serviceId = htmlspecialchars($activeServiceId ?: ($order['order_number'] ?? 'N/A'));

    $finalMrc  = number_format((float)($order['final_mrc'] ?? $order['mrc_amount'] ?? 0), 2);
    $finalNrc  = number_format((float)($order['final_nrc'] ?? $order['nrc_amount'] ?? 0), 2);
    $rate      = number_format((float)($order['usd_tzs_rate'] ?? 2600), 2);

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Dear <strong>" . htmlspecialchars($recipientName) . "</strong> (" . htmlspecialchars($recipientRole) . "),</p>
    
    <div style=\"background:#ecfdf5;border-left:4px solid #10b981;padding:16px 18px;border-radius:4px;margin:18px 0\">
      <div style=\"font-weight:700;color:#065f46;font-size:16px;margin-bottom:6px\">🎉 Service Activated &amp; Order Closed</div>
      <div style=\"color:#047857;font-size:14px;line-height:1.5\">
        We are pleased to inform you that <strong>Order {$orderNum}</strong> (created on <strong>{$createdOn}</strong> for <strong>{$partner}</strong>) has successfully passed all technical testing and User Acceptance Testing (UAT), and has now been <strong>CLOSED &amp; ACTIVATED</strong>.
      </div>
    </div>

    <div style=\"font-weight:700;color:#0f172a;font-size:14px;margin:22px 0 10px 0\">Commercial &amp; Service Delivery Summary:</div>
    <table border=\"0\" cellpadding=\"7\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;font-size:13.5px;margin-bottom:18px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0\">
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b;width:38%\">Order Number:</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#0f172a\">{$orderNum}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Active Service ID:</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#10b981\">{$serviceId}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Partner / Client:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$partner}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">End Customer / Site:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$custName}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Service Type:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$svcType}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Order Created On:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$createdOn}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Order Closed On:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$closedOn}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0;background:#ffffff\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Approved Monthly Fee (MRC):</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#0f172a\">\$" . $finalMrc . " USD</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0;background:#ffffff\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Approved Install Fee (NRC):</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#0f172a\">\$" . $finalNrc . " USD</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Exchange Rate Applied:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">{$rate} TZS / USD</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Billing Terms:</td>
        <td style=\"padding:8px 12px;color:#0f172a\">Monthly in Advance &bull; Net 30 Days</td>
      </tr>
      <tr>
        <td style=\"padding:8px 12px;font-weight:600;color:#64748b\">Billing Start Date:</td>
        <td style=\"padding:8px 12px;font-weight:700;color:#10b981\">" . date('d M Y') . "</td>
      </tr>
    </table>

    <p style=\"font-size:13.5px;color:#475569\">Finance &amp; Billing have received this notice to commence recurring monthly invoicing.</p>
    ";

    return wrapEmailHtml(
        "Order Closed & Live: {$orderNum}",
        "Service Activated &bull; {$partner}",
        $content,
        "View Closed Order",
        $targetUrl,
        '#10b981'
    );
}

/**
 * 3. Password Reset Email
 */
function renderPasswordResetEmail(string $recipientName, string $resetUrl, int $expiresInMinutes = 5): string {
    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
    <p style=\"font-size:14px;color:#475569\">We received a request to reset the password for your Neilos Partner Portal account.</p>
    
    <div style=\"background:#eff6ff;border-left:4px solid #2563eb;padding:14px 16px;border-radius:4px;margin:18px 0;font-size:13.5px;color:#1e40af\">
      <strong>Security Notice:</strong> For your protection, this reset link is valid for <strong>{$expiresInMinutes} minutes only</strong>. Once used or if a new link is requested, it will expire immediately.
    </div>

    <p style=\"font-size:14px;color:#475569\">Click the button below to set your new password:</p>
    ";

    return wrapEmailHtml(
        "Reset Your Password",
        "Neilos Partner Portal Account Security",
        $content,
        "Reset Password & Sign In",
        $resetUrl,
        '#2563eb'
    );
}

/**
 * 4. Two-Factor OTP Code Email
 */
function renderOtpEmail(string $recipientName, string $otpCode, int $expiresInMinutes = 10): string {
    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
    <p style=\"font-size:14px;color:#475569\">Here is your one-time verification code (OTP) for logging into the Neilos Partner Portal:</p>
    
    <div style=\"text-align:center;margin:28px 0;background:#f8fafc;padding:20px;border-radius:8px;border:1px dashed #cbd5e1\">
      <div style=\"font-size:32px;font-weight:800;letter-spacing:10px;color:#0f172a;font-family:monospace\">{$otpCode}</div>
      <div style=\"font-size:12px;color:#64748b;margin-top:8px\">Valid for {$expiresInMinutes} minutes &bull; Do not share this code</div>
    </div>

    <p style=\"font-size:13px;color:#64748b\">If you did not attempt to log in, please secure your account immediately or contact portal administrators.</p>
    ";

    return wrapEmailHtml(
        "Your Login OTP Code",
        "Two-Factor Authentication",
        $content,
        "",
        "",
        '#0f172a'
    );
}

/**
 * 5. KYC Status Notification Email
 */
function renderKycStatusEmail(string $recipientName, string $partnerName, string $status, string $remarks = '', string $kycUrl = ''): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($kycUrl) ? $kycUrl : ($baseUrl . '/?page=kyc');
    $color     = match($status) {
        'Approved' => '#10b981',
        'Rejected' => '#ef4444',
        default    => '#2563eb'
    };

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
    <p style=\"font-size:14px;color:#334155\">
      The KYC Compliance Application for <strong>" . htmlspecialchars($partnerName) . "</strong> has been updated to:
      <strong style=\"color:{$color}\">" . htmlspecialchars($status) . "</strong>.
    </p>
    " . (!empty($remarks) ? "<div style=\"background:#f8fafc;padding:12px 16px;border-radius:6px;border:1px solid #e2e8f0;margin:16px 0;font-size:13.5px\"><strong>Remarks / Feedback:</strong> " . htmlspecialchars($remarks) . "</div>" : "") . "
    ";

    return wrapEmailHtml(
        "KYC Application: {$status}",
        htmlspecialchars($partnerName),
        $content,
        "View KYC Application",
        $targetUrl,
        $color
    );
}

/**
 * 6. Welcome New User & One-Time Login Credentials Email
 */
function renderWelcomeCredentialsEmail(string $recipientName, string $username, string $tempPassword, string $role, string $loginUrl = ''): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($loginUrl) ? $loginUrl : ($baseUrl . '/?page=login');
    $uname     = htmlspecialchars($username);
    $rName     = htmlspecialchars($recipientName);
    $rRole     = htmlspecialchars($role);
    $tPass     = htmlspecialchars($tempPassword);

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Welcome, <strong>{$rName}</strong>!</p>
    <p style=\"font-size:14px;color:#334155;line-height:1.6\">
      Your user account has been successfully created on the <strong>Neilos Partner Portal</strong> with the role of <strong>{$rRole}</strong>.
    </p>

    <div style=\"font-weight:700;color:#0f172a;font-size:14px;margin:22px 0 10px 0\">Your Login Credentials:</div>
    <table border=\"0\" cellpadding=\"8\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;font-size:13.5px;margin-bottom:18px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0\">
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b;width:35%\">Username / Email:</td>
        <td style=\"padding:10px 14px;font-weight:700;color:#0f172a\">{$uname}</td>
      </tr>
      <tr style=\"border-bottom:1px solid #e2e8f0;background:#ffffff\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b\">Temporary Password:</td>
        <td style=\"padding:10px 14px;font-weight:800;color:#2563eb;font-family:monospace;font-size:15px;letter-spacing:1px\">{$tPass}</td>
      </tr>
      <tr>
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b\">Assigned Role:</td>
        <td style=\"padding:10px 14px;font-weight:600;color:#0f172a\">{$rRole}</td>
      </tr>
    </table>

    <div style=\"background:#fffbeb;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:4px;margin:18px 0;font-size:13px;color:#92400e;line-height:1.5\">
      <strong>Important Security Notice:</strong> This is a one-time temporary password. Upon logging in for the first time, the portal will immediately prompt you to set your own permanent, secure password before accessing the system.
    </div>
    ";

    return wrapEmailHtml(
        "Welcome to Neilos Partner Portal",
        "Your Account Has Been Created &bull; {$uname}",
        $content,
        "Sign In to Your Account",
        $targetUrl,
        '#2563eb'
    );
}

/**
 * 7. Admin Reset Password Notice Email
 */
function renderAdminPasswordResetNoticeEmail(string $recipientName, string $username, string $tempPassword, string $loginUrl = ''): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($loginUrl) ? $loginUrl : ($baseUrl . '/?page=login');
    $uname     = htmlspecialchars($username);
    $rName     = htmlspecialchars($recipientName);
    $tPass     = htmlspecialchars($tempPassword);

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>{$rName}</strong>,</p>
    <p style=\"font-size:14px;color:#334155;line-height:1.6\">
      Your password for the <strong>Neilos Partner Portal</strong> has been reset by an administrator.
    </p>

    <div style=\"font-weight:700;color:#0f172a;font-size:14px;margin:22px 0 10px 0\">Your New Temporary Credentials:</div>
    <table border=\"0\" cellpadding=\"8\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;font-size:13.5px;margin-bottom:18px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0\">
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b;width:35%\">Username / Email:</td>
        <td style=\"padding:10px 14px;font-weight:700;color:#0f172a\">{$uname}</td>
      </tr>
      <tr style=\"background:#ffffff\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b\">Temporary Password:</td>
        <td style=\"padding:10px 14px;font-weight:800;color:#2563eb;font-family:monospace;font-size:15px;letter-spacing:1px\">{$tPass}</td>
      </tr>
    </table>

    <div style=\"background:#fffbeb;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:4px;margin:18px 0;font-size:13px;color:#92400e;line-height:1.5\">
      <strong>Security Requirement:</strong> Please use this temporary password to log in. You will be required to change it to a new permanent password immediately upon signing in.
    </div>
    ";

    return wrapEmailHtml(
        "Your Password Has Been Reset",
        "Neilos Partner Portal &bull; {$uname}",
        $content,
        "Sign In to Your Account",
        $targetUrl,
        '#2563eb'
    );
}

/**
 * 8. Password Changed Security Confirmation Email
 */
function renderPasswordChangedConfirmationEmail(string $recipientName, string $username, string $loginUrl = ''): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($loginUrl) ? $loginUrl : ($baseUrl . '/?page=login');
    $uname     = htmlspecialchars($username);
    $rName     = htmlspecialchars($recipientName);
    $changeTime = date('d M Y, H:i:s \E\A\T');

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>{$rName}</strong>,</p>
    <p style=\"font-size:14px;color:#334155;line-height:1.6\">
      This is a security confirmation that your password for account <strong>{$uname}</strong> on the <strong>Neilos Partner Portal</strong> was successfully changed on <strong>{$changeTime}</strong>.
    </p>

    <div style=\"background:#f0fdf4;border-left:4px solid #10b981;padding:14px 16px;border-radius:4px;margin:18px 0;font-size:13px;color:#166534;line-height:1.5\">
      <strong>Security Notice:</strong> If you performed this change, no further action is required. If you did not make this change, please contact our support team immediately at <a href=\"mailto:support@neilosnetwork.co.tz\" style=\"color:#166534;font-weight:700\">support@neilosnetwork.co.tz</a>.
    </div>
    ";

    return wrapEmailHtml(
        "Security Alert: Password Changed",
        "Neilos Partner Portal &bull; {$uname}",
        $content,
        "Access Your Account",
        $targetUrl,
        '#10b981'
    );
}

/**
 * 9. Credentials Updated by Admin Email (Custom Password)
 */
function renderCredentialsUpdatedByAdminEmail(string $recipientName, string $username, string $newPassword, string $loginUrl = ''): string {
    $baseUrl   = getPortalBaseUrl();
    $targetUrl = !empty($loginUrl) ? $loginUrl : ($baseUrl . '/?page=login');
    $uname     = htmlspecialchars($username);
    $rName     = htmlspecialchars($recipientName);
    $tPass     = htmlspecialchars($newPassword);

    $content = "
    <p style=\"font-size:15px;color:#1e293b;margin-top:0\">Hello <strong>{$rName}</strong>,</p>
    <p style=\"font-size:14px;color:#334155;line-height:1.6\">
      Your login credentials for the <strong>Neilos Partner Portal</strong> have been updated by a system administrator.
    </p>

    <div style=\"font-weight:700;color:#0f172a;font-size:14px;margin:22px 0 10px 0\">Your Updated Login Credentials:</div>
    <table border=\"0\" cellpadding=\"8\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;font-size:13.5px;margin-bottom:18px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0\">
      <tr style=\"border-bottom:1px solid #e2e8f0\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b;width:35%\">Username / Email:</td>
        <td style=\"padding:10px 14px;font-weight:700;color:#0f172a\">{$uname}</td>
      </tr>
      <tr style=\"background:#ffffff\">
        <td style=\"padding:10px 14px;font-weight:600;color:#64748b\">New Password:</td>
        <td style=\"padding:10px 14px;font-weight:800;color:#2563eb;font-family:monospace;font-size:15px;letter-spacing:1px\">{$tPass}</td>
      </tr>
    </table>

    <p style=\"font-size:13.5px;color:#475569\">You may log in with your updated credentials using the button below.</p>
    ";

    return wrapEmailHtml(
        "Your Account Credentials Have Been Updated",
        "Neilos Partner Portal &bull; {$uname}",
        $content,
        "Sign In to Your Account",
        $targetUrl,
        '#2563eb'
    );
}
