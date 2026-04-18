<?php
require_once '../includes/admin_header.php';
// Any logged in user (super_admin or admin) can edit their own profile
$msg = '';
$err = '';

// Re-fetch fresh user details including phone
$stmt = $db->prepare("SELECT name, username, email, phone FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];

    try {
        if (!empty($new_password)) {
            // Update all, including password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $update->execute([$name, $username, $email, $phone, $hashed, $_SESSION['user_id']]);
        } else {
            // Update without changing password
            $update = $db->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ? WHERE id = ?");
            $update->execute([$name, $username, $email, $phone, $_SESSION['user_id']]);
        }
        $msg = "Profile information updated successfully!";
        
        // Refresh local details immediately
        $current_user['name'] = $name;
        $current_user['username'] = $username;
        $current_user['email'] = $email;
        $current_user['phone'] = $phone;
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $err = "Another user is already using that Username, Email, or Phone.";
        } else {
            $err = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-id-badge text-brand mr-2"></i> My Profile</h1>
    <p class="text-slate-500 mt-1">Manage your personal login information and credentials.</p>
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

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden max-w-2xl">
    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-bold text-slate-800">Account Details</h2>
    </div>
    <form method="POST" action="" class="p-6 md:p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Full Name (Public Writer Name)</label>
                <input type="text" name="name" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white" value="<?= htmlspecialchars($current_user['name'] ?? '') ?>" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Username</label>
                <input type="text" name="username" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white" value="<?= htmlspecialchars($current_user['username']) ?>" required>
            </div>
        </div>
        
        <div class="mb-5">
            <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
            <input type="email" name="email" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white" value="<?= htmlspecialchars($current_user['email']) ?>" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-slate-700 mb-2">Phone Number (Optional)</label>
            <input type="text" name="phone" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" placeholder="e.g. 01700000000">
            <p class="mt-2 text-sm text-slate-500"><i class="fa-solid fa-circle-info mr-1"></i> You can use this to login later.</p>
        </div>
        
        <hr class="my-8 border-slate-200">
        
        <div class="mb-8">
            <label class="block text-sm font-bold text-slate-700 mb-2">New Password <span class="text-slate-400 font-normal">(Leave blank to keep current)</span></label>
            <input type="password" name="new_password" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" placeholder="Enter new password">
        </div>
        
        <div class="border-t border-slate-200 pt-6">
            <button type="submit" class="bg-brand hover:bg-brandDark text-white font-bold py-3 px-8 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-check"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
