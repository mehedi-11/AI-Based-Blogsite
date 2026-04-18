<?php
require_once 'config.php';

// Get a setting from the database
function get_setting($key) {
    global $db;
    $stmt = $db->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : null;
}

// Get all settings
function get_all_settings() {
    global $db;
    $stmt = $db->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key_name']] = $row['value'];
    }
    return $settings;
}

// Authentication check
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user details
function get_current_user_details() {
    global $db;
    if (!is_logged_in()) return null;
    $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user has permission to view a specific module
function has_permission($module) {
    global $db;
    if (!is_logged_in()) return false;
    
    $user = get_current_user_details();
    if ($user['role'] === 'super_admin') {
        return true; 
    }
    
    $stmt = $db->prepare("SELECT id FROM admin_permissions WHERE user_id = ? AND module = ?");
    $stmt->execute([$user['id'], $module]);
    return $stmt->fetch() !== false;
}

// Force login redirect
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit;
    }
}

// Force permission check redirect
function require_permission($module) {
    require_login();
    if (!has_permission($module)) {
        die("<h1>403 Forbidden</h1><p>You do not have permission to view the {$module} module.</p><a href='" . BASE_URL . "admin/index.php'>Go Back</a>");
    }
}

// Global Theme Definitions (Hardcoded Light and Purple per request)
function get_theme_css_variables() {
    $bg = '#f8fafc';
    $accent = '#8b5cf6'; // Purple accent
    $text = '#0f172a';
    $text_muted = '#64748b';
    $glass = 'rgba(0,0,0,0.03)';
    $glass_border = 'rgba(0,0,0,0.1)';
    
    $css = ":root { \n";
    $css .= "  --primary: {$bg};\n";
    $css .= "  --secondary: color-mix(in srgb, {$bg} 90%, 'black');\n";
    $css .= "  --accent: {$accent};\n";
    $css .= "  --text: {$text};\n";
    $css .= "  --text-muted: {$text_muted};\n";
    $css .= "  --glass: {$glass};\n";
    $css .= "  --glass-border: {$glass_border};\n";
    $css .= "}";
    
    return $css;
}
?>
