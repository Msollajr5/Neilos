<?php
// ============================================================
// Auth Controller — login, logout, change_password, otp_verify
// ============================================================

$action = $_GET['page'] ?? 'login';

// --- LOGOUT ---
if ($action === 'logout') {
    if (!empty($_SESSION['user_id'])) {
        auditLog('User logged out', 'auth', $_SESSION['user_id']);
    }
    logoutUser();
    header('Location: ' . APP_URL . '/?page=login');
    exit;
}

// --- SESSION PING / KEEP-ALIVE ---
if ($action === 'session_ping') {
    startSecureSession();
    header('Content-Type: application/json');

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'timeout', 'redirect' => APP_URL . '/?page=login&reason=timeout']);
        exit;
    }

    $idleTimeout  = defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT : 900;
    $lastActivity = $_SESSION['last_activity'] ?? time();
    $elapsed      = time() - $lastActivity;

    if ($elapsed > $idleTimeout) {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['status' => 'timeout', 'redirect' => APP_URL . '/?page=login&reason=timeout']);
        exit;
    }

    // Refresh activity if requested by client (e.g., user clicked "Stay Logged In" or ongoing activity)
    if (!empty($_GET['keep_alive']) || !empty($_POST['keep_alive'])) {
        $_SESSION['last_activity'] = time();
        $elapsed = 0;
    }

    $remaining = max(0, $idleTimeout - $elapsed);
    echo json_encode([
        'status'            => 'active',
        'idle_timeout'      => $idleTimeout,
        'remaining_seconds' => $remaining
    ]);
    exit;
}

// --- CHANGE PASSWORD ---
if ($action === 'change_password') {
    requireLogin();
    $userId = $_SESSION['user_id'];
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $currentUserRec = $stmt->fetch();

    $isFirstLogin = !empty($_SESSION['is_first_login']) || !empty($currentUserRec['is_first_login']);
    $isExpired    = !empty($_SESSION['password_expired']) || (($_GET['reason'] ?? '') === 'expired');
    $errors       = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';

        // If not first-login, verify current password
        if (!$isFirstLogin) {
            if (empty($currentPass)) {
                $errors[] = 'Current password is required.';
            } elseif (!password_verify($currentPass, $currentUserRec['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        // Validate enterprise password policy
        $policyErrors = validatePasswordPolicy($newPass, $currentUserRec['password'] ?? null, $confirm);
        $errors = array_merge($errors, $policyErrors);

        if (empty($errors)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET password = ?, password_changed_at = NOW(), is_first_login = 0, otp_verified = 1 WHERE id = ?');
            $stmt->execute([$hash, $userId]);
            auditLog('Password changed (90-day policy rotation)', 'users', $userId);

            if (!empty($currentUserRec['email'])) {
                try {
                    $html = renderPasswordChangedConfirmationEmail($currentUserRec['full_name'], $currentUserRec['username'] ?: $currentUserRec['email']);
                    dispatchDirectEmail($currentUserRec['email'], "Security Alert: Your Password Was Changed", $html, "Your password on Neilos Partner Portal was changed.", 'users', $userId);
                } catch (Throwable $t) {}
            }

            $_SESSION['is_first_login']      = false;
            $_SESSION['password_expired']    = false;
            $_SESSION['password_warn']       = false;
            $_SESSION['password_days_left']  = defined('PASSWORD_EXPIRY_DAYS') ? PASSWORD_EXPIRY_DAYS : 90;
            $_SESSION['password_changed_at'] = date('Y-m-d H:i:s');

            setFlash('success', 'Your password has been updated successfully. Thank you for keeping your account secure!');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
    }

    $pageTitle = $isFirstLogin ? 'Set Permanent Password' : ($isExpired ? 'Password Expired — Update Password' : 'Change Password');
    include APP_DIR . '/views/auth/change_password.php';
    exit;
}

// --- FORGOT PASSWORD ---
if ($action === 'forgot_password') {
    $forgotError = '';
    $resetSent = false;
    $sentMaskedEmail = '';
    $sentFullName = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? $_POST['username'] ?? '');

        if (!empty($identifier)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, full_name, username, email, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && !empty($user['email'])) {
                // 1. Instantly invalidate/expire all previous unused reset tokens for this user
                $db->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0")->execute([$user['email']]);

                // 2. Generate a secure cryptographic 64-char token
                $token = bin2hex(random_bytes(32));

                // 3. Insert new reset record with strict 5-minute expiration
                $db->prepare("INSERT INTO password_resets (email, token, expires_at, used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)")
                   ->execute([$user['email'], $token]);

                // 4. Mask email to show first 2 letters and last 2 letters
                $sentMaskedEmail = maskEmail($user['email']);
                $sentFullName    = $user['full_name'];
                $resetSent       = true;

                // 5. Dispatch email with 5-minute reset link
                $resetUrl = getPortalBaseUrl() . '/?page=reset_password&token=' . urlencode($token);
                $html = renderPasswordResetEmail($user['full_name'], $resetUrl, 5);
                dispatchDirectEmail($user['email'], "Reset Your Neilos Partner Portal Password (5-Minute Link)", $html, "Reset your password using this link (expires in 5 minutes): {$resetUrl}", 'auth', $user['id']);
                auditLog("Password reset link requested for {$user['username']} ({$sentMaskedEmail})", 'users', $user['id']);
            } else {
                $forgotError = "No active account was found matching '" . htmlspecialchars($identifier) . "'. Please check your username or registered email.";
            }
        } else {
            $forgotError = 'Please enter your username or registered email address.';
        }
    }

    $pageTitle = 'Forgot Password';
    include APP_DIR . '/views/auth/forgot_password.php';
    exit;
}

// --- RESET PASSWORD (TOKEN-BASED) ---
if ($action === 'reset_password') {
    $token = trim($_GET['token'] ?? '');
    $db = getDB();
    $resetRecord = null;
    $tokenValid = false;
    $invalidTokenMessage = '';

    if ($token) {
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $resetRecord = $stmt->fetch();

        if ($resetRecord) {
            $uStmt = $db->prepare("SELECT id as user_id, full_name, username FROM users WHERE email = ? LIMIT 1");
            $uStmt->execute([$resetRecord['email']]);
            $uRec = $uStmt->fetch();
            if ($uRec) {
                $resetRecord = array_merge($resetRecord, $uRec);
            }
        }
        
        if (!$resetRecord) {
            $tokenValid = false;
            $invalidTokenMessage = 'This password reset link is invalid or malformed.';
        } elseif ((int)$resetRecord['used'] === 1) {
            $tokenValid = false;
            $invalidTokenMessage = 'This password reset link has already been used and is no longer valid. Please request a new link.';
        } elseif (strtotime($resetRecord['expires_at']) <= time()) {
            $tokenValid = false;
            $invalidTokenMessage = 'This password reset link has expired (links are strictly valid for 5 minutes only). Please request a new link.';
        } else {
            $tokenValid = true;
        }
    } else {
        $tokenValid = false;
        $invalidTokenMessage = 'No reset token provided. Please use the link sent to your email.';
    }

    $resetErrors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        // Re-verify token validity at submission time
        if (!$tokenValid || !$resetRecord) {
            $resetErrors[] = $invalidTokenMessage ?: 'This reset link has expired or is invalid. Please request a new reset link.';
        } else {
            $newPass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $policyErrors = validatePasswordPolicy($newPass, null, $confirm);
            $resetErrors  = array_merge($resetErrors, $policyErrors);

            if (empty($resetErrors)) {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                
                // Update user password and mark activated
                $db->prepare("UPDATE users SET password = ?, password_changed_at = NOW(), is_first_login = 0, otp_verified = 1 WHERE email = ?")
                   ->execute([$hash, $resetRecord['email']]);

                // Instantly mark current token and all tokens for this email as used/expired
                $db->prepare("UPDATE password_resets SET used = 1 WHERE email = ?")->execute([$resetRecord['email']]);

                auditLog('Password reset completed via 5-minute token', 'users', $resetRecord['user_id'] ?? null);

                // Send confirmation email
                try {
                    $html = renderPasswordChangedConfirmationEmail($resetRecord['full_name'], $resetRecord['username'] ?: $resetRecord['email']);
                    dispatchDirectEmail($resetRecord['email'], "Security Alert: Your Password Was Changed", $html, "Your password on Neilos Partner Portal was changed successfully.", 'users', $resetRecord['user_id'] ?? null);
                } catch (Throwable $t) {}

                setFlash('success', 'Your password has been successfully reset! You can now log in with your new password.');
                header('Location: ' . APP_URL . '/?page=login');
                exit;
            }
        }
    }

    $pageTitle = 'Set New Password';
    include APP_DIR . '/views/auth/reset_password.php';
    exit;
}

