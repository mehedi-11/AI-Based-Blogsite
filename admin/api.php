<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/ai_service.php';

header('Content-Type: application/json');
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? '';
$db_conn = $db; 

// Trackers
switch ($action) {
    case 'publish_hybrid_post':
        if (!is_logged_in() || !has_permission('ai_writer')) {
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $seo_keywords = $_POST['seo_keywords'] ?? null;
        $category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $author_id = isset($_POST['author_id']) && is_numeric($_POST['author_id']) ? (int)$_POST['author_id'] : $_SESSION['user_id'];
        
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

        try {
            $stmt = $db_conn->prepare("INSERT INTO posts (title, slug, content, excerpt, seo_keywords, featured_image, category_id, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $excerpt, $seo_keywords, $featured_image, $category_id, $author_id]);
            $post_id = $db_conn->lastInsertId();

            // Log: Super Admin notification
            log_notification('post_created', "New post created: \"$title\"", BASE_URL . "admin/edit_post.php?id=$post_id");

            echo json_encode(["success" => true, "id" => $post_id]);
        } catch (PDOException $e) {
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'update_hybrid_post':
        if (!is_logged_in() || !has_permission('posts')) {
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $post_id = isset($_POST['post_id']) && is_numeric($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $seo_keywords = $_POST['seo_keywords'] ?? null;
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
                $stmt = $db_conn->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, seo_keywords = ?, featured_image = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $excerpt, $seo_keywords, $featured_image, $category_id, $post_id]);
            } else {
                $stmt = $db_conn->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, seo_keywords = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $excerpt, $seo_keywords, $category_id, $post_id]);
            }

            // Log: Super Admin notification
            log_notification('post_edited', "Post updated: \"$title\"", BASE_URL . "admin/edit_post.php?id=$post_id");

            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'ai_generate_excerpt':
    case 'ai_generate_content':
        if (!is_logged_in() || !has_permission('ai_writer')) { echo json_encode(['error' => 'Forbidden']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['topic'])) { echo json_encode(['error' => 'Topic missing']); exit; }
        $ai = new AIService();
        
        if ($action === 'ai_generate_excerpt') {
            $response = $ai->generateExcerpt($input['topic']);
            echo json_encode(is_array($response) && isset($response['error']) ? $response : ['excerpt' => $response]);
        } else {
            $response = $ai->generateBlogPost($input['topic']);
            echo json_encode($response);
        }
        break;

    // --- VISITOR SOCIAL ENDPOINTS (Public) ---
    case 'like_post':
        $input = json_decode(file_get_contents('php://input'), true);
        $post_id = $input['post_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        try {
            $stmt = $db_conn->prepare("INSERT INTO post_likes (post_id, ip_address) VALUES (?, ?)");
            $stmt->execute([$post_id, $ip]);
            
            // Get post info for notification
            $p_stmt = $db_conn->prepare("SELECT title, author_id FROM posts WHERE id = ?");
            $p_stmt->execute([$post_id]);
            $p_data = $p_stmt->fetch();

            if ($p_data) {
                $msg = "Someone liked your post: \"{$p_data['title']}\"";
                // Notify Author (Private)
                log_notification('post_liked', $msg, BASE_URL . "post.php?slug=" . $post_id, $p_data['author_id'], null);
                // Notify Super Admin (Global)
                log_notification('post_liked', "Activity: \"{$p_data['title']}\" received a like.", null, null, null);
            }

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
        
        try {
            $stmt = $db_conn->prepare("INSERT INTO post_shares (post_id, platform) VALUES (?, ?)");
            $stmt->execute([$post_id, $platform]);

            // Get post info
            $p_stmt = $db_conn->prepare("SELECT title, author_id FROM posts WHERE id = ?");
            $p_stmt->execute([$post_id]);
            $p_data = $p_stmt->fetch();

            if ($p_data) {
                $msg = "Your post was shared: \"{$p_data['title']}\"";
                log_notification('post_shared', $msg, null, $p_data['author_id'], null);
                log_notification('post_shared', "Activity: \"{$p_data['title']}\" was shared via $platform.", null, null, null);
            }
            
            $countStmt = $db_conn->prepare("SELECT COUNT(*) FROM post_shares WHERE post_id = ?");
            $countStmt->execute([$post_id]);
            echo json_encode(['success' => true, 'shares' => $countStmt->fetchColumn()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
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
        
        try {
            $stmt = $db_conn->prepare("INSERT INTO comments (post_id, name, content) VALUES (?, ?, ?)");
            if($stmt->execute([$post_id, $name, $content])) {
                // Get post info
                $p_stmt = $db_conn->prepare("SELECT title, author_id FROM posts WHERE id = ?");
                $p_stmt->execute([$post_id]);
                $p_data = $p_stmt->fetch();

                if ($p_data) {
                    $msg = "$name commented on: \"{$p_data['title']}\"";
                    log_notification('comment_added', $msg, BASE_URL . "admin/index.php", $p_data['author_id'], null);
                    log_notification('comment_added', "New comment from $name on \"{$p_data['title']}\"", null, null, null);
                }
                echo json_encode(['success' => true]);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
?>
