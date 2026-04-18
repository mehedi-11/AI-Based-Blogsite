<?php
require_once '../includes/admin_header.php';
require_permission('posts');

$msg = '';

if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$del_id])) {
        $msg = "Post deleted successfully.";
    }
}

// Fetch all posts for admin view
$stmt = $db->query("
    SELECT p.id, p.title, p.created_at, p.status, c.name as category_name, u.username as author_name
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-table-list text-brand mr-2"></i> Manage Posts</h1>
        <p class="text-slate-500 mt-1">View, edit, or delete the articles in your system.</p>
    </div>
    <a href="create_post.php" class="bg-brand hover:bg-brandDark text-white font-bold py-2.5 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm shrink-0">
        <i class="fa-solid fa-plus"></i> Create New Post
    </a>
</div>

<?php if ($msg): ?>
    <div class="bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <?php if (count($posts) === 0): ?>
        <div class="text-center py-16">
            <i class="fa-solid fa-file-circle-question text-slate-300 fa-4x mb-4"></i>
            <p class="text-slate-500 text-lg">No posts found.</p>
            <p class="text-slate-400">Start by creating a new article.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[800px] border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-sm uppercase tracking-wider">
                        <th class="py-4 px-6 font-semibold">Title & Category</th>
                        <th class="py-4 px-6 font-semibold">Author & Stats</th>
                        <th class="py-4 px-6 font-semibold">Published</th>
                        <th class="py-4 px-6 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($posts as $post): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="py-4 px-6">
                                <strong class="text-slate-800 text-lg block mb-1 group-hover:text-brand transition-colors"><?= htmlspecialchars($post['title']) ?></strong>
                                <span class="bg-indigo-50 text-brand px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide">
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-slate-700 font-medium mb-1 flex items-center gap-1.5"><i class="fa-solid fa-user-pen text-slate-400 text-sm"></i> <?= htmlspecialchars($post['author_name']) ?></div>
                                <div class="text-xs text-slate-500 flex gap-3">
                                    <?php
                                        $stmtLog = $db->prepare("SELECT 
                                            (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) as likes,
                                            (SELECT COUNT(*) FROM post_shares WHERE post_id = ?) as shares,
                                            (SELECT COUNT(*) FROM comments WHERE post_id = ?) as comments
                                        ");
                                        $stmtLog->execute([$post['id'], $post['id'], $post['id']]);
                                        $metrics = $stmtLog->fetch();
                                    ?>
                                    <span title="Likes" class="flex items-center gap-1"><i class="fa-solid fa-thumbs-up text-blue-400"></i> <?= $metrics['likes'] ?></span>
                                    <span title="Comments" class="flex items-center gap-1"><i class="fa-solid fa-comment text-emerald-400"></i> <?= $metrics['comments'] ?></span>
                                    <span title="Shares" class="flex items-center gap-1"><i class="fa-solid fa-share-nodes text-purple-400"></i> <?= $metrics['shares'] ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-500">
                                <?= date('M j, Y', strtotime($post['created_at'])) ?>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <a href="../post.php?slug=<?= urlencode(strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $post['title'])))) ?>" target="_blank" class="text-slate-400 hover:text-brand bg-white border border-slate-200 hover:border-brand px-3 py-2 rounded-lg transition-colors inline-block mr-2 shadow-sm" title="View"><i class="fa-solid fa-eye"></i></a>
                                <a href="edit_post.php?id=<?= $post['id'] ?>" class="text-blue-500 hover:text-white bg-white border border-blue-200 hover:bg-blue-600 hover:border-blue-600 px-3 py-2 rounded-lg transition-all shadow-sm inline-block mr-2" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="?delete=<?= $post['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this post?');" class="text-red-400 hover:text-white bg-white border border-red-200 hover:bg-red-500 hover:border-red-500 px-3 py-2 rounded-lg transition-all shadow-sm inline-block" title="Delete"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
