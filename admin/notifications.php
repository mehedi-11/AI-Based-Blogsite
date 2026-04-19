<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_login();
$user = get_current_user_details();

// Handle Mark All as Read (Must happen before any HTML output)
if (isset($_GET['mark_read'])) {
    if ($user['role'] === 'super_admin') {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (scope = 'global' OR (scope='user' AND recipient_id = ?))")
           ->execute([$user['id']]);
    } else {
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND scope = 'user' AND recipient_id = ?")
           ->execute([$user['id']]);
    }
    header("Location: notifications.php");
    exit;
}

require_once '../includes/admin_header.php';

// Stats for filters
$filter = $_GET['filter'] ?? 'all';
$uid = (int)$user['id'];

if ($user['role'] === 'super_admin') {
    $where_base = "(scope = 'global' OR (scope='user' AND recipient_id = $uid))";
} else {
    $where_base = "(scope = 'user' AND recipient_id = $uid)";
}

$where_unread = "$where_base AND is_read = 0";

if ($filter === 'unread') {
    $current_where = $where_unread;
} else {
    $current_where = $where_base;
}

// Fetch notifications
$stmt = $db->query("SELECT * FROM notifications WHERE $current_where ORDER BY created_at DESC LIMIT 50");
$notifications = $stmt->fetchAll();

$unread_count = $db->query("SELECT COUNT(*) FROM notifications WHERE $where_unread")->fetchColumn();
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-bell text-brand mr-2"></i> Notifications</h1>
        <p class="text-slate-500 mt-1">Stay updated with system activity and social interactions.</p>
    </div>
    <div class="flex items-center gap-3">
        <?php if ($unread_count > 0): ?>
        <a href="?mark_read=1" class="text-sm font-bold text-brand hover:underline p-2 rounded-lg hover:bg-indigo-50 transition-colors">
            <i class="fa-solid fa-check-double mr-1"></i> Mark all as read
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="flex gap-2 mb-6">
    <a href="?filter=all" class="px-4 py-2 rounded-xl text-sm font-bold transition-all <?= $filter === 'all' ? 'bg-brand text-white shadow-md' : 'bg-white text-slate-500 border border-slate-200 hover:border-brandLight' ?>">
        All Notifications
    </a>
    <a href="?filter=unread" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= $filter === 'unread' ? 'bg-brand text-white shadow-md' : 'bg-white text-slate-500 border border-slate-200 hover:border-brandLight' ?>">
        Unread
        <?php if ($unread_count > 0): ?>
            <span class="<?= $filter === 'unread' ? 'bg-white/20' : 'bg-red-500 text-white' ?> text-[10px] px-1.5 py-0.5 rounded-full"><?= $unread_count ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <?php if (empty($notifications)): ?>
        <div class="p-16 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                <i class="fa-solid fa-bell-slash text-slate-300 text-3xl"></i>
            </div>
            <p class="text-slate-500 font-medium">No notifications found.</p>
            <p class="text-slate-400 text-sm mt-1"><?= $filter === 'unread' ? 'You are all caught up!' : 'Check back later for updates.' ?></p>
        </div>
    <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($notifications as $n): 
                $ico = $notif_icon_map[$n['type']] ?? ['fa-circle-info', 'text-slate-400'];
            ?>
            <div class="p-5 flex items-start gap-4 hover:bg-slate-50 transition-colors <?= !$n['is_read'] ? 'bg-indigo-50/30' : '' ?>">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i class="fa-solid <?= $ico[0] ?> <?= $ico[1] ?> text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-slate-700 font-medium leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
                        <span class="text-[11px] font-bold text-slate-400 whitespace-nowrap bg-slate-100 px-2 py-1 rounded-md">
                            <?= date('M j, Y • g:i a', strtotime($n['created_at'])) ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-circle opacity-60"></i> <?= $n['actor_name'] ?: 'System' ?>
                        </span>
                        <?php if ($n['link']): ?>
                        <span class="text-slate-200">|</span>
                        <a href="<?= htmlspecialchars($n['link']) ?>" class="text-[11px] font-bold text-brand hover:underline flex items-center gap-1">
                            View Details <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!$n['is_read']): ?>
                    <div class="w-2.5 h-2.5 bg-brand rounded-full mt-2 ring-4 ring-indigo-50"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
