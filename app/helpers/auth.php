<?php
// ============================================================
// Neilos Partner Portal — Auth Helper
// ============================================================

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');

            $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800;
            $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $currentSavePath = session_save_path();
            if (!$currentSavePath || !is_dir($currentSavePath) || !is_writable($currentSavePath)) {
                $fallbackDir = sys_get_temp_dir();
                if (is_dir($fallbackDir) && is_writable($fallbackDir)) {
                    session_save_path($fallbackDir);
                }
            }
        }

        @session_start();

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $fallbackDir = sys_get_temp_dir();
            session_save_path($fallbackDir);
            @session_start();
        }
    }
}

/**
 * Validate password complexity according to enterprise policy:
 * - Minimum 8 characters
 * - At least 1 uppercase letter
 * - At least 1 lowercase letter
 * - At least 1 digit
 * - At least 1 special symbol
 * - Confirmation match
 * - Cannot match current password
 */
function validatePasswordPolicy(string $newPassword, ?string $currentHash = null, ?string $confirmPassword = null): array {
    $errors = [];
    if (strlen($newPassword) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $newPassword)) {
        $errors[] = 'Password must contain at least one uppercase letter (A-Z).';
    }
    if (!preg_match('/[a-z]/', $newPassword)) {
        $errors[] = 'Password must contain at least one lowercase letter (a-z).';
    }
    if (!preg_match('/[0-9]/', $newPassword)) {
        $errors[] = 'Password must contain at least one number (0-9).';
    }
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?~`]/', $newPassword)) {
        $errors[] = 'Password must contain at least one special symbol (!@#$%^&* etc.).';
    }
    if ($confirmPassword !== null && $newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if ($currentHash !== null && !empty($newPassword) && password_verify($newPassword, $currentHash)) {
        $errors[] = 'New password cannot be the same as your current password.';
    }
    return $errors;
}

/**
 * Check password expiration against corporate 90-day rotation policy.
 */
function checkPasswordExpiry(array $user): array {
    $changedAt = $user['password_changed_at'] ?? null;
    if (empty($changedAt)) {
        $changedAt = $user['created_at'] ?? date('Y-m-d H:i:s');
    }
    $changedTime = strtotime($changedAt);
    if ($changedTime === false || $changedTime <= 0) {
        $changedTime = time();
    }

    $maxDays  = defined('PASSWORD_EXPIRY_DAYS') ? PASSWORD_EXPIRY_DAYS : 90;
    $warnDays = defined('PASSWORD_EXPIRY_WARN_DAYS') ? PASSWORD_EXPIRY_WARN_DAYS : 14;

    $ageSeconds = max(0, time() - $changedTime);
    $daysOld    = (int)floor($ageSeconds / 86400);
    $daysLeft   = max(0, $maxDays - $daysOld);
    $isExpired  = ($daysOld >= $maxDays);
    $isWarn     = (!$isExpired && $daysLeft <= $warnDays);

    return [
        'expired'             => $isExpired,
        'warn'                => $isWarn,
        'days_old'            => $daysOld,
        'days_left'           => $daysLeft,
        'password_changed_at' => $changedAt,
    ];
}

function requireLogin(): void {
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['status' => 'unauthenticated', 'redirect' => APP_URL . '/?page=login']);
            exit;
        }
        header('Location: ' . APP_URL . '/?page=login');
        exit;
    }

    $page = $_GET['page'] ?? 'dashboard';

    // 1. Enforce Idle Session Timeout
    $idleTimeout = defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT : 900;
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idleTimeout)) {
        session_unset();
        session_destroy();

        if ($page === 'sse') {
            http_response_code(401);
            echo "event: session_timeout\ndata: {}\n\n";
            exit;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['status' => 'timeout', 'redirect' => APP_URL . '/?page=login&reason=timeout']);
            exit;
        }

        header('Location: ' . APP_URL . '/?page=login&reason=timeout');
        exit;
    }

    if ($page !== 'sse') {
        $_SESSION['last_activity'] = time();
    }

    // 2. Ensure password expiration & first-login status is populated in session
    if (!isset($_SESSION['password_expired']) || !isset($_SESSION['is_first_login'])) {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT password_changed_at, created_at, is_first_login FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $u = $stmt->fetch();
            if ($u) {
                $_SESSION['is_first_login'] = (bool)$u['is_first_login'];
                $expiry = checkPasswordExpiry($u);
                $_SESSION['password_expired']    = $expiry['expired'];
                $_SESSION['password_warn']       = $expiry['warn'];
                $_SESSION['password_days_left']  = $expiry['days_left'];
                $_SESSION['password_changed_at'] = $expiry['password_changed_at'];
            }
        } catch (Throwable $e) {}
    }

    // 3. Enforce first-login flow
    if (!empty($_SESSION['is_first_login']) && !in_array($page, ['change_password', 'logout'])) {
        header('Location: ' . APP_URL . '/?page=change_password');
        exit;
    }

    // 4. Enforce 90-day password rotation policy
    if (!empty($_SESSION['password_expired']) && !in_array($page, ['change_password', 'logout'])) {
        header('Location: ' . APP_URL . '/?page=change_password&reason=expired');
        exit;
    }
}

function requireRole(array $allowedRoles): void {
    requireLogin();
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $allowedRoles) && $role !== 'System Admin') {
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

function ensurePermissionsSchema(PDO $db): void {
    static $done = false;
    if ($done) return;
    try {
        $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('permissions', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT NULL");
        }
        $done = true;
    } catch (Exception $e) {}
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): array {
    return [
        'id'              => $_SESSION['user_id']         ?? 0,
        'full_name'       => $_SESSION['full_name']       ?? '',
        'username'        => $_SESSION['username']        ?? '',
        'email'           => $_SESSION['email']           ?? '',
        'role'            => $_SESSION['user_role']       ?? '',
        'partner_id'      => $_SESSION['partner_id']      ?? null,
        'permissions'     => $_SESSION['permissions']     ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null,
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'System Admin';
}

function isPartnerUser(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'Partner' || $role === 'Partner User';
}

function isContractorUser(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'Contractor' || $role === 'Contractor User';
}

function isManagement(): bool {
    return ($_SESSION['user_role'] ?? '') === 'Management';
}

function isProjectManager(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'Project Manager' || $role === 'Project Coordinator';
}

function isKAM(): bool {
    return ($_SESSION['user_role'] ?? '') === 'KAM';
}

function isBSA(): bool {
    return ($_SESSION['user_role'] ?? '') === 'BSA';
}

function isFinance(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['Billing', 'Finance'], true);
}

function isExternalUser(): bool {
    return isPartnerUser() || isContractorUser();
}

function isKamUser(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'KAM';
}

function hasRole(string ...$roles): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'System Admin' || in_array($role, $roles);
}

function canManageUsdExchangeRate(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['System Admin', 'Admin', 'Management'], true);
}

function canViewDashboardFinancials(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return !in_array($role, ['Partner', 'Partner User', 'Project Manager', 'BSA', 'Contractor', 'Contractor User', 'KAM'], true);
}

function canViewDashboardPartnersCard(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return !in_array($role, ['Partner', 'Partner User', 'Contractor', 'Contractor User'], true);
}

function canViewCoverageCheck(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['Partner', 'Partner User', 'BSA', 'Management', 'KAM', 'Admin', 'System Admin'], true);
}

function partnerWhere(string $alias = ''): array {
    $partnerId = (int)($_SESSION['partner_id'] ?? 0);

    if (isPartnerUser()) {
        if ($alias === 'p') {
            return [
                'condition' => "p.id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'pka') {
            return [
                'condition' => "pka.partner_id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'k') {
            return [
                'condition' => "k.partner_id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'o') {
            return [
                'condition' => "o.partner_id = ?",
                'params'    => [$partnerId],
            ];
        }
        $col = $alias ? "$alias.partner_id" : 'partner_id';
        return [
            'condition' => "$col = ?",
            'params'    => [$partnerId],
        ];
    }

    if (isContractorUser()) {
        if ($alias === 'p') {
            return [
                'condition' => "p.id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'pka') {
            return [
                'condition' => "pka.partner_id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'k') {
            return [
                'condition' => "k.partner_id = ?",
                'params'    => [$partnerId],
            ];
        }
        if ($alias === 'o') {
            return [
                'condition' => "(o.partner_id = ? OR o.id IN (SELECT ca_sub.order_id FROM contractor_assignments ca_sub WHERE ca_sub.contractor_partner_id = ?))",
                'params'    => [$partnerId, $partnerId],
            ];
        }
        $col = $alias ? "$alias.partner_id" : 'partner_id';
        return [
            'condition' => "$col = ?",
            'params'    => [$partnerId],
        ];
    }

    if (isKamUser()) {
        $kamId   = (int)($_SESSION['user_id'] ?? 0);
        $kamName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? '');

        if ($alias === 'p') {
            return [
                'condition' => "(p.kam_id = ? OR p.assigned_kam_name = ?)",
                'params'    => [$kamId, $kamName],
            ];
        }
        if ($alias === 'pka') {
            return [
                'condition' => "(pka.partner_id IN (SELECT p_sub.id FROM partners p_sub WHERE p_sub.kam_id = ? OR p_sub.assigned_kam_name = ?))",
                'params'    => [$kamId, $kamName],
            ];
        }
        if ($alias === 'k') {
            return [
                'condition' => "(k.partner_id IN (SELECT p_sub.id FROM partners p_sub WHERE p_sub.kam_id = ? OR p_sub.assigned_kam_name = ?))",
                'params'    => [$kamId, $kamName],
            ];
        }
        $kamCol = $alias ? "$alias.kam_id" : 'kam_id';
        $partnerCol = $alias ? "$alias.partner_id" : 'partner_id';
        return [
            'condition' => "($kamCol = ? OR $partnerCol IN (SELECT p_sub.id FROM partners p_sub WHERE p_sub.kam_id = ? OR p_sub.assigned_kam_name = ?))",
            'params'    => [$kamId, $kamId, $kamName],
        ];
    }
    return ['condition' => '1=1', 'params' => []];
}

function getAllPermissionCatalog(): array {
    return [
        'Dashboard' => [
            'dashboard.view'     => 'View Dashboard',
        ],
        'Order Lifecycle' => [
            'orders.create'      => 'Create New Service Order',
            'orders.bulk'        => 'FTTH Bulk Upload',
            'orders.view'        => 'View Order Tracking & Details',
            'orders.edit'        => 'Edit Service Orders',
            'orders.approve'     => 'Approve / Commercial Management',
            'orders.delete'      => 'Delete Service Orders',
        ],
        'Field & Vendor Delivery' => [
            'contractors.view'   => 'View Contractors / Jobs',
            'contractors.assign' => 'Assign Contractor Jobs',
        ],
        'Compliance & SLAs' => [
            'kyc.view'           => 'View KYC Applications',
            'kyc.create'         => 'Create / Edit Draft KYC',
            'kyc.approve'        => 'Approve KYC Applications',
            'kyc.reject'         => 'Reject KYC Applications',
            'sla.view'           => 'View SLA Tracking',
        ],
        'Administration' => [
            'partners.view'      => 'View Partner Management',
            'partners.manage'    => 'Manage Partners (Create/Edit/Delete)',
            'contractors.manage' => 'Manage Contractors Profile',
            'users.view'         => 'View User Management',
            'users.manage'       => 'Manage Users & Configure Permissions',
            'admin.settings'     => 'Company Settings',
            'activity_logs.view' => 'View Activity Logs',
        ],
    ];
}

function getRoleDefaultPermissions(string $role): array {
    return match ($role) {
        'System Admin', 'Admin' => [
            'dashboard.view', 'orders.create', 'orders.bulk', 'orders.view', 'orders.edit', 'orders.approve', 'orders.delete',
            'contractors.view', 'contractors.assign',
            'kyc.view', 'kyc.create', 'kyc.approve', 'kyc.reject', 'sla.view',
            'partners.view', 'partners.manage', 'contractors.manage', 'users.view', 'users.manage', 'admin.settings', 'activity_logs.view'
        ],
        'Management' => [
            'dashboard.view', 'orders.create', 'orders.bulk', 'orders.view', 'orders.edit', 'orders.approve',
            'contractors.view', 'contractors.assign',
            'kyc.view', 'kyc.create', 'kyc.approve', 'kyc.reject', 'sla.view',
            'partners.view', 'partners.manage', 'contractors.manage', 'users.view', 'users.manage', 'admin.settings', 'activity_logs.view'
        ],
        'KAM' => [
            'dashboard.view', 'orders.view', 'orders.edit', 'orders.approve',
            'partners.view', 'partners.manage',
            'kyc.view', 'kyc.create', 'sla.view'
        ],
        'BSA' => [
            'dashboard.view', 'orders.view', 'orders.edit',
            'sla.view'
        ],
        'Project Manager', 'Project Coordinator' => [
            'dashboard.view', 'orders.create', 'orders.bulk', 'orders.view', 'orders.edit', 'orders.approve',
            'contractors.view', 'contractors.assign', 'contractors.manage',
            'surveys.view', 'surveys.assign', 'surveys.manage',
            'kyc.view', 'kyc.create', 'kyc.approve', 'sla.view',
            'partners.view', 'partners.manage', 'users.view', 'admin.settings', 'activity_logs.view', 'reports.view'
        ],
        'Management' => [
            'dashboard.view', 'orders.create', 'orders.bulk', 'orders.view', 'orders.edit', 'orders.approve',
            'contractors.view', 'contractors.manage', 'surveys.view', 'surveys.manage',
            'kyc.view', 'kyc.create', 'kyc.approve', 'sla.view',
            'partners.view', 'partners.manage', 'users.view', 'admin.settings', 'activity_logs.view', 'reports.view'
        ],
        'Partner', 'Partner User' => [
            'dashboard.view', 'orders.create', 'orders.bulk', 'orders.view',
            'kyc.view', 'kyc.create', 'sla.view'
        ],
        'Contractor', 'Contractor User' => [
            'dashboard.view', 'contractors.view', 'surveys.view',
            'kyc.view', 'kyc.create'
        ],
        'Finance', 'Billing' => [
            'dashboard.view', 'orders.view', 'orders.edit',
            'sla.view', 'reports.view'
        ],
        default => [
            'dashboard.view', 'orders.view'
        ]
    };
}

function getUserPermissions(?array $user = null): array {
    if ($user === null) {
        $user = currentUser();
    }
    if (empty($user['id'])) {
        return [];
    }
    $raw = $user['permissions'] ?? null;
    if ($raw === null || $raw === '') {
        return getRoleDefaultPermissions($user['role'] ?? ($user['user_role'] ?? ''));
    }
    if ($raw === '*' || $raw === 'all') {
        return ['*'];
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    return getRoleDefaultPermissions($user['role'] ?? ($user['user_role'] ?? ''));
}

function hasPermission(string $permissionKey, ?array $user = null): bool {
    if ($user === null) {
        $user = currentUser();
    }
    if (empty($user['id'])) {
        return false;
    }

    $userRole = $user['role'] ?? ($user['user_role'] ?? '');
    if (($userRole === 'System Admin' || $userRole === 'Admin') && empty($user['permissions'])) {
        return true;
    }

    if ($userRole === 'KAM' && in_array($permissionKey, ['partners.view', 'partners.manage', 'kyc.view', 'kyc.create'], true)) {
        return true;
    }

    if (($userRole === 'Partner' || $userRole === 'Partner User') && in_array($permissionKey, ['dashboard.view', 'orders.view', 'orders.create', 'orders.bulk', 'kyc.view', 'kyc.create', 'sla.view'], true)) {
        return true;
    }

    if (($userRole === 'Project Manager' || $userRole === 'Project Coordinator') && in_array($permissionKey, ['dashboard.view', 'orders.view', 'orders.create', 'orders.edit', 'orders.bulk', 'orders.approve', 'contractors.view', 'contractors.assign', 'contractors.manage', 'surveys.view', 'surveys.assign', 'surveys.manage', 'kyc.view', 'kyc.create', 'kyc.approve', 'sla.view', 'partners.view', 'partners.manage', 'users.view', 'activity_logs.view', 'reports.view'], true)) {
        return true;
    }

    if (($userRole === 'Contractor' || $userRole === 'Contractor User') && in_array($permissionKey, ['dashboard.view', 'contractors.view', 'surveys.view', 'kyc.view', 'kyc.create'], true)) {
        return true;
    }

    $perms = getUserPermissions($user);
    if (in_array('*', $perms, true)) {
        return true;
    }

    return in_array($permissionKey, $perms, true);
}

function requirePermission(string $permissionKey): void {
    requireLogin();
    if (!hasPermission($permissionKey)) {
        http_response_code(403);
        $pageTitle = 'Access Denied';
        include __DIR__ . '/../views/layout/header.php';
        ?>
        <div class="container" style="max-width:620px;margin:60px auto">
          <div class="card" style="padding:40px;text-align:center;border-top:4px solid var(--danger)">
            <div style="font-size:3rem;margin-bottom:16px">🚫</div>
            <h2 style="color:var(--danger);font-weight:700;margin-bottom:12px;font-size:1.4rem">Access Denied</h2>
            <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.6;margin-bottom:24px">
              You do not have permission to access this module or action.<br>
              <span style="font-size:0.85rem;color:var(--text-muted)">(Required Permission: <code><?= e($permissionKey) ?></code>)</span>
            </p>
            <div style="display:flex;gap:12px;justify-content:center">
              <a href="<?= APP_URL ?>/?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
              <button onclick="history.back()" class="btn btn-secondary">Go Back</button>
            </div>
          </div>
        </div>
        <?php
        include __DIR__ . '/../views/layout/footer.php';
        exit;
    }
}

function loginUser(array $user): void {
    startSecureSession();
    if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
    }
    $_SESSION['user_id']         = $user['id'];
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['username']        = $user['username'];
    $_SESSION['email']           = $user['email'];
    $_SESSION['user_role']       = $user['role'];
    $_SESSION['partner_id']      = $user['partner_id'];
    $_SESSION['permissions']     = $user['permissions'] ?? null;
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
    $_SESSION['is_first_login']  = (bool)$user['is_first_login'];
    $_SESSION['otp_verified']    = (bool)$user['otp_verified'];
    $_SESSION['login_time']      = time();
    $_SESSION['last_activity']   = time();

    $expiry = checkPasswordExpiry($user);
    $_SESSION['password_expired']    = $expiry['expired'];
    $_SESSION['password_warn']       = $expiry['warn'];
    $_SESSION['password_days_left']  = $expiry['days_left'];
    $_SESSION['password_changed_at'] = $expiry['password_changed_at'];
}

function logoutUser(): void {
    startSecureSession();
    session_unset();
    session_destroy();
}

function auditLog(string $action, string $module = '', int $recordId = 0, string $old = '', string $new = ''): void {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value, ip_address) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $module,
            $recordId ?: null,
            $old ?: null,
            $new ?: null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {}
}
