<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();
$user = get_current_user_details();
$settings = get_all_settings();

// Fetch Notifications early for UI use
$notif_count = get_unread_notification_count((int)$user['id'], $user['role']);
$notif_items = get_recent_notifications((int)$user['id'], $user['role'], 8);
$notif_icon_map = [
    'post_created'     => ['fa-file-circle-plus', 'text-emerald-500'],
    'post_edited'      => ['fa-pen-to-square',    'text-blue-500'],
    'post_deleted'     => ['fa-trash',            'text-red-400'],
    'comment_added'    => ['fa-comment',          'text-sky-500'],
    'post_liked'       => ['fa-thumbs-up',        'text-pink-500'],
    'post_shared'      => ['fa-share-nodes',      'text-purple-500'],
    'profile_updated'  => ['fa-user-pen',         'text-amber-500'],
    'user_suspended'   => ['fa-ban',              'text-red-500'],
    'user_unsuspended' => ['fa-lock-open',        'text-emerald-500'],
    'user_added'       => ['fa-user-plus',        'text-brand'],
    'user_deleted'     => ['fa-user-minus',       'text-red-400'],
    'category_added'   => ['fa-tags',             'text-indigo-400'],
    'category_deleted' => ['fa-tag',              'text-orange-400'],
];

$current_page = basename($_SERVER['PHP_SELF']);

