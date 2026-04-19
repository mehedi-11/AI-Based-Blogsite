<?php
require_once '../includes/admin_header.php';
require_permission('users'); // Super admin only

// Only super_admin can access this
if ($user['role'] !== 'super_admin') {
    die("<div style='padding:2rem;'><h2>403 Forbidden</h2><p>Only the Super Admin can edit user accounts.</p><a href='users.php'>Go Back</a></div>");
}

$target_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$target_id) die("Invalid user ID.");

// Fetch target user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$target = $stmt->fetch();
if (!$target) die("User not found.");

// Don't let super_admin edit themselves here (they use My Profile)
if ($target['id'] == $_SESSION['user_id']) {
    header("Location: profile.php");
    exit;
}

$msg = '';
$err = '';
$is_admin_role = in_array($target['role'], ['admin', 'super_admin']);
$is_author_role = $target['role'] === 'author';

// Fetch current permissions
$perms_stmt = $db->prepare("SELECT module FROM admin_permissions WHERE user_id = ?");
$perms_stmt->execute([$target_id]);
$current_perms = $perms_stmt->fetchAll(PDO::FETCH_COLUMN);

// ── Handle Save ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']     ?? '');
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email']    ?? '');
    $phone       = trim($_POST['phone']    ?? '');
    $phone       = $phone === '' ? null : $phone;
    $bio         = trim($_POST['bio']      ?? '');
    $social_link = trim($_POST['social']   ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_perms   = $_POST['permissions']   ?? [];

    // Avatar upload
    $avatar = $target['avatar'];
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

        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET name=?, username=?, email=?, phone=?, bio=?, social_link=?, avatar=?, password=? WHERE id=?")
               ->execute([$name, $username, $email, $phone, $bio, $social_link, $avatar, $hashed, $target_id]);
        } else {
            $db->prepare("UPDATE users SET name=?, username=?, email=?, phone=?, bio=?, social_link=?, avatar=? WHERE id=?")
               ->execute([$name, $username, $email, $phone, $bio, $social_link, $avatar, $target_id]);
        }

        // Update permissions (only meaningful for admin/author roles)
        if ($target['role'] !== 'super_admin') {
            $db->prepare("DELETE FROM admin_permissions WHERE user_id = ?")->execute([$target_id]);
            if (!empty($new_perms)) {
                $p_stmt = $db->prepare("INSERT INTO admin_permissions (user_id, module) VALUES (?, ?)");
                foreach ($new_perms as $mod) {
                    $p_stmt->execute([$target_id, $mod]);
                }
            }
            $current_perms = $new_perms;
        }

        $db->commit();
        $msg = 'User <strong>' . htmlspecialchars($name) . '</strong> updated successfully!';

        // Refresh local state
        $target = array_merge($target, [
            'name' => $name, 'username' => $username, 'email' => $email,
            'phone' => $phone, 'bio' => $bio, 'social_link' => $social_link, 'avatar' => $avatar
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        $err = strpos($e->getMessage(), 'Duplicate entry') !== false
            ? 'Another user already uses that Username, Email, or Phone.'
            : 'Database error: ' . $e->getMessage();
    }
}

