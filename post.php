<?php
require_once 'includes/header.php';

$slug = $_GET['slug'] ?? '';
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, COALESCE(NULLIF(u.name, ''), u.username) as author_name 
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug = ?
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    echo "<div class='container' style='padding-top: 100px;'><h1>Post not found.</h1></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="container" style="max-width: 900px; padding-top: 3rem;">
    <a href="<?= BASE_URL ?>" style="color: var(--accent); display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Back to Intelligence</a>
    
    <header style="margin-bottom: 3rem; text-align: center;">
        <h1 style="font-size: 3rem; line-height: 1.2; margin-bottom: 1.5rem; font-family: var(--font-main);"><?= htmlspecialchars($post['title']) ?></h1>
        
        <div style="display: flex; justify-content: center; gap: 0.8rem; align-items: center; margin-bottom: 2rem; color: var(--text-muted); font-size: 0.95rem; font-family: var(--font-main);">
            <span><i class="fa-solid fa-calendar-alt"></i> <?= date('F j, Y', strtotime($post['created_at'])) ?></span>
            <span>&bull;</span>
            <span style="color: var(--accent); font-weight: bold;"><i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars($post['category_name']) ?></span>
            <span>&bull;</span>
            <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($post['author_name']) ?></span>
        </div>

        <?php if (!empty($post['featured_image'])): ?>
            <div style="text-align: center; margin-bottom: 2rem;">
                <img src="<?= BASE_URL . htmlspecialchars($post['featured_image']) ?>" alt="Featured Image" style="max-width: 100%; max-height: 500px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            </div>
        <?php endif; ?>

        <p style="font-size: 1.25rem; color: var(--text-muted); font-style: italic; line-height: 1.6; max-width: 800px; margin: 0 auto;">
            <?= htmlspecialchars($post['excerpt']) ?>
        </p>
    </header>

    <!-- Main Content -->
    <div style="font-size: 1.15rem; line-height: 1.9; color: var(--text); margin-bottom: 4rem; text-align: justify; font-family: var(--font-main);">
        <?= $post['content'] ?>
    </div>

    <!-- Social Bar -->
    <?php
    $likes = $db->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
    $likes->execute([$post['id']]);
    $likeCount = $likes->fetchColumn();

    $shares = $db->prepare("SELECT COUNT(*) FROM post_shares WHERE post_id = ?");
    $shares->execute([$post['id']]);
    $shareCount = $shares->fetchColumn();
    ?>
    <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
    <div style="display: flex; justify-content: center; gap: 2rem; align-items: center; margin-bottom: 3rem;">
        <button onclick="likePost(<?= $post['id'] ?>)" class="btn btn-outline" id="likeBtn" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-thumbs-up" style="color: var(--accent);"></i> <span id="likeText"><?= $likeCount ?> Likes</span>
        </button>
        <button onclick="sharePost(<?= $post['id'] ?>)" class="btn btn-outline" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-share-nodes" style="color: #10b981;"></i> <span id="shareText"><?= $shareCount ?> Shares</span>
        </button>
    </div>

    <!-- Comments Section -->
    <div class="card glass" style="margin-bottom: 4rem;">
        <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-comments"></i> Discussion</h3>
        
        <form id="commentForm" style="margin-bottom: 2rem;">
            <input type="hidden" id="commentPostId" value="<?= $post['id'] ?>">
            <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1rem;">
                <input type="text" id="commentName" class="form-control" placeholder="Your Name" required>
            </div>
            <textarea id="commentContent" class="form-control" rows="3" placeholder="Share your thoughts..." required style="margin-bottom: 1rem;"></textarea>
            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>

        <div id="commentsList">
            <?php
            $cmtStmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
            $cmtStmt->execute([$post['id']]);
            $comments = $cmtStmt->fetchAll();
            
            if(count($comments) == 0) {
                echo "<p style='color: var(--text-muted);'>No comments yet. Be the first to start the discussion!</p>";
            } else {
                foreach($comments as $cmt) {
                    echo "<div style='padding: 1rem 0; border-top: 1px solid var(--glass-border);'>";
                    echo "<div style='font-weight: bold; margin-bottom: 0.3rem;'><i class='fa-solid fa-user-circle' style='color: var(--text-muted);'></i> ".htmlspecialchars($cmt['name'])." <span style='font-weight: normal; font-size: 0.8rem; color: var(--text-muted); margin-left: 1rem;'>".date('M j, Y', strtotime($cmt['created_at']))."</span></div>";
                    echo "<div style='color: var(--text);'>".nl2br(htmlspecialchars($cmt['content']))."</div>";
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
async function likePost(postId) {
    try {
        const res = await fetch('admin/api.php?action=like_post', {
            method: 'POST', body: JSON.stringify({post_id: postId})
        });
        const data = await res.json();
        if(data.success) {
            document.getElementById('likeText').innerText = data.likes + ' Likes';
            document.getElementById('likeBtn').style.backgroundColor = 'var(--secondary)';
        } else if (data.info) {
            alert(data.info);
        }
    } catch(e) {}
}

async function sharePost(postId) {
    try {
        const res = await fetch('admin/api.php?action=share_post', {
            method: 'POST', body: JSON.stringify({post_id: postId, platform: 'web'})
        });
        const data = await res.json();
        if(data.success) {
            document.getElementById('shareText').innerText = data.shares + ' Shares';
            // Trigger native share if supported
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                });
            } else {
                alert("Link copied to clipboard!");
                navigator.clipboard.writeText(window.location.href);
            }
        }
    } catch(e) {}
}

document.getElementById('commentForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    
    try {
        const res = await fetch('admin/api.php?action=submit_comment', {
            method: 'POST',
            body: JSON.stringify({
                post_id: document.getElementById('commentPostId').value,
                name: document.getElementById('commentName').value,
                content: document.getElementById('commentContent').value
            })
        });
        const data = await res.json();
        if(data.success) {
            alert("Comment submitted successfully!");
            window.location.reload();
        } else {
            alert(data.error || 'Failed to submit comment');
            btn.disabled = false;
        }
    } catch(e) {
        btn.disabled = false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
