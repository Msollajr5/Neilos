<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — Neilos Partner Portal</title>
  <meta name="description" content="Reset your Neilos Partner Portal account password">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
  <script>document.documentElement.setAttribute('data-theme','light');</script>
</head>
<body>
<div class="login-page">
  <div class="login-card" style="max-width:460px">
    <div class="login-logo">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=4" alt="Neilos" class="login-logo-img">
      <div class="login-subtitle">Self-Service Password Reset</div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
      <span><?= e($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($forgotError)): ?>
    <div class="alert alert-danger" style="display:flex;align-items:flex-start;gap:10px;margin-bottom:18px">
      <svg style="flex-shrink:0;margin-top:2px" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?= e($forgotError) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($resetSent)): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm, 8px);padding:20px;margin-bottom:20px;text-align:center">
      <div style="width:48px;height:48px;background:#dcfce7;color:#16a34a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      
      <div style="font-size:1.05rem;font-weight:700;color:#166534;margin-bottom:6px">
        Reset Link Sent Successfully!
      </div>
      
      <p style="font-size:0.86rem;color:#374151;margin-bottom:12px;line-height:1.5">
        A secure password reset link has been dispatched to your registered email address:
      </p>

      <div style="font-family:monospace;font-size:1.08rem;font-weight:700;color:#0f172a;background:#ffffff;padding:10px 14px;border-radius:6px;border:1px solid #cbd5e1;display:inline-block;letter-spacing:0.5px;margin-bottom:14px">
        <?= e($sentMaskedEmail) ?>
      </div>

      <div style="background:#ffffff;border:1px dashed #cbd5e1;border-radius:6px;padding:12px;font-size:0.82rem;color:#64748b;text-align:left;line-height:1.5">
        <div style="font-weight:600;color:#1e293b;margin-bottom:4px">⏱️ Security Guidelines:</div>
        <div>&bull; The reset link is valid for <strong>5 minutes only</strong>.</div>
        <div>&bull; Once used or if a new link is requested, prior links expire instantly.</div>
        <div>&bull; If not received in 1 minute, please check your <strong>Junk / Spam</strong> folder.</div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px">
      <a href="<?= APP_URL ?>/?page=login" class="btn btn-primary w-100 btn-lg" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Return to Sign In
      </a>
      <a href="<?= APP_URL ?>/?page=forgot_password" class="btn btn-secondary w-100" style="text-decoration:none;text-align:center">
        Try Another Username / Email
      </a>
    </div>

    <?php else: ?>

    <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:18px;line-height:1.5">
      Enter your <strong>Username</strong> or <strong>Registered Email</strong> below. We will send a secure 5-minute password reset link to your registered email.
    </p>

    <form class="login-form" method="POST" action="<?= APP_URL ?>/?page=forgot_password">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="identifier">Username or Email Address <span class="required">*</span></label>
        <input type="text" id="identifier" name="identifier" class="form-control" required
               value="<?= e($_POST['identifier'] ?? $_POST['email'] ?? '') ?>" placeholder="e.g. admin, irene, or name@company.com"
               autocomplete="username" autofocus>
      </div>

      <div style="background:var(--surface-2, #f8fafc);border:1px solid var(--border, #e2e8f0);border-radius:var(--radius-sm, 6px);padding:10px 12px;font-size:0.8rem;color:var(--text-muted, #64748b);margin-bottom:18px;line-height:1.4">
        🔒 <strong>Security Policy:</strong> Reset links are cryptographically signed, valid for <strong>5 minutes only</strong>, and automatically expire upon use.
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Send Password Reset Link
      </button>
    </form>

    <div style="text-align:center;margin-top:24px;font-size:0.86rem">
      <a href="<?= APP_URL ?>/?page=login" style="color:var(--primary);text-decoration:none;font-weight:600">
        &larr; Back to Sign In
      </a>
    </div>

    <?php endif; ?>
  </div>
</div>
</body>
</html>
