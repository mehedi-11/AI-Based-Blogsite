<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/gemini.php';

header('Content-Type: application/json');
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$db_conn = $db; 

switch ($action) {
    case 'publish_hybrid_post':
        if (!has_permission('ai_writer')) {
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        if (!$category_id) {
            echo json_encode(['error' => 'Valid category is required']);
            exit;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        if (empty($slug)) $slug = 'post-' . time();
        
        // Handle physical image upload
        $featured_image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = 'assets/uploads/' . $filename;
            }
        }

        // publish_hybrid_post ending block
        try {
            $stmt = $db_conn->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, category_id, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $category_id, $_SESSION['user_id']]);
            echo json_encode(["success" => true, "id" => $db_conn->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'update_hybrid_post':
        if (!has_permission('posts')) {
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $post_id = isset($_POST['post_id']) && is_numeric($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        if (!$category_id || !$post_id) {
            echo json_encode(['error' => 'Valid category and post ID required.']);
            exit;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Handle physical image upload if a new one is provided
        $featured_image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = 'assets/uploads/' . $filename;
            }
        }

        try {
            if ($featured_image) {
                $stmt = $db_conn->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $category_id, $post_id]);
            } else {
                $stmt = $db_conn->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $post_id]);
            }
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'ai_generate_excerpt':
        if (!has_permission('ai_writer')) { echo json_encode(['error' => 'Forbidden']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['topic'])) { echo json_encode(['error' => 'Topic missing']); exit; }
        $gemini = new GeminiService();
        echo json_encode(['excerpt' => $gemini->generateExcerpt($input['topic'])]);
        break;

    case 'ai_generate_content':
        if (!has_permission('ai_writer')) { echo json_encode(['error' => 'Forbidden']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['topic'])) { echo json_encode(['error' => 'Topic missing']); exit; }
        $gemini = new GeminiService();
        echo json_encode(['content' => $gemini->generateHtmlContent($input['topic'])]);
        break;

    // --- VISITOR SOCIAL ENDPOINTS ---
    case 'like_post':
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = $input['post_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        try {
            $stmt = $db_conn->prepare("INSERT INTO post_likes (post_id, ip_address) VALUES (?, ?)");
            $stmt->execute([$post_id, $ip]);
            
            // Get new count
            $countStmt = $db_conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
            $countStmt->execute([$post_id]);
            echo json_encode(['success' => true, 'likes' => $countStmt->fetchColumn()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'info' => 'Already liked']);
        }
        break;

    case 'share_post':
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = $input['post_id'] ?? 0;
        $platform = $input['platform'] ?? 'web';
        
        $stmt = $db_conn->prepare("INSERT INTO post_shares (post_id, platform) VALUES (?, ?)");
        $stmt->execute([$post_id, $platform]);
        
        $countStmt = $db_conn->prepare("SELECT COUNT(*) FROM post_shares WHERE post_id = ?");
        $countStmt->execute([$post_id]);
        echo json_encode(['success' => true, 'shares' => $countStmt->fetchColumn()]);
        break;

    case 'submit_comment':
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = $input['post_id'] ?? 0;
        $name = trim($input['name'] ?? '');
        $content = trim($input['content'] ?? '');
        
        if(!$name || !$content) {
            echo json_encode(['error' => 'All fields required']);
            exit;
        }
        
        $stmt = $db_conn->prepare("INSERT INTO comments (post_id, name, content) VALUES (?, ?, ?)");
        if($stmt->execute([$post_id, $name, $content])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
?>
