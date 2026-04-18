<?php
require_once '../includes/admin_header.php';
require_permission('users'); // Super admin should only have this permission

$msg = '';
$err = '';

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
        $msg = "New Admin account created successfully!";
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
    $del = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
    if ($stmt->execute([$del])) {
        $msg = "Admin account deleted successfully.";
    }
}

// Fetch all users
$users = $db->query("SELECT id, name, username, email, role, created_at FROM users")->fetchAll();
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
                            <span class="bg-brandLight text-brand px-2.5 py-1 rounded-md text-xs font-bold tracking-wide">
                                <?= strtoupper($u['role']) ?>
                            </span>
                            <?php if ($u['id'] != 1): ?>
                                <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Are you sure you want to delete this admin account permanently?')" title="Delete Admin" class="text-red-400 hover:text-red-600 mt-1"><i class="fa-solid fa-trash fa-lg"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($u['role'] !== 'super_admin'): ?>
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
