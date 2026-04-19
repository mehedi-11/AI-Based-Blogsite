<?php
require_once '../includes/admin_header.php';
require_permission('posts'); // Authors panel tied to posts permission

$msg = '';
$err = '';

// ── Handle Add Author ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_author') {
    $name        = trim($_POST['name']     ?? '');
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email']    ?? '');
    $bio         = trim($_POST['bio']      ?? '');
    $social_link = trim($_POST['social']   ?? '');
    $raw_pw      = $_POST['password']      ?? '';

    if (!$name || !$username || !$email || !$raw_pw) {
        $err = 'Name, Username, Email and Password are required.';
    } else {
        $password = password_hash($raw_pw, PASSWORD_DEFAULT);

        // Handle avatar upload
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $up_dir = '../assets/uploads/avatars/';
            if (!is_dir($up_dir)) mkdir($up_dir, 0755, true);
            $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['avatar']['name']));
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $up_dir . $fname)) {
                $avatar = 'assets/uploads/avatars/' . $fname;
            }
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO users (name, username, email, password, role, bio, social_link, avatar)
                VALUES (?, ?, ?, ?, 'author', ?, ?, ?)
            ");
            $stmt->execute([$name, $username, $email, $password, $bio, $social_link, $avatar]);
            $new_author_id = $db->lastInsertId();

            // Auto-grant author permissions
            $p_stmt = $db->prepare("INSERT INTO admin_permissions (user_id, module) VALUES (?, ?)");
            foreach (['dashboard', 'ai_writer', 'posts', 'categories'] as $mod) {
                $p_stmt->execute([$new_author_id, $mod]);
            }

            $db->commit();
            $msg = "Author <strong>" . htmlspecialchars($name) . "</strong> added with default permissions!";
            log_notification('user_added', "New author added: $name");
        } catch (PDOException $e) {
            $db->rollBack();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $err = 'Username or Email is already taken.';
            } else {
                // Columns might not exist yet — run migration gracefully
                $err = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// ── Handle Delete Author ───────────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] != 1) {
    $del_id = (int)$_GET['delete'];
    try {
        $db->prepare("UPDATE posts SET author_id = NULL WHERE author_id = ?")->execute([$del_id]);
        $db->prepare("DELETE FROM users WHERE id = ? AND role = 'author'")->execute([$del_id]);
        $msg = "Author deleted. Their posts have been unassigned.";
        log_notification('user_deleted', "Author ID #$del_id was deleted. Posts have been unassigned.");
    } catch (PDOException $e) {
        $err = "Could not delete author: " . $e->getMessage();
    }
}

// ── Handle Suspend / Unsuspend ────────────────────────────────────────────────
if ($user['role'] === 'super_admin' && isset($_POST['toggle_suspend'])) {
    $tid    = (int)$_POST['target_id'];
    $action = $_POST['toggle_suspend'];
    $reason = trim($_POST['reason'] ?? 'Suspended by Super Admin.');
    if ($action === 'suspend' && $tid != $_SESSION['user_id']) {
        $db->prepare("UPDATE users SET status='suspended', suspended_reason=? WHERE id=?")->execute([$reason, $tid]);
        $msg = "Author suspended successfully.";
        log_notification('user_suspended', "Author ID #$tid was suspended. Reason: $reason");
    } elseif ($action === 'unsuspend') {
        $db->prepare("UPDATE users SET status='active', login_attempts=0, suspended_reason=NULL WHERE id=?")->execute([$tid]);
        $msg = "Author account restored successfully.";
        log_notification('user_unsuspended', "Author ID #$tid access restored.");
    }
}

