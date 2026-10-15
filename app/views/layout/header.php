<?php
// Layout header — included at the top of every authenticated page
$flash = getFlash();
$user  = currentUser();
$currentPage = $_GET['page'] ?? 'dashboard';
$currentType = trim($_GET['type'] ?? $_GET['category'] ?? '');
$activeSubKey = '';
if ($currentPage === 'partners') {
    if ($currentType === 'enterprise') {
        $activeSubKey = 'partners_enterprise';
    } elseif ($currentType === 'partner') {
        $activeSubKey = 'partners_all';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Neilos Partner Portal — Order Management & Service Delivery">
  <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css?v=<?= time() ?>">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
  <script>
    var APP_URL = '<?= APP_URL ?>';
    window.SERVER_PRICING_PACKAGES = <?= json_encode(getActivePricingPackages()) ?>;
  </script>
</head>
<body>
<div class="portal-wrapper">

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <script>
  (function() {
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    try {
      if (localStorage.getItem('neilos_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
        sb.classList.add('collapsed');
      }
      var pos = sessionStorage.getItem('neilos_sidebar_scroll');
      if (pos !== null) {
        sb.scrollTop = parseInt(pos, 10);
      }
    } catch(e) {}
  })();
  </script>
  <div class="sidebar-logo">
    <a href="<?= APP_URL ?>/?page=dashboard" class="sidebar-logo-link" title="Neilos Network">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=4" alt="Neilos Network" class="sidebar-logo-img">
    </a>
  </div>

  <nav class="sidebar-nav">
    <?php
    $sidebarSections = [
        [
            'label' => 'Main',
            'items' => [
                [
                    'key'        => 'dashboard',
                    'title'      => 'Dashboard',
                    'url'        => APP_URL . '/?page=dashboard',
                    'icon'       => 'dashboard',
                    'permission' => 'dashboard.view',
                ],
            ],
        ],
        [
            'label' => 'Partners & Commercial',
            'items' => [
                [
                    'key'        => 'partners',
                    'title'      => 'Partners & Clients',
                    'url'        => APP_URL . '/?page=partners',
                    'icon'       => 'building',
                    'permission' => 'partners.view',
                    'badge'      => function() use ($user) {
                        try {
                            $db = getDB();
                            $where = "(kyc_type IS NULL OR kyc_type != 'Contractor') AND (partner_type IS NULL OR partner_type != 'Contractor')";
                            $params = [];
                            if (isKamUser()) {
                                $where .= " AND (kam_id = ? OR assigned_kam_name = ?)";
                                $params = [(int)$user['id'], $user['full_name']];
                            }
                            $cntStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE {$where}");
                            $cntStmt->execute($params);
                            $cnt = (int)$cntStmt->fetchColumn();
                            return $cnt > 0 ? "<span class='nav-badge' style='background:rgba(15,76,129,0.15);color:var(--primary);border:1px solid var(--primary)'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    },
                    'subitems'   => [
                        [
                            'key'        => 'partners_all',
                            'title'      => 'All Partners',
                            'url'        => APP_URL . '/?page=partners&type=partner',
                            'badge'      => function() use ($user) {
                                try {
                                    $db = getDB();
                                    $where = "(kyc_type IS NULL OR kyc_type != 'Contractor') AND (partner_type IS NULL OR partner_type != 'Contractor') AND (partner_category = 'Partner' OR (partner_category IS NULL AND (partner_type IS NULL OR partner_type != 'Enterprise')))";
                                    $params = [];
                                    if (isKamUser()) {
                                        $where .= " AND (kam_id = ? OR assigned_kam_name = ?)";
                                        $params = [(int)$user['id'], $user['full_name']];
                                    }
                                    $cntStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE {$where}");
                                    $cntStmt->execute($params);
                                    $cnt = (int)$cntStmt->fetchColumn();
                                    return $cnt > 0 ? "<span class='nav-sub-badge'>$cnt</span>" : '';
                                } catch(Exception $e) { return ''; }
                            },
                        ],
                        [
                            'key'        => 'partners_enterprise',
                            'title'      => 'Enterprise Clients',
                            'url'        => APP_URL . '/?page=partners&type=enterprise',
                            'badge'      => function() use ($user) {
                                try {
                                    $db = getDB();
                                    $where = "(kyc_type IS NULL OR kyc_type != 'Contractor') AND (partner_type IS NULL OR partner_type != 'Contractor') AND (partner_category = 'Enterprise Client' OR partner_type = 'Enterprise')";
                                    $params = [];
                                    if (isKamUser()) {
                                        $where .= " AND (kam_id = ? OR assigned_kam_name = ?)";
                                        $params = [(int)$user['id'], $user['full_name']];
                                    }
                                    $cntStmt = $db->prepare("SELECT COUNT(*) FROM partners WHERE {$where}");
                                    $cntStmt->execute($params);
                                    $cnt = (int)$cntStmt->fetchColumn();
                                    return $cnt > 0 ? "<span class='nav-sub-badge'>$cnt</span>" : '';
                                } catch(Exception $e) { return ''; }
                            },
                        ],
                    ]
                ],
                [
                    'key'        => 'kyc',
                    'title'      => 'KYC Applications',
                    'url'        => APP_URL . '/?page=kyc',
                    'icon'       => 'document',
                    'permission' => 'kyc.view',
                ],
            ],
        ],
        [
            'label' => 'Order Lifecycle',
            'items' => [
                [
                    'key'        => 'new_order',
                    'title'      => 'New Service Order',
                    'url'        => APP_URL . '/?page=new_order',
                    'icon'       => 'plus-circle',
                    'permission' => 'orders.create',
                ],
                [
                    'key'        => 'ftth_bulk',
                    'title'      => 'Bulk Order Upload',
                    'url'        => APP_URL . '/?page=ftth_bulk',
                    'icon'       => 'upload',
                    'permission' => 'orders.bulk',
                ],
                [
                    'key'        => 'orders',
                    'title'      => 'Order Tracking',
                    'url'        => APP_URL . '/?page=orders',
                    'icon'       => 'list',
                    'permission' => 'orders.view',
                    'badge'      => function() use ($user) {
                        try {
                            $db = getDB();
                            $pw = partnerWhere('o');
                            $cntStmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$pw['condition']} AND o.status != 'Closed' AND o.status NOT IN ('Not Feasible', 'Cancelled')");
                            $cntStmt->execute($pw['params']);
                            $cnt = (int)$cntStmt->fetchColumn();
                            return $cnt > 0 ? "<span class='nav-badge' style='background:var(--warning);color:#fff'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    }
                ],
                [
                    'key'        => 'closed_orders',
                    'title'      => 'Closed Orders',
                    'url'        => APP_URL . '/?page=closed_orders',
                    'icon'       => 'check-circle',
                    'permission' => 'orders.view',
                    'badge'      => function() use ($user) {
                        try {
                            $db = getDB();
                            $pw = partnerWhere('o');
                            $cntStmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$pw['condition']} AND (o.status = 'Closed' OR o.status IN ('Not Feasible', 'Cancelled'))");
                            $cntStmt->execute($pw['params']);
                            $cnt = (int)$cntStmt->fetchColumn();
                            return $cnt > 0 ? "<span class='nav-badge' style='background:rgba(5,150,105,0.15);color:var(--success);border:1px solid var(--success)'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    }
                ],
            ],
        ],
        [
            'label' => 'Compliance & SLAs',
            'items' => [
                [
                    'key'        => 'sla_tracking',
                    'title'      => 'SLA Tracking',
                    'url'        => APP_URL . '/?page=sla_tracking',
                    'icon'       => 'clock',
                    'permission' => 'sla.view',
                ],
                [
                    'key'        => 'reports',
                    'title'      => 'Reports',
                    'url'        => APP_URL . '/?page=reports',
                    'icon'       => 'chart',
                    'permission' => 'reports.view',
                ],
            ],
        ],
        [
            'label' => 'Field & Vendor Delivery',
            'items' => [
                [
                    'key'        => isContractorUser() ? 'contractor' : 'contractors',
                    'title'      => isContractorUser() ? 'My Jobs' : 'Contractors',
                    'url'        => isContractorUser() ? (APP_URL . '/?page=contractor') : (APP_URL . '/?page=contractors'),
                    'icon'       => isContractorUser() ? 'briefcase' : 'users',
                    'permission' => 'contractors.view',
                    'badge'      => function() use ($user) {
                        if (!isContractorUser()) return '';
                        try {
                            $db = getDB();
                            $cntStmt = $db->prepare("SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = ? AND ca.status = 'Assigned'");
                            $cntStmt->execute([$user['partner_id'] ?? 0]);
                            $cnt = (int)$cntStmt->fetchColumn();
                            return $cnt > 0 ? "<span class='nav-badge'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    }
                ],
                [
                    'key'        => 'surveys',
                    'title'      => isContractorUser() ? 'Allocated Surveys' : 'Surveys & Field Teams',
                    'url'        => APP_URL . '/?page=surveys',
                    'icon'       => 'map',
                    'permission' => 'surveys.view',
                    'badge'      => function() use ($user) {
                        try {
                            $db = getDB();
                            ensureSurveysSchema($db);
                            if (isContractorUser()) {
                                $pId = (int)($user['partner_id'] ?? 0);
                                $cnt = (int)$db->query("SELECT COUNT(*) FROM survey_tasks WHERE contractor_partner_id = $pId AND status IN ('Assigned', 'In Progress')")->fetchColumn();
                            } else {
                                $cnt = (int)$db->query("SELECT COUNT(*) FROM survey_tasks WHERE status IN ('Assigned', 'In Progress', 'Completed')")->fetchColumn();
                            }
                            return $cnt > 0 ? "<span class='nav-badge' style='background:var(--primary);color:#fff'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    }
                ],
                [
                    'key'        => 'contractor_teams',
                    'title'      => 'Teams & Technicians',
                    'url'        => APP_URL . '/?page=contractor&action=teams',
                    'icon'       => 'users',
                    'permission' => 'contractors.view',
                    'badge'      => function() use ($user) {
                        try {
                            $db = getDB();
                            if (isContractorUser()) {
                                $pId = (int)($user['partner_id'] ?? 0);
                                $cnt = (int)$db->query("SELECT COUNT(*) FROM contractor_technicians WHERE contractor_partner_id = $pId AND status = 'Pending'")->fetchColumn();
                            } else {
                                $cnt = (int)$db->query("SELECT COUNT(*) FROM contractor_technicians WHERE status = 'Pending'")->fetchColumn();
                            }
                            return $cnt > 0 ? "<span class='nav-badge' style='background:var(--warning);color:#fff'>$cnt</span>" : '';
                        } catch(Exception $e) { return ''; }
                    }
                ],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                [
                    'key'        => 'users',
                    'title'      => 'User Management',
                    'url'        => APP_URL . '/?page=users',
                    'icon'       => 'users',
                    'permission' => 'users.view',
                ],
                [
                    'key'        => 'settings',
                    'title'      => 'Company Settings',
                    'url'        => APP_URL . '/?page=settings',
                    'icon'       => 'settings',
                    'permission' => 'admin.settings',
                ],
                [
                    'key'        => 'activity_logs',
                    'title'      => 'Activity Logs',
                    'url'        => APP_URL . '/?page=activity_logs',
                    'icon'       => 'activity',
                    'permission' => 'activity_logs.view',
                ],
            ],
        ],
    ];

    foreach ($sidebarSections as $sec):
        $visibleItems = array_filter($sec['items'], function($it) {
            return hasPermission($it['permission']);
        });
        if (empty($visibleItems)) continue;
    ?>
    <div class="nav-section-label"><?= e($sec['label']) ?></div>
    <?php foreach ($visibleItems as $item): 
        $hasSub = !empty($item['subitems']);
        $isSubActive = false;
        if ($hasSub) {
            foreach ($item['subitems'] as $sub) {
                if ($activeSubKey === $sub['key']) {
                    $isSubActive = true;
                    break;
                }
            }
        }
        $isParentActive = ($currentPage === $item['key']);
    ?>
    <div class="nav-item <?= $hasSub ? 'has-submenu' : '' ?> <?= ($isParentActive || $isSubActive) ? 'open' : '' ?>">
      <a href="<?= $item['url'] ?>" class="nav-link <?= ($isParentActive && !$isSubActive) ? 'active' : '' ?>">
        <?= svgIcon($item['icon']) ?>
        <span><?= e($item['title']) ?></span>
        <?php if (!empty($item['badge']) && is_callable($item['badge'])) echo $item['badge'](); ?>
        <?php if ($hasSub): ?>
        <span class="submenu-arrow"><?= svgIcon('chevron-down', 12) ?></span>
        <?php endif; ?>
      </a>
      <?php if ($hasSub): ?>
      <div class="nav-submenu">
        <?php foreach ($item['subitems'] as $sub): 
            $isThisSubActive = ($activeSubKey === $sub['key']);
        ?>
        <a href="<?= $sub['url'] ?>" class="nav-sublink <?= $isThisSubActive ? 'active' : '' ?>">
          <span class="nav-sublink-bullet"></span>
          <span><?= e($sub['title']) ?></span>
          <?php if (!empty($sub['badge']) && is_callable($sub['badge'])) echo $sub['badge'](); ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </nav>


  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/?page=profile" class="sidebar-user" style="text-decoration:none;color:inherit">
      <div class="sidebar-avatar">
        <?php $spPic = profilePictureUrl($user['profile_picture'] ?? null); ?>
        <?php if ($spPic): ?>
        <img src="<?= e($spPic) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
        <div class="sidebar-user-role"><?= e($user['role']) ?></div>
      </div>
    </a>
  </div>
  <script>
  (function() {
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    try {
      var pos = sessionStorage.getItem('neilos_sidebar_scroll');
      if (pos !== null) {
        sb.scrollTop = parseInt(pos, 10);
      }
    } catch(e) {}
  })();
  </script>
</aside>

<!-- Main -->
<div class="main-content">
  <!-- Topbar -->
  <header class="topbar">
    <button class="hamburger" id="sidebarToggle" aria-label="Toggle navigation menu">
      <span></span><span></span><span></span>
    </button>
    <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    <div class="topbar-actions">
      <span style="font-size:.8rem;color:var(--text-secondary)"><?= e($user['full_name']) ?></span>
      <a href="<?= APP_URL ?>/?page=profile" class="topbar-btn" title="Profile" style="position:relative">
        <?php $tpPic = profilePictureUrl($user['profile_picture'] ?? ''); ?>
        <?php if ($tpPic): ?>
        <img src="<?= e($tpPic) ?>" alt="Profile" style="width:28px;height:28px;border-radius:50%;object-fit:cover">
        <?php else: ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/?page=logout" class="topbar-btn" title="Sign Out">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </header>

  <!-- Page Content -->
  <div class="page-content">
    <?php if (!empty($_SESSION['password_warn']) && isset($_SESSION['password_days_left'])): ?>
    <div class="alert alert-warning" style="background:linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%);border-left:4px solid #f59e0b;color:#92400e;display:flex;align-items:center;justify-content:space-between;padding:12px 18px;margin-bottom:18px;border-radius:var(--radius-sm);box-shadow:0 1px 3px rgba(0,0,0,0.06)">
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:1.25rem">⚠️</span>
        <div style="font-size:0.88rem;line-height:1.4">
          <strong>Password Expiration Notice:</strong> Your password is scheduled to expire in <strong><?= (int)$_SESSION['password_days_left'] ?> <?= (int)$_SESSION['password_days_left'] === 1 ? 'day' : 'days' ?></strong> per company 90-day security policy.
        </div>
      </div>
      <a href="<?= APP_URL ?>/?page=profile" class="btn btn-sm" style="background:#f59e0b;color:#fff;font-weight:600;text-decoration:none;border-radius:6px;padding:6px 14px;white-space:nowrap;box-shadow:0 1px 2px rgba(0,0,0,0.1)">Update Password</a>
    </div>
    <?php endif; ?>

    <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" data-countdown="20" style="position:relative;overflow:hidden;display:flex;align-items:center;gap:12px;padding-bottom:14px">
      <?= svgIcon($flash['type'] === 'success' ? 'check' : ($flash['type'] === 'danger' ? 'x' : 'info')) ?>
      <span style="flex:1"><?= renderNotificationMessage($flash['message']) ?></span>
      <span class="notification-countdown-badge" style="font-size:0.78rem;font-weight:700;padding:3px 10px;border-radius:12px;background:rgba(0,0,0,0.08);color:inherit;white-space:nowrap;display:inline-flex;align-items:center;gap:4px">
        ⏳ <span class="countdown-timer-text">20s</span>
      </span>
      <div class="notification-progress-bar" style="position:absolute;bottom:0;left:0;height:4px;width:100%;background:currentColor;opacity:0.4;transition:width 1s linear"></div>
    </div>
    <?php endif; ?>
