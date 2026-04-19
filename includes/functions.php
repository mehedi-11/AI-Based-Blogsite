<?php
require_once __DIR__ . '/config.php';

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
    $stmt = $db->prepare("SELECT id, name, username, email, role, avatar, status, suspended_reason FROM users WHERE id = ?");
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

// Force login redirect — also boots suspended users immediately
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "admin/login.php");
        exit;
    }
    // Check suspension on EVERY page load (instant kick-out)
    global $db;
    $stmt = $db->prepare("SELECT status, suspended_reason FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && $row['status'] === 'suspended') {
        // Store reason in session flash so login page modal can show it
        $_SESSION['suspended_reason'] = $row['suspended_reason'] ?? 'No reason provided.';
        session_destroy();
        header("Location: " . BASE_URL . "admin/login.php?suspended=1");
        exit;
    }
    // Track last activity for active-user widget
    $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);
}

// Force permission check redirect
function require_permission($module) {
    require_login();
    if (!has_permission($module)) {
        die("<h1>403 Forbidden</h1><p>You do not have permission to view the {$module} module.</p><a href='" . BASE_URL . "admin/index.php'>Go Back</a>");
    }
}

// ── Notification Helpers ─────────────────────────────────────────

/**
 * Log a notification.
 * scope='global'  → visible to super admin.
 * scope='user'    → visible to recipient_id user.
 * Pass BOTH scopes by calling twice if needed.
 */
function log_notification(string $type, string $message, ?string $link = null, ?int $recipient_id = null, ?int $actor_id = null): void {
    global $db;
    $scope    = $recipient_id ? 'user' : 'global';
    $actor_id = $actor_id ?? ($_SESSION['user_id'] ?? null);
    // Resolve actor name
    $actor_name = null;
    if ($actor_id) {
        $r = $db->prepare("SELECT COALESCE(NULLIF(name,''), username) FROM users WHERE id = ?");
        $r->execute([$actor_id]);
        $actor_name = $r->fetchColumn();
    }
    $db->prepare("
        INSERT INTO notifications (type, actor_id, actor_name, message, link, scope, recipient_id)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([$type, $actor_id, $actor_name, $message, $link, $scope, $recipient_id]);
}

/** Get unread notification count for the given user. */
function get_unread_notification_count(int $user_id, string $role): int {
    global $db;
    if ($role === 'super_admin') {
        // Super admin sees global + their own
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE is_read = 0
              AND (scope = 'global' OR (scope='user' AND recipient_id = ?))
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE is_read = 0 AND scope = 'user' AND recipient_id = ?
        ");
        $stmt->execute([$user_id]);
    }
    return (int)$stmt->fetchColumn();
}

/** Fetch recent notifications for the bell dropdown. */
function get_recent_notifications(int $user_id, string $role, int $limit = 8): array {
    global $db;
    if ($role === 'super_admin') {
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE scope = 'global' OR (scope='user' AND recipient_id = :uid)
            ORDER BY created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE scope = 'user' AND recipient_id = :uid
            ORDER BY created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    return $stmt->fetchAll();
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
    $css .= "}\n";
    
    // Dark Mode Theme
    $dark_bg = '#0f172a'; // Slate-900
    $dark_secondary = '#1e293b'; // Slate-800
    $dark_text = '#f8fafc'; // Slate-50
    $dark_text_muted = '#94a3b8'; // Slate-400
    $dark_glass = 'rgba(255,255,255,0.05)';
    $dark_glass_border = 'rgba(255,255,255,0.1)';
    
    $css .= ":root.dark-mode {\n";
    $css .= "  --primary: {$dark_bg};\n";
    $css .= "  --secondary: {$dark_secondary};\n";
    $css .= "  --text: {$dark_text};\n";
    $css .= "  --text-muted: {$dark_text_muted};\n";
    $css .= "  --glass: {$dark_glass};\n";
    $css .= "  --glass-border: {$dark_glass_border};\n";
    $css .= "}\n";
    
    return $css;
}

// Calculate estimated reading time
function estimate_reading_time($text) {
    if (empty(trim($text))) return 1;
    $word_count = str_word_count(strip_tags($text));
    $minutes = ceil($word_count / 200); // 200 words per minute average
    return max(1, $minutes);
}
?>