// Helper: returns active classes if current page matches
function nav_active(string $page): string {
    global $current_page;
    return $current_page === $page
        ? 'bg-brandLight text-brand font-semibold'
        : 'text-slate-600 hover:bg-slate-100 font-medium';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= htmlspecialchars($settings['site_name'] ?? 'AI Blog') ?></title>

    <?php if (!empty($settings['favicon_url'])): ?>
        <link rel="icon" href="<?= BASE_URL . htmlspecialchars($settings['favicon_url']) ?>" type="image/x-icon">
    <?php endif; ?>

    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Josefin Sans"', 'sans-serif'] },
                    colors: {
                        brand:      '#6366f1',
                        brandDark:  '#4f46e5',
                        brandLight: '#e0e7ff'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f1f5f9; color: #0f172a; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .nav-section-label {
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            padding: 0 0.75rem;
            margin-top: 1.25rem;
            margin-bottom: 0.3rem;
            display: block;
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-slate-100 flex h-screen overflow-hidden">

    <!-- Mobile Top Bar -->
    <div class="md:hidden flex items-center justify-between bg-white px-4 py-3 border-b border-slate-200 fixed w-full z-50">
        <span class="font-bold text-lg text-slate-900">Command Center</span>
        <div class="flex items-center gap-3">
            <!-- Mobile Notif Bell -->
            <button id="mobileNotifBtn" class="relative text-slate-500 hover:text-brand transition-colors focus:outline-none">
                <i class="fa-solid fa-bell fa-lg"></i>
                <?php if ($notif_count > 0): ?>
                <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center leading-none">
                    <?= $notif_count > 99 ? '99+' : $notif_count ?>
                </span>
                <?php endif; ?>
            </button>
            <button id="mobileMenuBtn" class="text-slate-500 focus:outline-none focus:ring-2 focus:ring-brand rounded-md p-1">
                <i class="fa-solid fa-bars fa-lg"></i>
            </button>
        </div>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white border-r border-slate-200 w-64 flex-shrink-0 flex flex-col h-full absolute md:relative z-40 transition-transform transform -translate-x-full md:translate-x-0 pt-16 md:pt-0">

        <!-- Brand Header -->
        <div class="px-6 py-5 border-b border-slate-100 hidden md:block">
            <span class="font-bold text-xl tracking-tight text-slate-900">Command Center</span>
            <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($settings['site_name'] ?? 'AI Blog') ?></p>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 overflow-y-auto no-scrollbar px-3 py-3 flex flex-col">

            <!-- ── SECTION 1: Content ──────────────────────────── -->
            <span class="nav-section-label" style="margin-top:0.5rem;">Content</span>

            <?php if (has_permission('dashboard')): ?>
            <a href="<?= BASE_URL ?>admin/index.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('index.php') ?>">
                <i class="fa-solid fa-gauge w-4 text-center text-slate-400"></i>
                <span>Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if (has_permission('ai_writer')): ?>
            <a href="<?= BASE_URL ?>admin/create_post.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('create_post.php') ?>">
                <i class="fa-solid fa-pen-nib w-4 text-center text-slate-400"></i>
                <span>Create Blog</span>
            </a>
            <?php endif; ?>

            <?php if (has_permission('posts')): ?>
            <a href="<?= BASE_URL ?>admin/posts.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('posts.php') ?>">
                <i class="fa-solid fa-table-list w-4 text-center text-slate-400"></i>
                <span>Manage Posts</span>
            </a>
            <a href="<?= BASE_URL ?>admin/categories.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('categories.php') ?>">
                <i class="fa-solid fa-tags w-4 text-center text-slate-400"></i>
                <span>Categories</span>
            </a>
            <a href="<?= BASE_URL ?>admin/notifications.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('notifications.php') ?>">
                <i class="fa-solid fa-bell w-4 text-center text-slate-400"></i>
                <span>Notifications</span>
            </a>
            <?php endif; ?>

            <!-- ── SECTION 2: Administration ──────────────────── -->
            <span class="nav-section-label">Administration</span>

            <?php if (has_permission('users') && $user['role'] !== 'author'): ?>
            <a href="<?= BASE_URL ?>admin/users.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('users.php') ?>">
                <i class="fa-solid fa-users w-4 text-center text-slate-400"></i>
                <span>Manage Admins</span>
            </a>
            <a href="<?= BASE_URL ?>admin/authors.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('authors.php') ?>">
                <i class="fa-solid fa-feather-pointed w-4 text-center text-slate-400"></i>
                <span>Manage Authors</span>
            </a>
            <?php endif; ?>

            <?php if (has_permission('settings')): ?>
            <a href="<?= BASE_URL ?>admin/settings.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('settings.php') ?>">
                <i class="fa-solid fa-gear w-4 text-center text-slate-400"></i>
                <span>Site Settings</span>
            </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>admin/profile.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= nav_active('profile.php') ?>">
                <i class="fa-solid fa-user-circle w-4 text-center text-slate-400"></i>
                <span>My Profile</span>
            </a>

            <!-- ── SECTION 3: Site ────────────────────────────── -->
            <span class="nav-section-label">Site</span>

            <a href="<?= BASE_URL ?>" target="_blank"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-slate-600 hover:bg-slate-100 font-medium">
                <i class="fa-solid fa-globe w-4 text-center text-slate-400"></i>
                <span>Visit Site</span>
            </a>
            <a href="<?= BASE_URL ?>admin/logout.php"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-red-500 hover:bg-red-50 font-medium">
                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                <span>Logout</span>
            </a>

        </nav>

        <!-- Logged-in user strip at bottom -->
        <div class="border-t border-slate-100 px-4 py-3 flex items-center gap-3 flex-shrink-0">
            <?php
            $avatarSrc = !empty($user['avatar'])
                ? BASE_URL . htmlspecialchars($user['avatar'])
                : 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] ?: $user['username']) . '&background=6366f1&color=fff&bold=true&size=40';
            ?>
            <img src="<?= $avatarSrc ?>" alt="avatar"
                 class="w-8 h-8 rounded-full object-cover border border-brandLight flex-shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($user['name'] ?: $user['username']) ?></p>
                <p class="text-xs text-slate-400 capitalize"><?= str_replace('_', ' ', $user['role']) ?></p>
            </div>
        </div>

    </aside>

    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 relative flex flex-col">

    <?php
        // Already fetched at top
    ?>

    <!-- Sticky Top Bar -->
    <div class="sticky top-0 z-30 bg-white border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between gap-4 shadow-sm pt-16 md:pt-3 flex-shrink-0">
        <span class="text-sm text-slate-400 hidden md:block"><?= date('l, F j, Y') ?></span>
        <div class="flex items-center gap-2 ml-auto">
            <!-- Notification Bell (Desktop) -->
            <div class="relative hidden md:block" id="notifWrapper">
                <button id="notifBtn"
                        class="relative p-2.5 rounded-xl text-slate-500 hover:bg-slate-100 hover:text-brand transition-colors focus:outline-none">
                    <i class="fa-solid fa-bell fa-lg"></i>
                    <?php if ($notif_count > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-extrabold w-5 h-5 rounded-full flex items-center justify-center leading-none animate-pulse">
                        <?= $notif_count > 99 ? '99+' : $notif_count ?>
                    </span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Notification Dropdown Panel (Shared) -->
            <div id="notifDropdown"
                 class="hidden absolute right-0 top-full mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden z-[100]">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="font-bold text-slate-800 text-sm">Notifications</span>
                    <span class="text-[10px] bg-brandLight text-brand px-2 py-0.5 rounded-full font-bold uppercase"><?= $notif_count ?> New</span>
                </div>
                <ul class="max-h-[350px] overflow-y-auto divide-y divide-slate-50 no-scrollbar">
                    <?php if (empty($notif_items)): ?>
                    <li class="p-8 text-center">
                        <i class="fa-solid fa-bell-slash text-slate-200 text-3xl mb-2 block"></i>
                        <p class="text-xs text-slate-400 italic">No new notifications</p>
                    </li>
                    <?php else: foreach ($notif_items as $n): 
                        $ico = $notif_icon_map[$n['type']] ?? ['fa-circle-info', 'text-slate-400'];
                    ?>
                    <li>
                        <a href="<?= $n['link'] ?: '#' ?>" class="px-4 py-3.5 flex gap-3 hover:bg-slate-50 transition-colors <?= !$n['is_read'] ? 'bg-indigo-50/20' : '' ?>">
                            <span class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <i class="fa-solid <?= $ico[0] ?> text-sm <?= $ico[1] ?>"></i>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-slate-700 leading-snug line-clamp-2"><?= htmlspecialchars($n['message']) ?></p>
                                <p class="text-[10px] text-slate-400 mt-0.5"><?= date('M j, g:i a', strtotime($n['created_at'])) ?></p>
                            </div>
                            <?php if (!$n['is_read']): ?><span class="w-2 h-2 bg-brand rounded-full flex-shrink-0 mt-2"></span><?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; endif; ?>
                </ul>
                <div class="border-t border-slate-100 px-4 py-2.5 bg-slate-50 text-center">
                    <a href="<?= BASE_URL ?>admin/notifications.php" class="text-xs font-semibold text-brand hover:underline">View all →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content Wrapper -->
    <div class="flex-1 p-4 md:p-8 overflow-x-hidden">

    <script>
        const btn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const notifBtn = document.getElementById('notifBtn');
        const mobileNotifBtn = document.getElementById('mobileNotifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function toggleNotif(e) {
            e.stopPropagation();
            if (notifDropdown) {
                notifDropdown.classList.toggle('hidden');
                // Auto positioning on mobile
                if (window.innerWidth < 768) {
                    notifDropdown.style.position = 'fixed';
                    notifDropdown.style.top = '60px';
                    notifDropdown.style.right = '10px';
                    notifDropdown.style.width = 'calc(100vw - 20px)';
                    notifDropdown.style.maxWidth = '350px';
                } else {
                    notifDropdown.style.position = 'absolute';
                    notifDropdown.style.top = '100%';
                    notifDropdown.style.right = '0';
                    notifDropdown.style.width = '320px';
                }
            }
        }

        if (btn) btn.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);
        if (notifBtn) notifBtn.addEventListener('click', toggleNotif);
        if (mobileNotifBtn) mobileNotifBtn.addEventListener('click', toggleNotif);

        document.addEventListener('click', function(e) {
            if (notifDropdown && !notifDropdown.contains(e.target)) {
                if (notifBtn && !notifBtn.contains(e.target) && mobileNotifBtn && !mobileNotifBtn.contains(e.target)) {
                    notifDropdown.classList.add('hidden');
                }
            }
        });
    </script>
