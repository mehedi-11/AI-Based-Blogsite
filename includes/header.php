<?php
require_once 'config.php';
require_once 'functions.php';

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
    <title><?= htmlspecialchars($site_name) ?></title>
    
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
    <style>
        <?= get_theme_css_variables() ?>
    </style>
</head>
<body>

<nav class="navbar glass">
    <div class="container nav-container">
        <a href="<?= BASE_URL ?>" class="nav-logo">
            <?php if ($logo_url): ?>
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo">
            <?php endif; ?>
            <span><?= htmlspecialchars($site_name) ?></span>
        </a>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>">Home</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= BASE_URL ?>admin/index.php" class="btn btn-primary" style="padding: 0.4rem 1rem">Admin Panel</a>
                <a href="<?= BASE_URL ?>admin/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>admin/login.php" class="btn btn-outline" style="padding: 0.4rem 1rem">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div style="padding-top: 80px;"></div> <!-- Spacer for fixed navbar -->
