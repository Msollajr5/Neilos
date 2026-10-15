<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password — Neilos Partner Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
  <script>document.documentElement.setAttribute('data-theme','light');</script>
  <style>
    .pwd-requirements {
      background: var(--surface-2, #f8fafc);
      border: 1px solid var(--border, #e2e8f0);
      border-radius: var(--radius-sm, 8px);
      padding: 12px 14px;
      margin: 14px 0 18px 0;
      font-size: 0.8rem;
    }
    .pwd-requirements-title {
      font-weight: 700;
      color: var(--text-primary, #0f172a);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .pwd-rule {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted, #64748b);
      margin-bottom: 4px;
      transition: color 0.15s ease;
    }
    .pwd-rule:last-child { margin-bottom: 0; }
    .pwd-rule.met {
      color: #16a34a;
      font-weight: 500;
    }
    .pwd-rule-dot {
      width: 13px;
      height: 13px;
      border-radius: 50%;
      background: #cbd5e1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 9px;
      color: #fff;
      transition: all 0.15s ease;
    }
    .pwd-rule.met .pwd-rule-dot {
      background: #16a34a;
    }
  </style>
</head>
<body>
<div class="login-page">
  <div class="login-card" style="max-width:480px">
    <div class="login-logo">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=4" alt="Neilos" class="login-logo-img">
      <div class="login-subtitle">Set New Password</div>
    </div>

    <?php if (!empty($resetErrors)): ?>
    <div class="alert alert-danger" style="margin-bottom:18px">
      <ul style="margin:0;padding-left:18px">
        <?php foreach ($resetErrors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($tokenValid)): ?>
    <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:var(--radius-sm, 8px);padding:12px 14px;margin-bottom:18px;font-size:0.86rem;line-height:1.4">
      🔒 <strong>Account Verified:</strong> Setting new password for <strong><?= e($resetRecord['full_name'] ?? 'User') ?></strong> (<?= e($resetRecord['username'] ?? $resetRecord['email']) ?>).
    </div>

    <form class="login-form" method="POST" action="<?= APP_URL ?>/?page=reset_password&token=<?= e($token) ?>" id="resetPasswordForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="new_password">New Password <span class="required">*</span></label>
        <div class="input-group">
          <input type="password" id="new_password" name="new_password" class="form-control" required
                 placeholder="Enter new strong password" autocomplete="new-password"
                 style="border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
          <button type="button" class="input-addon" onclick="toggleField('new_password')" style="cursor:pointer;border-left:none;border-radius:0 var(--radius-sm) var(--radius-sm) 0" title="Show/hide password">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
        <div class="input-group">
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                 placeholder="Confirm new password" autocomplete="new-password"
                 style="border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
          <button type="button" class="input-addon" onclick="toggleField('confirm_password')" style="cursor:pointer;border-left:none;border-radius:0 var(--radius-sm) var(--radius-sm) 0" title="Show/hide password">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- Password Requirements Checklist -->
      <div class="pwd-requirements">
        <div class="pwd-requirements-title">
          <span>🛡️</span> Security Requirements:
        </div>
        <div class="pwd-rule" id="rule-length"><span class="pwd-rule-dot">✓</span> Minimum 8 characters</div>
        <div class="pwd-rule" id="rule-upper"><span class="pwd-rule-dot">✓</span> At least 1 uppercase letter (A-Z)</div>
        <div class="pwd-rule" id="rule-lower"><span class="pwd-rule-dot">✓</span> At least 1 lowercase letter (a-z)</div>
        <div class="pwd-rule" id="rule-num"><span class="pwd-rule-dot">✓</span> At least 1 number (0-9)</div>
        <div class="pwd-rule" id="rule-symbol"><span class="pwd-rule-dot">✓</span> At least 1 special character (!@#$%^&*)</div>
        <div class="pwd-rule" id="rule-match"><span class="pwd-rule-dot">✓</span> Passwords match</div>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg">
        Set New Password &amp; Continue
      </button>
    </form>
    <?php else: ?>
    <div class="alert alert-danger" style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px">
      <svg style="flex-shrink:0;margin-top:2px" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div style="font-size:0.9rem;line-height:1.5">
        <strong>Invalid or Expired Link</strong>
        <div style="margin-top:4px"><?= e($invalidTokenMessage ?? 'This password reset link is invalid, has already been used, or has expired (links are strictly valid for 5 minutes only).') ?></div>
      </div>
    </div>
    <div style="text-align:center;margin-top:20px">
      <a href="<?= APP_URL ?>/?page=forgot_password" class="btn btn-primary w-100 btn-lg" style="text-decoration:none">
        Request New Reset Link
      </a>
    </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:24px;font-size:0.86rem">
      <a href="<?= APP_URL ?>/?page=login" style="color:var(--primary);text-decoration:none;font-weight:600">
        &larr; Back to Sign In
      </a>
    </div>
  </div>
</div>

<script>
function toggleField(fieldId) {
  var input = document.getElementById(fieldId);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}

(function() {
  var newPwd = document.getElementById('new_password');
  var confPwd = document.getElementById('confirm_password');

  var rLength = document.getElementById('rule-length');
  var rUpper = document.getElementById('rule-upper');
  var rLower = document.getElementById('rule-lower');
  var rNum = document.getElementById('rule-num');
  var rSymbol = document.getElementById('rule-symbol');
  var rMatch = document.getElementById('rule-match');

  function checkRules() {
    if (!newPwd) return;
    var val = newPwd.value || '';
    var conf = confPwd ? (confPwd.value || '') : '';

    if (val.length >= 8) { rLength.classList.add('met'); } else { rLength.classList.remove('met'); }
    if (/[A-Z]/.test(val)) { rUpper.classList.add('met'); } else { rUpper.classList.remove('met'); }
    if (/[a-z]/.test(val)) { rLower.classList.add('met'); } else { rLower.classList.remove('met'); }
    if (/[0-9]/.test(val)) { rNum.classList.add('met'); } else { rNum.classList.remove('met'); }
    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val)) { rSymbol.classList.add('met'); } else { rSymbol.classList.remove('met'); }
    if (val.length > 0 && val === conf) { rMatch.classList.add('met'); } else { rMatch.classList.remove('met'); }
  }

  if (newPwd) newPwd.addEventListener('input', checkRules);
  if (confPwd) confPwd.addEventListener('input', checkRules);
})();
</script>
</body>
</html>
