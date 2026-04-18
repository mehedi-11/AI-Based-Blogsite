<?php
require_once 'config.php';
require_once 'functions.php';

require_login();
$user = get_current_user_details();
$settings = get_all_settings();

// Dynamic CSS overrides just like the visitor header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= htmlspecialchars($settings['site_name'] ?? 'AI Blog') ?></title>
    
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
                    fontFamily: {
                        sans: ['"Josefin Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: '#6366f1', // Indigo-500
                        brandDark: '#4f46e5',
                        brandLight: '#e0e7ff'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f1f5f9; color: #0f172a; }
        /* Hide scrollbar for a cleaner sidebar look */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-slate-100 flex h-screen overflow-hidden">

    <!-- Mobile Header/Nav Toggle -->
    <div class="md:hidden flex items-center justify-between bg-white px-4 py-3 border-b border-slate-200 fixed w-full z-50">
        <div class="font-bold text-lg"><i class="fa-solid fa-pen-nib text-brand"></i> Command Center</div>
        <button id="mobileMenuBtn" class="text-slate-500 focus:outline-none focus:ring-2 focus:ring-brand rounded-md p-1">
            <i class="fa-solid fa-bars fa-lg"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white border-r border-slate-200 w-64 flex-shrink-0 flex flex-col h-full absolute md:relative z-40 transition-transform transform -translate-x-full md:translate-x-0 pt-16 md:pt-0">
        <div class="p-6 hidden md:block">
            <h2 class="font-bold text-xl tracking-tight text-slate-900"><i class="fa-solid fa-pen-nib text-brand"></i> Command Center</h2>
        </div>
        <nav class="flex-1 overflow-y-auto no-scrollbar px-4 pb-4 flex flex-col gap-1">
            <?php if (has_permission('dashboard')): ?>
                <a href="<?= BASE_URL ?>admin/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700 <?= basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == '' ? 'bg-brandLight text-brand' : '' ?>">
                    <i class="fa-solid fa-gauge w-5 text-center"></i> Dashboard
                </a>
            <?php endif; ?>
            
            <?php if (has_permission('ai_writer')): ?>
                <a href="<?= BASE_URL ?>admin/create_post.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-bold text-brand hover:bg-brandLight <?= basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'bg-brandLight' : '' ?>">
                    <i class="fa-solid fa-pen-nib w-5 text-center"></i> Create Post
                </a>
            <?php endif; ?>

            <?php if (has_permission('posts')): ?>
                <a href="<?= BASE_URL ?>admin/posts.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700 <?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'bg-brandLight text-brand' : '' ?>">
                    <i class="fa-solid fa-table-list w-5 text-center"></i> Manage Posts
                </a>
                <a href="<?= BASE_URL ?>admin/categories.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700 <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-brandLight text-brand' : '' ?>">
                    <i class="fa-solid fa-tags w-5 text-center"></i> Categories
                </a>
            <?php endif; ?>

            <?php if (has_permission('settings')): ?>
                <a href="<?= BASE_URL ?>admin/settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700 <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-brandLight text-brand' : '' ?>">
                    <i class="fa-solid fa-gear w-5 text-center"></i> Site Settings
                </a>
            <?php endif; ?>

            <?php if (has_permission('users')): ?>
                <a href="<?= BASE_URL ?>admin/users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700 <?=  basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-brandLight text-brand' : '' ?>">
                    <i class="fa-solid fa-users w-5 text-center"></i> Manage Admins
                </a>
            <?php endif; ?>
            
            <hr class="my-4 border-slate-200">
            <a href="<?= BASE_URL ?>" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-slate-700">
                <i class="fa-solid fa-globe w-5 text-center"></i> Visit Site
            </a>
            <a href="<?= BASE_URL ?>admin/profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium hover:bg-slate-50 text-indigo-600 <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-brandLight' : '' ?>">
                <i class="fa-solid fa-user-circle w-5 text-center"></i> My Profile
            </a>
            <a href="<?= BASE_URL ?>admin/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium text-red-600 hover:bg-red-50 mt-auto">
                <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-30 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8 pt-20 md:pt-8 bg-slate-50 relative">

    <script>
        const btn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        if(btn) btn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);
    </script>
