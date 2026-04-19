<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$settings = get_all_settings();
$site_name = $settings['site_name'] ?? 'AI Blogsite';
$logo_url = $settings['logo_url'] ?? '';
$accent_color = $settings['accent_color'] ?? '#3b82f6';
$bg_color = $settings['bg_color'] ?? '#0f172a';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? $site_name) ?></title>
    
    <?php if (isset($page_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <?php endif; ?>
    
    <?php if (isset($page_keywords)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <?php endif; ?>
    
    <!-- OpenGraph Social Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title ?? $site_name) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($og_type ?? 'website') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($og_url ?? (BASE_URL . $_SERVER['REQUEST_URI'])) ?>">
    <?php if (isset($page_description)): ?>
        <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <?php endif; ?>
    <?php if (isset($og_image)): ?>
        <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <?php endif; ?>
    
    <?php if (!empty($settings['favicon_url'])): ?>
        <link rel="icon" href="<?= BASE_URL . htmlspecialchars($settings['favicon_url']) ?>" type="image/x-icon">
    <?php endif; ?>
    
    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <!-- Dynamic Theme Injection -->
    <style id="themeStyleTag">
        <?= get_theme_css_variables() ?>
    </style>
    
    <!-- LocalStorage Theme Init -->
    <script>
        const savedTheme = localStorage.getItem('theme_preference');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark-mode');
        } else if (savedTheme === 'light') {
            document.documentElement.classList.remove('dark-mode');
        }
    </script>
</head>
<body class="transition-colors duration-300">

<nav class="navbar glass">
    <div class="container nav-container">
        <a href="<?= BASE_URL ?>" class="nav-logo">
            <?php if ($logo_url): ?>
                <?php 
                    $full_logo_path = (strpos($logo_url, 'http') === 0 || strpos($logo_url, '/') === 0) ? $logo_url : BASE_URL . $logo_url;
                ?>
                <img src="<?= htmlspecialchars($full_logo_path) ?>" alt="Logo">
            <?php endif; ?>
            <span><?= htmlspecialchars($site_name) ?></span>
        </a>
        <div class="nav-links" style="display: flex; align-items: center; gap: 1rem;">
            <a href="<?= BASE_URL ?>">Home</a>
            
            <!-- Theme Toggle -->
            <button id="themeToggleBtn" style="background: none; border: none; cursor: pointer; color: var(--text); font-size: 1.2rem; margin: 0 0.5rem;" title="Toggle Dark/Light Mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>
            
            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-primary" style="padding: 0.4rem 1rem">Admin Panel</a>
                <a href="<?= BASE_URL ?>admin/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>admin/login.php" class="btn btn-outline" style="padding: 0.4rem 1rem">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    const themeBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    
    // Set initial icon based on localStorage
    if (localStorage.getItem('theme_preference') === 'dark') {
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
    }
    
    themeBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark-mode');
        if (document.documentElement.classList.contains('dark-mode')) {
            localStorage.setItem('theme_preference', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            localStorage.setItem('theme_preference', 'light');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
        
        // Let CSS handle the transition via .dark-mode overrides if we implement them natively
    });
</script>

<div style="padding-top: 80px;"></div> <!-- Spacer for fixed navbar -->
