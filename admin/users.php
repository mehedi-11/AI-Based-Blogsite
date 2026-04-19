<?php
require_once '../includes/admin_header.php';
require_permission('users'); // Super admin should only have this permission

$msg = '';
$err = '';

// ── Handle Suspend / Unsuspend ────────────────────────────────────
if ($user['role'] === 'super_admin' && isset($_POST['toggle_suspend'])) {
    $tid    = (int)$_POST['target_id'];
    $action = $_POST['toggle_suspend'];
    $reason = trim($_POST['reason'] ?? 'Suspended by Super Admin.');
    if ($tid != $_SESSION['user_id']) {
        if ($action === 'suspend') {
            $db->prepare("UPDATE users SET status='suspended', suspended_reason=? WHERE id=?")->execute([$reason, $tid]);
            $msg = "Account suspended.";
            log_notification('user_suspended', "User ID #$tid was suspended. Reason: $reason");
        } else {
            $db->prepare("UPDATE users SET status='active', login_attempts=0, suspended_reason=NULL WHERE id=?")->execute([$tid]);
            $msg = "Account restored.";
            log_notification('user_unsuspended', "User ID #$tid access has been restored.");
        }
    }
}

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $permissions = $_POST['permissions'] ?? [];

    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([$name, $username, $email, $password]);
        
        $new_user_id = $db->lastInsertId();

        if (is_array($permissions)) {
            $p_stmt = $db->prepare("INSERT INTO admin_permissions (user_id, module) VALUES (?, ?)");
            foreach($permissions as $mod) {
                $p_stmt->execute([$new_user_id, $mod]);
            }
        }
        
        $db->commit();
        $msg = "Administrator <strong>" . htmlspecialchars($name) . "</strong> created successfully!";
        log_notification('user_added', "New administrator added: $name");
    } catch(PDOException $e) {
        $db->rollBack();
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $err = "Username or Email already exists.";
        } else {
            $err = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_GET['delete'] != 1) {
    $del_id = (int)$_GET['delete'];
    try {
        $db->prepare("DELETE FROM users WHERE id = ? AND id != 1")->execute([$del_id]);
        $msg = "User permanently deleted.";
        log_notification('user_deleted', "User ID #$del_id was permanently deleted from the system.");
    } catch (PDOException $e) {
        $err = "Error deleting user.";
    }
}

