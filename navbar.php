<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

$session_role = $_SESSION['role'] ?? '';

if ($session_role === 'Maintenance Staff') {
    $role = 'Maintenance Staff';
} elseif ($session_role === 'Super Admin') {
    $role = 'Super Admin';
} else {
    $role = 'Student';
}

$user_name     = $_SESSION['user_name']     ?? 'Anne Arbolente';
$user_subtitle = $_SESSION['user_subtitle'] ?? $role;

$name_parts = explode(' ', $user_name);
$initials   = strtoupper(
    substr($name_parts[0], 0, 1) .
    (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : '')
);

$home_link = ($role === 'Maintenance Staff') ? 'staff_dashboard.php'
           : (($role === 'Super Admin')      ? 'admin.php'
           :                                   'org_dashboard.php');

if ($role === 'Maintenance Staff') {
    $nav_items = [
        ['href' => 'staff_dashboard.php', 'icon' => 'fa-gauge-high',     'label' => 'Dashboard', 'active' => ['staff_dashboard.php']],
        ['href' => 'staff_requests.php',  'icon' => 'fa-clipboard-list', 'label' => 'Requests',  'active' => ['staff_requests.php', 'request_management.php']],
        ['href' => 'staff_inventory.php', 'icon' => 'fa-boxes-stacked',  'label' => 'Inventory', 'active' => ['staff_inventory.php', 'maintenance_inventory.php']],
        ['href' => 'staff_users.php',     'icon' => 'fa-users',          'label' => 'Users',     'active' => ['staff_users.php', 'account_management.php']],
        ['href' => 'staff_reports.php',   'icon' => 'fa-chart-line',     'label' => 'Reports',   'active' => ['staff_reports.php']],
    ];
} elseif ($role === 'Super Admin') {
    $nav_items = [
        ['href' => 'admin.php',                 'icon' => 'fa-gauge-high',  'label' => 'Dashboard',             'active' => ['admin.php']],
        ['href' => 'registration_requests.php', 'icon' => 'fa-user-plus',  'label' => 'Registration Requests', 'active' => ['registration_requests.php']],
        ['href' => 'recognized_organizations.php','icon' => 'fa-building',  'label' => 'Organization Records',  'active' => ['recognized_organizations.php']],
        ['href' => 'organizations.php',         'icon' => 'fa-users-gear', 'label' => 'Account Management',    'active' => ['organizations.php']],
    ];
} else {
    $nav_items = [
        ['href' => 'org_dashboard.php', 'icon' => 'fa-gauge-high',        'label' => 'Dashboard',        'active' => ['org_dashboard.php']],
        ['href' => 'borrow_list.php',   'icon' => 'fa-magnifying-glass',  'label' => 'Browse Equipment', 'active' => ['borrow.php', 'borrow_list.php']],
        ['href' => 'org_history.php',   'icon' => 'fa-clock-rotate-left', 'label' => 'History',          'active' => ['org_history.php']],
    ];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary:           #2c3e50;
        --accent:            #f1c40f;
        --bg:                #f4f7f6;
        --text-muted:        #bdc3c7;
        --header-h:          70px;
        --sidebar-expanded:  260px;
        --sidebar-collapsed: 75px;
        --transition:        all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg); }

    /* ── TOP HEADER ── */
    .top-header {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: var(--header-h);
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
        z-index: 2000;
        border-bottom: 1px solid #eef0f2;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Header hamburger — permanently hidden */
    .menu-btn {
        display: none !important;
    }

    .header-left .logo {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }
    .header-left .logo img {
        height: 42px;
        width: auto;
        object-fit: contain;
        display: block;
    }

    .header-right { display: flex; align-items: center; gap: 12px; }
    .user-info { text-align: right; line-height: 1.2; }
    .user-info .name { display: block; font-weight: 700; color: #333; font-size: 14px; }
    .user-info .role { font-size: 11px; color: #888; text-transform: uppercase; }

    .user-avatar {
        width: 40px; height: 40px;
        background: var(--primary);
        color: #fff;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold;
    }

    /* ── SIDEBAR ── */
    .sidebar {
        width: var(--sidebar-expanded);
        background-color: var(--primary);
        height: calc(100vh - var(--header-h));
        position: fixed;
        top: var(--header-h);
        left: 0;
        z-index: 1000;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
    }

    .sidebar-header {
        padding: 20px 27px;
        cursor: pointer;
        color: #fff;
        display: flex;
        align-items: center;
        transition: 0.2s;
        flex-shrink: 0;
    }
    .sidebar-header:hover { background: rgba(255,255,255,0.05); }
    .sidebar-header i { width: 25px; text-align: center; font-size: 20px; }

    .nav-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
    .nav-item  { padding: 2px 12px; white-space: nowrap; }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 14px 15px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 14px;
        border-radius: 8px;
        transition: 0.2s;
    }
    .nav-link i     { min-width: 25px; text-align: center; font-size: 18px; }
    .nav-text       { margin-left: 15px; transition: opacity 0.2s; }
    .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); }

    .nav-item.active .nav-link {
        color: var(--accent);
        font-weight: 600;
        background: rgba(255,255,255,0.03);
    }

    /* Desktop collapsed state */
    body.collapsed .sidebar      { width: var(--sidebar-collapsed); }
    body.collapsed .nav-text     { opacity: 0; pointer-events: none; }
    body.collapsed .main-wrapper { margin-left: var(--sidebar-collapsed); }

    .logout-section {
        margin-top: auto;
        padding-bottom: 20px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }

    .main-wrapper {
        margin-left: var(--sidebar-expanded);
        margin-top: var(--header-h);
        padding: 40px;
        box-sizing: border-box;
        transition: var(--transition);
    }

    /* ── MOBILE ── */
    @media (max-width: 992px) {

        /* Sidebar always visible on mobile, starts collapsed (icon-only) */
        .sidebar {
            width: var(--sidebar-collapsed);
            transform: none;
        }

        /* Main content always offset by collapsed sidebar */
        .main-wrapper,
        body.collapsed .main-wrapper {
            margin-left: var(--sidebar-collapsed);
        }

        /* Hide nav labels in mobile collapsed state */
        .nav-text {
            opacity: 0;
            pointer-events: none;
            width: 0;
            overflow: hidden;
        }

        /* Center icons when collapsed */
        .nav-item { padding: 2px 8px; }
        .nav-link  { justify-content: center; padding: 14px 0; }

        /* Mobile expanded: sidebar grows to full width */
        body.mobile-expanded .sidebar {
            width: var(--sidebar-expanded);
        }

        body.mobile-expanded .main-wrapper {
            margin-left: var(--sidebar-expanded);
        }

        /* Show labels when mobile expanded */
        body.mobile-expanded .nav-text {
            opacity: 1;
            pointer-events: auto;
            width: auto;
            overflow: visible;
        }

        body.mobile-expanded .nav-item { padding: 2px 12px; }
        body.mobile-expanded .nav-link  { justify-content: flex-start; padding: 14px 15px; }
    }