// ── Fetch all authors ──────────────────────────────────────────────────────────
$authors = $db->query("
    SELECT u.*,
           COUNT(p.id) AS post_count
    FROM users u
    LEFT JOIN posts p ON p.author_id = u.id
    WHERE u.role = 'author'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-feather-pointed text-brand mr-2"></i> Author Management</h1>
    <p class="text-slate-500 mt-1">Add and manage blog authors. Authors can be assigned to posts.</p>
</div>

<?php if ($msg): ?>
    <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= $msg ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="bg-red-50 text-red-600 border border-red-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-8">

    <!-- ── Authors List ────────────────────────────────────────────────── -->
    <div class="xl:col-span-3 space-y-4">
        <h2 class="text-lg font-bold text-slate-800 mb-2"><i class="fa-solid fa-users mr-1 text-brand"></i> Current Authors (<?= count($authors) ?>)</h2>

        <?php if (empty($authors)): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-400">
                <i class="fa-solid fa-user-slash fa-3x mb-4 block"></i>
                <p>No authors yet. Add your first author using the form.</p>
            </div>
        <?php else: ?>
            <?php foreach ($authors as $a): ?>
                <?php
                    $avatarSrc = !empty($a['avatar'])
                        ? BASE_URL . htmlspecialchars($a['avatar'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($a['name']) . '&background=6366f1&color=fff&bold=true&size=80';
                ?>
                <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-start gap-4 hover:shadow-md transition-shadow group">
                    <img src="<?= $avatarSrc ?>" alt="<?= htmlspecialchars($a['name']) ?>"
                         class="w-16 h-16 rounded-full object-cover border-2 border-brandLight flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-slate-900 text-lg leading-tight"><?= htmlspecialchars($a['name']) ?></p>
                                <p class="text-slate-500 text-sm">@<?= htmlspecialchars($a['username']) ?> &bull; <?= htmlspecialchars($a['email']) ?></p>
                            </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="bg-brandLight text-brand text-xs font-bold px-2.5 py-1 rounded-lg">
                                    <?= $a['post_count'] ?> Post<?= $a['post_count'] != 1 ? 's' : '' ?>
                                </span>
                                <?php if ($a['status'] === 'suspended'): ?>
                                    <span class="bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded-md text-xs font-bold">SUSPENDED</span>
                                <?php endif; ?>
                                <a href="edit_user.php?id=<?= $a['id'] ?>" title="Edit Author"
                                   class="text-slate-400 hover:text-brand opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-slate-100">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <?php if ($user['role'] === 'super_admin'): ?>
                                    <?php if ($a['status'] === 'suspended'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="target_id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="toggle_suspend" value="unsuspend">
                                            <button type="submit" title="Restore Account"
                                                    class="text-emerald-500 hover:text-emerald-700 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-emerald-50">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" title="Suspend Author"
                                                onclick="openSuspendModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['name'] ?: $a['username'])) ?>')"
                                                class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-red-50">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="?delete=<?= $a['id'] ?>"
                                   onclick="return confirm('Delete author <?= htmlspecialchars(addslashes($a['name'])) ?>? Their posts will be unassigned.')"
                                   class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-red-50"
                                   title="Delete Author">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <?php if ($a['status'] === 'suspended'): ?>
                            <div class="mt-2 text-red-600 bg-red-50 p-2 rounded-lg border border-red-100 text-xs italic">
                                <strong>Suspension Reason:</strong> "<?= htmlspecialchars($a['suspended_reason'] ?: 'No reason provided.') ?>"
                            </div>
                        <?php else: ?>
                            <?php if (!empty($a['bio'])): ?>
                                <p class="text-slate-600 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($a['bio']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($a['social_link'])): ?>
                                <a href="<?= htmlspecialchars($a['social_link']) ?>" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-xs text-brand hover:underline mt-2">
                                    <i class="fa-solid fa-link"></i> Social Profile
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <p class="text-slate-400 text-xs mt-2"><i class="fa-regular fa-calendar"></i> Joined <?= date('M j, Y', strtotime($a['created_at'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Add Author Form ─────────────────────────────────────────────── -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm sticky top-6">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 rounded-t-xl">
                <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-user-plus mr-1 text-brand"></i> Add New Author</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                <input type="hidden" name="action" value="create_author">

                <!-- Avatar Upload -->
                <div class="flex flex-col items-center gap-3">
                    <div id="avatarPreviewWrapper" class="w-24 h-24 rounded-full bg-brandLight border-2 border-dashed border-brandLight overflow-hidden flex items-center justify-center">
                        <i class="fa-solid fa-user fa-2x text-brand" id="avatarIcon"></i>
                        <img id="avatarPreviewImg" src="" alt="" class="w-full h-full object-cover hidden">
                    </div>
                    <label class="cursor-pointer text-sm font-semibold text-brand hover:underline">
                        <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload Avatar
                        <input type="file" name="avatar" accept="image/*" class="hidden" id="avatarInput">
                    </label>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Jane Doe"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors">
                </div>

                <!-- Username & Email -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Username <span class="text-red-400">*</span></label>
                        <input type="text" name="username" required placeholder="janedoe"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Email <span class="text-red-400">*</span></label>
                        <input type="email" name="email" required placeholder="jane@example.com"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Password <span class="text-red-400">*</span></label>
                    <input type="password" name="password" required placeholder="Set a strong password"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                </div>

                <!-- Bio -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Short Bio <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea name="bio" rows="3" placeholder="A brief description about the author..."
                              class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors resize-none"></textarea>
                </div>

                <!-- Social Link -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Social Profile URL <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="url" name="social" placeholder="https://twitter.com/janedoe"
                           class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                </div>

                <button type="submit" class="w-full bg-brand hover:bg-brandDark text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-sm flex justify-center items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Add Author
                </button>
            </form>
        </div>
    </div>

</div>

<script>
// Avatar live preview
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('avatarPreviewImg');
        const icon = document.getElementById('avatarIcon');
        img.src = e.target.result;
        img.classList.remove('hidden');
        icon.classList.add('hidden');
    };
    reader.readAsDataURL(file);
});

function openSuspendModal(id, name) {
    document.getElementById('suspendTargetId').value = id;
    document.getElementById('suspendTargetName').textContent = name;
    document.getElementById('suspendModal').classList.remove('hidden');
}
function closeSuspendModal() {
    document.getElementById('suspendModal').classList.add('hidden');
}
</script>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1"><i class="fa-solid fa-ban text-red-500 mr-2"></i>Suspend Author</h3>
        <p class="text-slate-500 text-sm mb-4">Suspending <strong id="suspendTargetName"></strong>. They will be locked out immediately.</p>
        <form method="POST" id="suspendForm">
            <input type="hidden" name="target_id" id="suspendTargetId">
            <input type="hidden" name="toggle_suspend" value="suspend">
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Reason <span class="text-slate-400 font-normal">(shown to author)</span></label>
                <textarea name="reason" rows="3" placeholder="e.g. Violation of content guidelines..."
                          class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-400 resize-none text-sm"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeSuspendModal()"
                        class="flex-1 border border-slate-300 text-slate-600 font-semibold py-2.5 rounded-xl hover:bg-slate-50">Cancel</button>
                <button type="submit"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 rounded-xl">
                    <i class="fa-solid fa-ban mr-1"></i> Suspend
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
