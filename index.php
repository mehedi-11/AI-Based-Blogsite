<?php
require_once 'includes/header.php';

// Fetch all published posts
$stmt = $db->query("
    SELECT p.*, c.name as category_name 
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'published' 
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<section class="hero glass" style="margin-top: -2rem; border-radius: 0 0 32px 32px; border-top: none;">
    <div class="container">
        <h1 class="gradient-text" style="margin-top: 2rem;">Intelligence Meets Creativity</h1>
        <p>Explore a new world of thoughts powered by advanced AI architecture. Formal, insightful, and always ahead of the curve.</p>
    </div>
</section>

<div class="container mt-2 mb-2">
    <h2 style="font-size: 2.5rem; margin-bottom: 2rem;">Recent Intelligence</h2>
    
    <?php if (count($posts) === 0): ?>
        <p style="color: var(--text-muted)">No intelligence recorded yet.</p>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach($posts as $post): ?>
                <div class="card glass" style="padding: 0; overflow: hidden; border-radius: 8px;">
                    <?php if (!empty($post['featured_image'])): ?>
                        <?php $full_image = (strpos($post['featured_image'], 'http') === 0 || strpos($post['featured_image'], '/') === 0) ? $post['featured_image'] : BASE_URL . $post['featured_image']; ?>
                        <div style="height: 200px; width: 100%; background: url('<?= htmlspecialchars($full_image) ?>') center/cover;"></div>
                    <?php else: ?>
                        <div style="height: 200px; width: 100%; background: var(--secondary); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            <i class="fa-solid fa-image fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; flex-grow: 1;">
                        <span class="card-meta" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span><i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></span>
                            <span style="color: var(--text-muted); font-size: 0.85em;"><i class="fa-solid fa-clock"></i> <?= estimate_reading_time($post['content']) ?> Min</span>
                        </span>
                        <h3 style="font-size: 1.25rem; font-family: var(--font-main); line-height: 1.3;"><?= htmlspecialchars($post['title']) ?></h3>
                        <p style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.95rem; color: var(--text-muted);">
                            <?= htmlspecialchars($post['excerpt']) ?>
                        </p>
                        <a href="post.php?slug=<?= urlencode($post['slug']) ?>" style="color: var(--accent); font-weight: 600; margin-top: auto; display: inline-flex; align-items: center; gap: 0.5rem;">
                            Read Article <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