// Fetch all users — include status
$users = $db->query("SELECT id, name, username, email, role, status, suspended_reason, created_at FROM users")->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-users text-brand mr-2"></i> Admin Managers</h1>
    <p class="text-slate-500 mt-1">Super Admin Panel: Assign localized access to new admins.</p>
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- List Users -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-800">Existing Admins</h2>
        </div>
        <ul class="divide-y divide-slate-100 p-0 m-0 text-sm">
            <?php foreach($users as $u): ?>
                <li class="p-6 hover:bg-slate-50 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <strong class="text-lg text-slate-800 block leading-tight"><?= htmlspecialchars($u['name'] ?? $u['username']) ?></strong>
                            <div class="text-slate-500 mt-1">@<?= htmlspecialchars($u['username']) ?> &bull; <?= htmlspecialchars($u['email']) ?></div>
                        </div>
                        <div class="flex flex-col items-end gap-2 shrink-0">
                            <!-- Role + Status badges -->
                            <div class="flex gap-1.5 flex-wrap justify-end">
                                <span class="bg-brandLight text-brand px-2.5 py-1 rounded-md text-xs font-bold tracking-wide"><?= strtoupper($u['role']) ?></span>
                                <?php if ($u['status'] === 'suspended'): ?>
                                    <span class="bg-red-50 text-red-600 border border-red-200 px-2 py-1 rounded-md text-xs font-bold">SUSPENDED</span>
                                <?php else: ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-1 rounded-md text-xs font-bold">ACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="edit_user.php?id=<?= $u['id'] ?>" title="Edit User"
                                   class="text-slate-400 hover:text-brand transition-colors">
                                    <i class="fa-solid fa-pen-to-square fa-lg"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($u['status'] === 'suspended'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="toggle_suspend" value="unsuspend">
                                            <button type="submit" title="Restore Account"
                                                    class="text-emerald-500 hover:text-emerald-700 transition-colors">
                                                <i class="fa-solid fa-lock-open fa-lg"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" title="Suspend Account"
                                                onclick="openSuspendModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'] ?: $u['username'])) ?>')"
                                                class="text-red-400 hover:text-red-600 transition-colors">
                                            <i class="fa-solid fa-ban fa-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Permanently delete this account?')" title="Delete"
                                       class="text-slate-300 hover:text-red-500 transition-colors">
                                        <i class="fa-solid fa-trash fa-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($u['status'] === 'suspended'): ?>
                        <div class="mt-3 pt-3 border-t border-red-50">
                            <span class="text-xs font-bold text-red-500 uppercase tracking-wider block mb-1">Suspension Reason</span>
                            <p class="text-sm text-red-600 bg-red-50 p-2 rounded-lg border border-red-100 italic">
                                "<?= htmlspecialchars($u['suspended_reason'] ?: 'No reason specified.') ?>"
                            </p>
                        </div>
                    <?php elseif ($u['role'] !== 'super_admin'): ?>
                        <?php 
                            $stmt = $db->prepare("SELECT module FROM admin_permissions WHERE user_id = ?");
                            $stmt->execute([$u['id']]);
                            $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        ?>
                        <div class="text-slate-400 mt-3 pt-3 border-t border-slate-50">
                            <span class="font-semibold text-slate-500">Access:</span> <?= count($perms) > 0 ? implode(', ', $perms) : 'None' ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Create User -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 h-fit">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 rounded-t-xl">
            <h2 class="text-lg font-bold text-slate-800">Create New Admin</h2>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="create_admin">
            
            <div class="mb-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">Full Name</label>
                <input type="text" name="name" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                <input type="password" name="password" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required>
            </div>
            
            <div class="mb-8">
                <label class="block text-sm font-bold text-slate-700 mb-2">Assign Modules Access</label>
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg flex flex-col gap-3">
                    <label class="flex items-center gap-3 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="dashboard" class="w-4 h-4 text-brand rounded focus:ring-brand">
                        Dashboard Overview
                    </label>
                    <label class="flex items-center gap-3 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="ai_writer" class="w-4 h-4 text-brand rounded focus:ring-brand">
                        AI Content Writer 
                    </label>
                    <label class="flex items-center gap-3 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="posts" class="w-4 h-4 text-brand rounded focus:ring-brand">
                        Manage Posts Data
                    </label>
                    <label class="flex items-center gap-3 text-slate-700 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="settings" class="w-4 h-4 text-brand rounded focus:ring-brand">
                        Site Settings 
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-brand hover:bg-brandDark text-white font-bold py-3.5 px-4 rounded-xl transition-colors shadow-sm flex justify-center items-center gap-2">
                <i class="fa-solid fa-user-plus"></i> Create Account
            </button>
        </form>
    </div>
</div>

<?php 
require_once '../includes/admin_footer.php'; 
?>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1"><i class="fa-solid fa-ban text-red-500 mr-2"></i>Suspend Account</h3>
        <p class="text-slate-500 text-sm mb-4">Suspending <strong id="suspendTargetName"></strong>. They will be immediately logged out.</p>
        <form method="POST" id="suspendForm">
            <input type="hidden" name="target_id" id="suspendTargetId">
            <input type="hidden" name="toggle_suspend" value="suspend">
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Reason <span class="text-slate-400 font-normal">(shown to user)</span></label>
                <textarea name="reason" rows="3" placeholder="e.g. Violation of content policy..."
                          class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-400 resize-none text-sm"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeSuspendModal()"
                        class="flex-1 border border-slate-300 text-slate-600 font-semibold py-2.5 rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 rounded-xl transition-colors">
                    <i class="fa-solid fa-ban mr-1"></i> Suspend
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSuspendModal(id, name) {
    document.getElementById('suspendTargetId').value = id;
    document.getElementById('suspendTargetName').textContent = name;
    document.getElementById('suspendModal').classList.remove('hidden');
}
function closeSuspendModal() {
    document.getElementById('suspendModal').classList.add('hidden');
}
document.getElementById('suspendModal').addEventListener('click', function(e) {
    if (e.target === this) closeSuspendModal();
});
</script>
