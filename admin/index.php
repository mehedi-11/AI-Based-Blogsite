<?php
require_once '../includes/admin_header.php';
require_permission('dashboard');

// ── Core Stats ────────────────────────────────────────────────────
$total_posts      = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$published_posts  = $db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$draft_posts      = $db->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
$total_categories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_admins     = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('super_admin','admin')")->fetchColumn();
$total_authors    = $db->query("SELECT COUNT(*) FROM users WHERE role = 'author'")->fetchColumn();
$total_comments   = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pending_comments = $db->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$approved_comments= $db->query("SELECT COUNT(*) FROM comments WHERE status='approved'")->fetchColumn();
$total_likes      = $db->query("SELECT COUNT(*) FROM post_likes")->fetchColumn();
$total_shares     = $db->query("SELECT COUNT(*) FROM post_shares")->fetchColumn();

// ── Posts this month ──────────────────────────────────────────────
$posts_this_month = $db->query("SELECT COUNT(*) FROM posts WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// ── Most liked post ──────────────────────────────────────────────
$top_liked = $db->query("
    SELECT p.title, p.slug, COUNT(l.id) as total
    FROM posts p LEFT JOIN post_likes l ON l.post_id = p.id
    WHERE p.status='published'
    GROUP BY p.id ORDER BY total DESC LIMIT 1
")->fetch();

// ── Most shared post ─────────────────────────────────────────────
$top_shared = $db->query("
    SELECT p.title, p.slug, COUNT(s.id) as total
    FROM posts p LEFT JOIN post_shares s ON s.post_id = p.id
    WHERE p.status='published'
    GROUP BY p.id ORDER BY total DESC LIMIT 1
")->fetch();

// ── Most commented post ──────────────────────────────────────────
$top_commented = $db->query("
    SELECT p.title, p.slug, COUNT(c.id) as total
    FROM posts p LEFT JOIN comments c ON c.post_id = p.id
    WHERE p.status='published'
    GROUP BY p.id ORDER BY total DESC LIMIT 1
")->fetch();

// ── Recent 5 Posts ───────────────────────────────────────────────
$recent_posts = $db->query("
    SELECT p.title, p.slug, p.status, p.created_at,
           c.name as category,
           COALESCE(NULLIF(u.name,''), u.username) as author
    FROM posts p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN users u ON u.id = p.author_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

// ── Recent 5 Comments ────────────────────────────────────────────
$recent_comments = $db->query("
    SELECT cm.name, cm.content, cm.status, cm.created_at, p.title as post_title
    FROM comments cm
    LEFT JOIN posts p ON p.id = cm.post_id
    ORDER BY cm.created_at DESC LIMIT 5
")->fetchAll();

// ── Posts by Category ────────────────────────────────────────────
$cat_stats = $db->query("
    SELECT c.name, COUNT(p.id) as total
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id
    GROUP BY c.id ORDER BY total DESC LIMIT 6
")->fetchAll();

// ── Top Authors ──────────────────────────────────────────────────
$top_authors = $db->query("
    SELECT u.name, u.username, u.avatar, COUNT(p.id) as total
    FROM users u
    LEFT JOIN posts p ON p.author_id = u.id
    WHERE u.role IN ('author','admin','super_admin')
    GROUP BY u.id ORDER BY total DESC LIMIT 5
")->fetchAll();

// ── Last 7 Days Posts Chart Data ─────────────────────────────────
$chart_data = $db->query("
    SELECT DATE(created_at) as day, COUNT(*) as total
    FROM posts
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('D d', strtotime($d));
    $chart_values[] = (int)($chart_data[$d] ?? 0);
}

$max_chart = max($chart_values) ?: 1;

// current user display name
$display_name = $user['name'] ?: $user['username'];
$today = date('l, F j, Y');
?>

<!-- Welcome Banner -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            Good <?= (date('H') < 12) ? 'Morning' : ((date('H') < 17) ? 'Afternoon' : 'Evening') ?>, <?= htmlspecialchars($display_name) ?> 👋
        </h1>
        <p class="text-slate-400 mt-1 text-sm"><i class="fa-regular fa-calendar mr-1"></i><?= $today ?></p>
    </div>
    <a href="create_post.php" class="bg-brand hover:bg-brandDark text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 shadow-sm transition-colors shrink-0">
        <i class="fa-solid fa-pen-nib"></i> New Blog Post
    </a>
</div>

<!-- ── ROW 1: Key Stat Cards ───────────────────────────────────── -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <?php
    $stat_cards = [
        ['label'=>'Total Posts',    'value'=>$total_posts,       'icon'=>'fa-file-lines',   'color'=>'bg-indigo-50 text-indigo-600'],
        ['label'=>'Published',      'value'=>$published_posts,   'icon'=>'fa-check-circle', 'color'=>'bg-emerald-50 text-emerald-600'],
        ['label'=>'Drafts',         'value'=>$draft_posts,       'icon'=>'fa-pencil',       'color'=>'bg-amber-50 text-amber-600'],
        ['label'=>'Categories',     'value'=>$total_categories,  'icon'=>'fa-tags',         'color'=>'bg-sky-50 text-sky-600'],
        ['label'=>'Total Likes',    'value'=>$total_likes,       'icon'=>'fa-thumbs-up',    'color'=>'bg-pink-50 text-pink-600'],
        ['label'=>'Total Shares',   'value'=>$total_shares,      'icon'=>'fa-share-nodes',  'color'=>'bg-purple-50 text-purple-600'],
    ];
    foreach ($stat_cards as $c): ?>
    <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col shadow-sm hover:shadow-md transition-shadow">
        <div class="<?= $c['color'] ?> w-10 h-10 rounded-lg flex items-center justify-center mb-3">
            <i class="fa-solid <?= $c['icon'] ?>"></i>
        </div>
        <div class="text-2xl font-extrabold text-slate-900 leading-none"><?= number_format($c['value']) ?></div>
        <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mt-1"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── ROW 2: 3 more stats ────────────────────────────────────── -->
<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
    <!-- Comments overview -->
    <div class="xl:col-span-2 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Comments Overview</p>
        <div class="flex items-end gap-6">
            <div>
                <div class="text-3xl font-extrabold text-slate-900"><?= number_format($total_comments) ?></div>
                <div class="text-sm text-slate-400 mt-0.5">Total Comments</div>
            </div>
            <div class="flex flex-col gap-2 flex-1">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-emerald-600 font-semibold">Approved</span>
                        <span class="font-bold"><?= $approved_comments ?></span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-400 rounded-full" style="width:<?= $total_comments > 0 ? round(($approved_comments/$total_comments)*100) : 0 ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-amber-500 font-semibold">Pending</span>
                        <span class="font-bold"><?= $pending_comments ?></span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-400 rounded-full" style="width:<?= $total_comments > 0 ? round(($pending_comments/$total_comments)*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Stats -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Team</p>
        <div class="flex justify-around items-center mb-4">
            <div class="text-center">
                <div class="text-2xl font-extrabold text-brand"><?= $total_admins ?></div>
                <div class="text-xs text-slate-400 mt-0.5">Admins</div>
            </div>
            <div class="w-px h-8 bg-slate-100"></div>
            <div class="text-center">
                <div class="text-2xl font-extrabold text-purple-500"><?= $total_authors ?></div>
                <div class="text-xs text-slate-400 mt-0.5">Authors</div>
            </div>
        </div>
        <!-- Online Players Widget -->
        <div class="pt-3 border-t border-slate-50">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                Online Team
            </p>
            <div class="flex -space-x-2 overflow-hidden">
                <?php
                $online_users = $db->query("
                    SELECT name, username, avatar FROM users 
                    WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                    ORDER BY last_activity DESC LIMIT 6
                ")->fetchAll();
                
                if (empty($online_users)): ?>
                    <span class="text-[10px] text-slate-300 italic">No one online</span>
                <?php else: foreach ($online_users as $ou): 
                    $ou_av = !empty($ou['avatar']) 
                        ? BASE_URL . $ou['avatar'] 
                        : 'https://ui-avatars.com/api/?name=' . urlencode($ou['name'] ?: $ou['username']) . '&background=6366f1&color=fff&size=24';
                ?>
                    <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white object-cover" 
                         src="<?= $ou_av ?>" 
                         title="<?= htmlspecialchars($ou['name'] ?: $ou['username']) ?> is active">
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- This Month -->
    <div class="bg-gradient-to-br from-brand to-indigo-400 text-white border border-indigo-300 rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200 mb-2">This Month</p>
        <div class="text-4xl font-extrabold leading-none"><?= $posts_this_month ?></div>
        <div class="text-indigo-100 text-sm mt-1">Posts published in <?= date('F') ?></div>
    </div>

    <!-- Publication Rate -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Publication Rate</p>
        <?php $pub_rate = $total_posts > 0 ? round(($published_posts / $total_posts) * 100) : 0; ?>
        <div class="flex items-center justify-center my-3">
            <div class="relative w-20 h-20">
                <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#6366f1" stroke-width="3"
                        stroke-dasharray="<?= $pub_rate ?> <?= 100-$pub_rate ?>"
                        stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center text-lg font-extrabold text-brand">
                    <?= $pub_rate ?>%
                </div>
            </div>
        </div>
        <p class="text-xs text-center text-slate-400"><?= $published_posts ?>/<?= $total_posts ?> published</p>
    </div>
</div>

<!-- ── ROW 3: 7-Day Activity Chart ────────────────────────────── -->
<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-bold text-slate-800 text-lg">Posts Activity</h2>
            <p class="text-slate-400 text-sm">Last 7 days publishing activity</p>
        </div>
        <span class="bg-brandLight text-brand text-xs font-bold px-3 py-1 rounded-full">7 Days</span>
    </div>
    <div class="flex items-end gap-2 h-28">
        <?php foreach ($chart_values as $i => $val): ?>
        <div class="flex-1 flex flex-col items-center gap-1">
            <span class="text-xs text-slate-400 font-medium"><?= $val ?: '' ?></span>
            <div class="w-full rounded-t-md transition-all hover:opacity-80 <?= $val > 0 ? 'bg-brand' : 'bg-slate-100' ?>"
                 style="height:<?= $max_chart > 0 ? round(($val/$max_chart)*96) : 4 ?>px; min-height:4px;"
                 title="<?= $chart_labels[$i] ?>: <?= $val ?> posts"></div>
            <span class="text-[10px] text-slate-400 whitespace-nowrap"><?= $chart_labels[$i] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── NEW: Global Activity Log ──────────────────────────────── -->
<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-800 text-lg"><i class="fa-solid fa-bolt text-amber-400 mr-2"></i>Global Activity Log</h2>
        <a href="notifications.php" class="text-xs font-bold text-brand hover:underline">Full Log →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $global_notifs = $db->query("
            SELECT * FROM notifications 
            WHERE scope = 'global' 
            ORDER BY created_at DESC LIMIT 6
        ")->fetchAll();
        
        if (empty($global_notifs)): ?>
            <div class="col-span-full py-4 text-center text-slate-400 text-sm italic">No recent activity recorded.</div>
        <?php else: foreach ($global_notifs as $gn):
            $ico = $notif_icon_map[$gn['type']] ?? ['fa-circle-info', 'text-slate-400'];
        ?>
        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 flex gap-3 hover:border-brandLight transition-colors">
            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center flex-shrink-0 shadow-sm border border-slate-100">
                <i class="fa-solid <?= $ico[0] ?> <?= $ico[1] ?> text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-slate-700 font-medium line-clamp-1 leading-tight"><?= htmlspecialchars($gn['message']) ?></p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                        <?= $gn['actor_name'] ?: 'System' ?>
                    </span>
                    <span class="text-[10px] text-slate-300">•</span>
                    <span class="text-[10px] text-slate-400 italic">
                        <?= date('H:i', strtotime($gn['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ── ROW 4: Top Performers ──────────────────────────────────── -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

    <!-- Top Liked Post -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3"><i class="fa-solid fa-fire text-pink-400 mr-1"></i>Most Liked</p>
        <?php if ($top_liked && $top_liked['total'] > 0): ?>
            <p class="font-semibold text-slate-800 text-sm leading-snug mb-2"><?= htmlspecialchars($top_liked['title']) ?></p>
            <span class="text-2xl font-extrabold text-pink-500"><?= $top_liked['total'] ?></span>
            <span class="text-xs text-slate-400 ml-1">likes</span>
        <?php else: ?>
            <p class="text-slate-400 text-sm">No likes yet.</p>
        <?php endif; ?>
    </div>

    <!-- Top Shared Post -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3"><i class="fa-solid fa-share-nodes text-purple-400 mr-1"></i>Most Shared</p>
        <?php if ($top_shared && $top_shared['total'] > 0): ?>
            <p class="font-semibold text-slate-800 text-sm leading-snug mb-2"><?= htmlspecialchars($top_shared['title']) ?></p>
            <span class="text-2xl font-extrabold text-purple-500"><?= $top_shared['total'] ?></span>
            <span class="text-xs text-slate-400 ml-1">shares</span>
        <?php else: ?>
            <p class="text-slate-400 text-sm">No shares yet.</p>
        <?php endif; ?>
    </div>

    <!-- Top Commented Post -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3"><i class="fa-solid fa-comments text-sky-400 mr-1"></i>Most Discussed</p>
        <?php if ($top_commented && $top_commented['total'] > 0): ?>
            <p class="font-semibold text-slate-800 text-sm leading-snug mb-2"><?= htmlspecialchars($top_commented['title']) ?></p>
            <span class="text-2xl font-extrabold text-sky-500"><?= $top_commented['total'] ?></span>
            <span class="text-xs text-slate-400 ml-1">comments</span>
        <?php else: ?>
            <p class="text-slate-400 text-sm">No comments yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ── ROW 5: Posts by Category + Top Authors ─────────────────── -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

    <!-- Posts per Category -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <h2 class="font-bold text-slate-800 text-base mb-4"><i class="fa-solid fa-tags text-slate-400 mr-2"></i>Posts by Category</h2>
        <?php if (empty($cat_stats)): ?>
            <p class="text-slate-400 text-sm">No categories yet.</p>
        <?php else: ?>
            <div class="space-y-3">
            <?php
            $cat_max = max(array_column($cat_stats, 'total')) ?: 1;
            $cat_colors = ['bg-brand','bg-emerald-400','bg-amber-400','bg-sky-400','bg-pink-400','bg-purple-400'];
            foreach ($cat_stats as $i => $cs):
                $pct = round(($cs['total'] / $cat_max) * 100);
            ?>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($cs['name']) ?></span>
                    <span class="text-slate-500 font-bold"><?= $cs['total'] ?></span>
                </div>
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full <?= $cat_colors[$i % count($cat_colors)] ?>" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top Authors -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <h2 class="font-bold text-slate-800 text-base mb-4"><i class="fa-solid fa-trophy text-amber-400 mr-2"></i>Top Authors</h2>
        <?php if (empty($top_authors)): ?>
            <p class="text-slate-400 text-sm">No authors yet.</p>
        <?php else: ?>
            <div class="space-y-3">
            <?php foreach ($top_authors as $rank => $a):
                $av = !empty($a['avatar'])
                    ? BASE_URL . htmlspecialchars($a['avatar'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($a['name'] ?: $a['username']) . '&background=6366f1&color=fff&size=36&bold=true';
            ?>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-300 w-4 text-center"><?= $rank+1 ?></span>
                <img src="<?= $av ?>" class="w-9 h-9 rounded-full object-cover border border-brandLight flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($a['name'] ?: $a['username']) ?></p>
                    <p class="text-xs text-slate-400"><?= $a['total'] ?> post<?= $a['total'] != 1 ? 's' : '' ?></p>
                </div>
                <?php if ($rank === 0): ?>
                <i class="fa-solid fa-crown text-amber-400 text-xs"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── ROW 6: Recent Posts + Recent Comments ──────────────────── -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    <!-- Recent Posts -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="bg-slate-50 px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-bold text-slate-800"><i class="fa-regular fa-clock text-slate-400 mr-2"></i>Recent Posts</h2>
            <a href="posts.php" class="text-xs text-brand font-semibold hover:underline">View All →</a>
        </div>
        <ul class="divide-y divide-slate-50">
            <?php if (empty($recent_posts)): ?>
                <li class="p-6 text-slate-400 text-sm text-center">No posts yet.</li>
            <?php else: foreach ($recent_posts as $p): ?>
            <li class="px-5 py-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate"><?= htmlspecialchars($p['title']) ?></p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span class="mr-2"><i class="fa-solid fa-user-pen opacity-60 mr-0.5"></i><?= htmlspecialchars($p['author'] ?? 'Unknown') ?></span>
                        <span><i class="fa-regular fa-calendar opacity-60 mr-0.5"></i><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                    </p>
                </div>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 <?= $p['status'] === 'published' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' ?>">
                    <?= ucfirst($p['status']) ?>
                </span>
            </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>

    <!-- Recent Comments -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="bg-slate-50 px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-bold text-slate-800"><i class="fa-solid fa-comments text-slate-400 mr-2"></i>Recent Comments</h2>
            <span class="text-xs bg-amber-50 text-amber-600 border border-amber-200 font-bold px-2 py-0.5 rounded-full"><?= $pending_comments ?> pending</span>
        </div>
        <ul class="divide-y divide-slate-50">
            <?php if (empty($recent_comments)): ?>
                <li class="p-6 text-slate-400 text-sm text-center">No comments yet.</li>
            <?php else: foreach ($recent_comments as $c): ?>
            <li class="px-5 py-3.5 hover:bg-slate-50 transition-colors">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <p class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['name']) ?></p>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full shrink-0 <?= $c['status'] === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' ?>">
                        <?= ucfirst($c['status']) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 truncate mb-1"><?= htmlspecialchars($c['content']) ?></p>
                <p class="text-[10px] text-slate-400"><i class="fa-regular fa-file-lines mr-0.5"></i><?= htmlspecialchars($c['post_title'] ?? '') ?></p>
            </li>
            <?php endforeach; endif; ?>
        </ul>
    </div>
</div>

<!-- ── Quick Actions ─────────────────────────────────────────── -->
<div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php $qa = [
        ['href'=>'create_post.php',  'label'=>'New Post',       'icon'=>'fa-pen-nib',       'color'=>'bg-brand text-white hover:bg-brandDark'],
        ['href'=>'posts.php',        'label'=>'All Posts',      'icon'=>'fa-table-list',    'color'=>'bg-white border-slate-200 text-slate-700 hover:border-brand hover:text-brand border'],
        ['href'=>'categories.php',   'label'=>'Categories',     'icon'=>'fa-tags',          'color'=>'bg-white border-slate-200 text-slate-700 hover:border-brand hover:text-brand border'],
        ['href'=>'authors.php',      'label'=>'Authors',        'icon'=>'fa-feather-pointed','color'=>'bg-white border-slate-200 text-slate-700 hover:border-brand hover:text-brand border'],
    ]; foreach ($qa as $a): ?>
    <a href="<?= $a['href'] ?>" class="<?= $a['color'] ?> rounded-xl p-4 flex items-center gap-3 font-semibold text-sm transition-all shadow-sm hover:shadow-md">
        <i class="fa-solid <?= $a['icon'] ?>"></i> <?= $a['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
