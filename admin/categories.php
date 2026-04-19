<?php
require_once '../includes/admin_header.php';
require_permission('posts');

$msg = '';
$err = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name']);
    if ($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $msg = "Category successfully created.";
            log_notification('category_added', "New category created: $name");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $err = "That category name already exists.";
            } else {
                $err = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    try {
        $db->beginTransaction();
        // Nullify foreign keys safely
        $stmtUpdate = $db->prepare("UPDATE posts SET category_id = NULL WHERE category_id = ?");
        $stmtUpdate->execute([$del]);
        
        $stmtDelete = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmtDelete->execute([$del]);
        
        $db->commit();
        $msg = "Category deleted. Any associated posts are now 'Uncategorized'.";
        log_notification('category_deleted', "Category ID #$del was deleted. Associated posts uncategorized.");
    } catch (PDOException $e) {
        $db->rollBack();
        $err = "Deletion failed: " . $e->getMessage();
    }
}

// Fetch Categories and their usage counts
$catsQuery = $db->query("
    SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$categories = $catsQuery->fetchAll();
?>

<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-tags text-brand mr-2"></i> Categories</h1>
        <p class="text-slate-500 mt-1">Organize your blog into clear structural taxonomies.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="bg-red-50 text-red-600 border border-red-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Add New Category -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 h-fit">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 rounded-t-xl">
                <h2 class="text-lg font-bold text-slate-800">Add New Category</h2>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="add_category">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Category Name</label>
                    <input type="text" name="name" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" placeholder="e.g. Artificial Intelligence" required>
                </div>
                <button type="submit" class="w-full bg-brand hover:bg-brandDark text-white font-bold py-3.5 px-4 rounded-xl transition-colors shadow-sm flex justify-center items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Save Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[500px] border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-sm uppercase tracking-wider">
                            <th class="py-4 px-6 font-semibold">Name</th>
                            <th class="py-4 px-6 font-semibold">Slug Base</th>
                            <th class="py-4 px-6 font-semibold">Usage count</th>
                            <th class="py-4 px-6 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($categories) === 0): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">
                                    <i class="fa-solid fa-folder-open text-slate-300 fa-3x mb-3 block"></i>
                                    No categories generated yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($categories as $cat): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <strong class="text-slate-800 text-lg"><?= htmlspecialchars($cat['name']) ?></strong>
                                    </td>
                                    <td class="py-4 px-6 text-slate-500">
                                        <code><?= htmlspecialchars($cat['slug']) ?></code>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-sm font-semibold">
                                            <?= $cat['post_count'] ?> Posts
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <a href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Delete this category? Any posts using it will become Uncategorized.');" class="text-red-400 hover:text-white bg-white border border-red-200 hover:bg-red-500 hover:border-red-500 px-3 py-2 rounded-lg transition-all shadow-sm flex items-center justify-center w-min ml-auto" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<?php require_once '../includes/admin_footer.php'; ?>