$avatarSrc = !empty($target['avatar'])
    ? BASE_URL . htmlspecialchars($target['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($target['name'] ?: $target['username']) . '&background=6366f1&color=fff&bold=true&size=120';

$allModules = [
    'dashboard'  => ['label' => 'Dashboard Overview',   'icon' => 'fa-gauge'],
    'ai_writer'  => ['label' => 'AI Content Writer',    'icon' => 'fa-robot'],
    'posts'      => ['label' => 'Manage Posts',          'icon' => 'fa-table-list'],
    'categories' => ['label' => 'Categories',            'icon' => 'fa-tags'],
    'settings'   => ['label' => 'Site Settings',         'icon' => 'fa-gear'],
    'users'      => ['label' => 'Manage Users',          'icon' => 'fa-users'],
];

$back_url = $is_author_role ? 'authors.php' : 'users.php';
?>

<div class="mb-8 flex items-center gap-4">
    <a href="<?= $back_url ?>" class="text-slate-500 hover:text-brand transition-colors">
        <i class="fa-solid fa-arrow-left fa-lg"></i>
    </a>
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            <i class="fa-solid fa-user-pen text-brand mr-2"></i> Edit User
        </h1>
        <p class="text-slate-500 mt-1">Manage full account details for <strong><?= htmlspecialchars($target['name'] ?: $target['username']) ?></strong>.</p>
    </div>
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

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-3 gap-8 max-w-6xl">

    <!-- ── LEFT: Avatar & Role Card ────────────────────────────── -->
    <div class="xl:col-span-1 space-y-6">

        <!-- Avatar -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 flex flex-col items-center text-center">
            <div class="relative mb-4 group">
                <img id="avatarPreviewImg" src="<?= $avatarSrc ?>"
                     alt="<?= htmlspecialchars($target['name'] ?: $target['username']) ?>"
                     class="w-32 h-32 rounded-full object-cover border-4 border-brandLight shadow-md">
                <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" title="Change Photo">
                    <i class="fa-solid fa-camera text-white fa-xl"></i>
                    <input type="file" name="avatar" accept="image/*" class="hidden" id="avatarInput">
                </label>
            </div>
            <p class="font-bold text-slate-900 text-lg leading-snug"><?= htmlspecialchars($target['name'] ?: $target['username']) ?></p>
            <span class="mt-1 bg-brandLight text-brand text-xs font-bold px-2.5 py-1 rounded-full tracking-wide uppercase">
                <?= str_replace('_', ' ', $target['role']) ?>
            </span>
            <p class="text-slate-400 text-xs mt-3">Hover over photo to change avatar</p>
        </div>

        <!-- Permissions Card (not for super_admin) -->
        <?php if ($target['role'] !== 'super_admin'): ?>
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm"><i class="fa-solid fa-shield-halved mr-2 text-brand"></i>Module Access</h3>
            </div>
            <div class="p-5 flex flex-col gap-3">
                <?php foreach ($allModules as $mod => $info): ?>
                    <label class="flex items-center gap-3 text-slate-700 cursor-pointer hover:text-brand transition-colors group">
                        <input type="checkbox" name="permissions[]" value="<?= $mod ?>"
                               <?= in_array($mod, $current_perms) ? 'checked' : '' ?>
                               class="w-4 h-4 text-brand rounded border-slate-300 focus:ring-brand">
                        <i class="fa-solid <?= $info['icon'] ?> w-4 text-center text-slate-400 group-hover:text-brand transition-colors"></i>
                        <span class="text-sm font-medium"><?= $info['label'] ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-brandLight border border-brand border-opacity-30 rounded-xl p-5 text-center">
            <i class="fa-solid fa-crown text-brand fa-2x mb-2"></i>
            <p class="text-brand font-bold text-sm">Super Admin</p>
            <p class="text-slate-500 text-xs mt-1">Has full access to all modules by default.</p>
        </div>
        <?php endif; ?>

    </div><!-- /left -->

    <!-- ── RIGHT: Form Fields ────────────────────────────────────── -->
    <div class="xl:col-span-2 space-y-6">

        <!-- Basic Info -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-user mr-2 text-brand"></i>Basic Information</h2>
            </div>
            <div class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($target['name'] ?? '') ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Username <span class="text-red-400">*</span></label>
                        <input type="text" name="username" required
                               value="<?= htmlspecialchars($target['username']) ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Email <span class="text-red-400">*</span></label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($target['email']) ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Phone <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input type="text" name="phone"
                               value="<?= htmlspecialchars($target['phone'] ?? '') ?>"
                               placeholder="e.g. 01700000000"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Author Details (always present) -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-feather-pointed mr-2 text-brand"></i>Author Details</h2>
                <p class="text-xs text-slate-400 mt-0.5">Displayed on the public article bio card.</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Short Bio</label>
                    <textarea name="bio" rows="4"
                              placeholder="A short description about this user..."
                              class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors resize-none bg-slate-50 focus:bg-white"><?= htmlspecialchars($target['bio'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Social / Portfolio URL</label>
                    <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-brand bg-slate-50 focus-within:bg-white">
                        <span class="px-3 py-2.5 bg-slate-100 border-r border-slate-300 text-slate-500 text-sm"><i class="fa-solid fa-link"></i></span>
                        <input type="url" name="social"
                               value="<?= htmlspecialchars($target['social_link'] ?? '') ?>"
                               placeholder="https://twitter.com/handle"
                               class="flex-1 px-4 py-2.5 outline-none bg-transparent text-slate-700">
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Reset -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-key mr-2 text-amber-500"></i>Reset Password</h2>
                <p class="text-xs text-slate-400 mt-0.5">Leave blank to keep the current password unchanged.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password" id="newPasswordInput"
                               placeholder="Set a new password"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirm Password</label>
                        <input type="password" id="confirmPasswordInput"
                               placeholder="Re-type new password"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand transition-colors">
                        <p id="pwMatchMsg" class="text-xs mt-1 hidden"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save & Danger Zone -->
        <div class="flex items-center justify-between gap-4">
            <a href="<?= $back_url ?>"
               class="text-slate-500 hover:text-slate-700 border border-slate-300 hover:border-slate-400 font-semibold py-2.5 px-6 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Cancel
            </a>
            <div class="flex items-center gap-3">
                <?php if ($target['id'] != 1): ?>
                <a href="<?= $back_url ?>?delete=<?= $target['id'] ?>"
                   onclick="return confirm('Permanently delete this user? Posts will be unassigned.')"
                   class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 font-bold py-2.5 px-5 rounded-lg transition-all flex items-center gap-2">
                    <i class="fa-solid fa-trash"></i> Delete
                </a>
                <?php endif; ?>
                <button type="submit" id="saveBtn"
                        class="bg-brand hover:bg-brandDark text-white font-bold py-2.5 px-8 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </div>

    </div><!-- /right -->
</form>

<script>
// Avatar live preview
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('avatarPreviewImg').src = e.target.result; };
    reader.readAsDataURL(file);
});

// Password match guard
const newPw   = document.getElementById('newPasswordInput');
const confPw  = document.getElementById('confirmPasswordInput');
const pwMsg   = document.getElementById('pwMatchMsg');
const saveBtn = document.getElementById('saveBtn');

function checkPw() {
    if (!confPw.value) { pwMsg.classList.add('hidden'); return; }
    const match = newPw.value === confPw.value;
    pwMsg.textContent = match ? '✓ Passwords match' : '✗ Passwords do not match';
    pwMsg.className   = 'text-xs mt-1 ' + (match ? 'text-emerald-600' : 'text-red-500');
    pwMsg.classList.remove('hidden');
    saveBtn.disabled  = !match;
}
newPw.addEventListener('input', checkPw);
confPw.addEventListener('input', checkPw);

document.querySelector('form').addEventListener('submit', e => {
    if (newPw.value && newPw.value !== confPw.value) {
        e.preventDefault();
        alert('Passwords do not match.');
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
