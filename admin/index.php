<?php
require_once '../includes/admin_header.php';
require_permission('dashboard');

$stmt = $db->query("SELECT COUNT(*) as c FROM posts");
$post_count = $stmt->fetch()['c'];

$stmt = $db->query("SELECT COUNT(*) as c FROM users");
$user_count = $stmt->fetch()['c'];

$stmt = $db->query("SELECT title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
$recent_posts = $stmt->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">Welcome, <?= htmlspecialchars($user['username']) ?></h1>
    <p class="text-slate-500 mt-1">Here's what's happening today.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center">
        <div class="bg-brandLight text-brand rounded-lg p-4 mr-4 flex-shrink-0">
            <i class="fa-solid fa-file-lines fa-xl"></i>
        </div>
        <div>
            <div class="text-slate-500 text-sm font-medium uppercase tracking-wider">Total Articles</div>
            <div class="text-3xl font-bold text-slate-900"><?= $post_count ?></div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center">
        <div class="bg-emerald-100 text-emerald-600 rounded-lg p-4 mr-4 flex-shrink-0">
            <i class="fa-solid fa-users fa-xl"></i>
        </div>
        <div>
            <div class="text-slate-500 text-sm font-medium uppercase tracking-wider">System Admins</div>
            <div class="text-3xl font-bold text-slate-900"><?= $user_count ?></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-0 mb-8 overflow-hidden">
    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-clock rotate-icon text-slate-400 mr-2"></i> Recent Articles</h2>
    </div>
    <div class="p-6">
        <?php if (count($recent_posts) > 0): ?>
            <ul class="divide-y divide-slate-100 -my-4">
                <?php foreach($recent_posts as $p): ?>
                    <li class="py-4 flex flex-col md:flex-row md:justify-between md:items-center group gap-2 md:gap-0">
                        <strong class="text-slate-700 group-hover:text-brand transition-colors text-lg"><?= htmlspecialchars($p['title']) ?></strong>
                        <span class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-lg w-max whitespace-nowrap"><i class="fa-regular fa-calendar mr-1"></i> <?= date('F j, Y', strtotime($p['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fa-solid fa-folder-open text-slate-300 fa-3x mb-3"></i>
                <p class="text-slate-500">No articles yet. Generate one in the <a href="create_post.php" class="text-brand hover:underline font-bold">Create Post</a> module!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../includes/admin_footer.php'; 
?>