</style>

<header class="top-header">
    <div class="header-left">
        <a href="<?php echo $home_link; ?>" class="logo">
            <img src="assets/images/tooltracelogo.png" alt="ToolTrace logo">
        </a>
    </div>
    <div class="header-right">
        <div class="user-info">
            <span class="name"><?php echo htmlspecialchars($user_name); ?></span>
            <span class="role"><?php echo htmlspecialchars($user_subtitle); ?></span>
        </div>
        <div class="user-avatar"><?php echo $initials; ?></div>
    </div>
</header>

<aside class="sidebar">
    <!-- Single hamburger — lives in the sidebar on all screen sizes -->
    <div class="sidebar-header" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>

    <ul class="nav-menu">
    <?php foreach ($nav_items as $item): ?>
        <li class="nav-item <?php echo in_array($current_page, $item['active'], true) ? 'active' : ''; ?>">
            <a href="<?php echo $item['href']; ?>" class="nav-link">
                <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                <span class="nav-text"><?php echo $item['label']; ?></span>
            </a>
        </li>
    <?php endforeach; ?>

    <li class="nav-item logout-section">
        <a href="signout.php" class="nav-link" style="color: #ff7675;">
            <i class="fa-solid fa-power-off"></i>
            <span class="nav-text">Sign Out</span>
        </a>
    </li>
</ul>
</aside>

<script>
    function toggleSidebar() {
        if (window.innerWidth <= 992) {
            // Mobile: toggle between icon-only and expanded
            document.body.classList.toggle('mobile-expanded');
        } else {
            // Desktop: toggle collapsed/expanded
            document.body.classList.toggle('collapsed');
            localStorage.setItem('sidebarState', document.body.classList.contains('collapsed') ? 'collapsed' : 'expanded');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Restore desktop sidebar state only
        if (window.innerWidth > 992 && localStorage.getItem('sidebarState') === 'collapsed') {
            document.body.classList.add('collapsed');
        }
    });
</script>