// --- OTP VERIFY ---
if ($action === 'otp_verify') {
    requireLogin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $entered = trim($_POST['otp_code'] ?? '');
        $db = getDB();
        $stmt = $db->prepare('SELECT otp_code FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $stored = $stmt->fetchColumn();
        if ($entered === $stored) {
            $db->prepare('UPDATE users SET otp_verified = 1 WHERE id = ?')->execute([$_SESSION['user_id']]);
            $_SESSION['otp_verified'] = true;
            setFlash('success', 'OTP verified. Welcome to Neilos Partner Portal!');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
        $otpError = 'Invalid OTP code. Please try again.';
    } else {
        // Generate new OTP
        $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $db   = getDB();
        $db->prepare('UPDATE users SET otp_code = ? WHERE id = ?')->execute([$otp, $_SESSION['user_id']]);
        $_SESSION['current_otp'] = $otp; // For simulation display

        // Send OTP via Email
        $userEmail = $_SESSION['user_email'] ?? '';
        $userName  = $_SESSION['full_name'] ?? 'User';
        if ($userEmail) {
            $html = renderOtpEmail($userName, $otp, 10);
            dispatchDirectEmail($userEmail, "Your Neilos Portal Login OTP Code: {$otp}", $html, "Your OTP code is: {$otp}", 'auth', $_SESSION['user_id']);
        }
    }
    $pageTitle = 'OTP Verification';
    include APP_DIR . '/views/auth/otp_verify.php';
    exit;
}

// --- LOGIN ---
$loginError = '';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        try {
            $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            // Self-heal missing is_active column if needed
            try { $db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"); } catch (Throwable $t) {}
            try {
                $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND (is_active = 1 OR status = "Active")');
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
            } catch (Throwable $t) {
                $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?)');
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            try {
                $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            } catch (Throwable $e) {}
            auditLog('Login', 'users', $user['id']);

            if ($user['is_first_login']) {
                header('Location: ' . APP_URL . '/?page=change_password');
            } elseif (!empty($_SESSION['password_expired'])) {
                header('Location: ' . APP_URL . '/?page=change_password&reason=expired');
            } else {
                header('Location: ' . APP_URL . '/?page=dashboard');
            }
            exit;
        } else {
            $loginError = 'Invalid username or password.';
        }
    } else {
        $loginError = 'Please enter your username and password.';
    }
}

$pageTitle = 'Sign In';
include APP_DIR . '/views/auth/login.php';
