<?php
require_once '../includes/admin_header.php';
// Any logged-in user can manage their own profile
$msg = '';
$err = '';

// Re-fetch full user details (including author fields)
$stmt = $db->prepare("SELECT name, username, email, phone, role, bio, social_link, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']     ?? '');
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email']    ?? '');
    $phone       = trim($_POST['phone']    ?? '');
    $phone       = $phone === '' ? null : $phone;
    $bio         = trim($_POST['bio']      ?? '');
    $social_link = trim($_POST['social']   ?? '');
    $new_password = $_POST['new_password'] ?? '';

    // Handle avatar upload
    $avatar = $current_user['avatar']; // keep existing by default
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $up_dir = '../assets/uploads/avatars/';
        if (!is_dir($up_dir)) mkdir($up_dir, 0755, true);
        $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['avatar']['name']));
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $up_dir . $fname)) {
            $avatar = 'assets/uploads/avatars/' . $fname;
        }
    }

    try {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET name=?, username=?, email=?, phone=?, bio=?, social_link=?, avatar=?, password=? WHERE id=?")
               ->execute([$name, $username, $email, $phone, $bio, $social_link, $avatar, $hashed, $_SESSION['user_id']]);
        } else {
            $db->prepare("UPDATE users SET name=?, username=?, email=?, phone=?, bio=?, social_link=?, avatar=? WHERE id=?")
               ->execute([$name, $username, $email, $phone, $bio, $social_link, $avatar, $_SESSION['user_id']]);
        }
        $msg = "Profile updated successfully!";
        log_notification('profile_updated', "User updated their profile information.");
        $_SESSION['user_name'] = $name; // Update session name

        // Refresh local state immediately
        $current_user = array_merge($current_user, [
            'name' => $name, 'username' => $username, 'email' => $email,
            'phone' => $phone, 'bio' => $bio, 'social_link' => $social_link, 'avatar' => $avatar
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $err = 'Another user already uses that Username, Email, or Phone.';
        } else {
            $err = 'Database error: ' . $e->getMessage();
        }
    }
}

$is_author = ($user['role'] === 'author');
$avatarSrc = !empty($current_user['avatar'])
    ? BASE_URL . htmlspecialchars($current_user['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['name'] ?: $current_user['username']) . '&background=6366f1&color=fff&bold=true&size=120';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-id-badge text-brand mr-2"></i> My Profile</h1>
    <p class="text-slate-500 mt-1">Manage your personal information, author bio, and login credentials.</p>
</div>

<?php if ($msg): ?>
    <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="bg-red-50 text-red-600 border border-red-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-3 gap-8 max-w-6xl">

    <!-- ── LEFT: Avatar Card ─────────────────────────────────────── -->
    <div class="xl:col-span-1">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 flex flex-col items-center text-center sticky top-6">

            <!-- Avatar Preview -->
            <div class="relative mb-4 group">
                <img id="avatarPreviewImg" src="<?= $avatarSrc ?>"
                     alt="<?= htmlspecialchars($current_user['name'] ?: $current_user['username']) ?>"
                     class="w-32 h-32 rounded-full object-cover border-4 border-brandLight shadow-md">
                <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" title="Change Photo">
                    <i class="fa-solid fa-camera text-white fa-xl"></i>
                    <input type="file" name="avatar" accept="image/*" class="hidden" id="avatarInput">
                </label>
            </div>

            <p class="font-bold text-slate-900 text-lg leading-snug"><?= htmlspecialchars($current_user['name'] ?: $current_user['username']) ?></p>
            <span class="mt-1 bg-brandLight text-brand text-xs font-bold px-2.5 py-1 rounded-full tracking-wide uppercase">
                <?= str_replace('_', ' ', $current_user['role']) ?>
            </span>

            <?php if (!empty($current_user['social_link'])): ?>
                <a href="<?= htmlspecialchars($current_user['social_link']) ?>" target="_blank" rel="noopener"
                   class="mt-3 text-sm text-brand hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-link text-xs"></i> Social Profile
                </a>
            <?php endif; ?>

            <p class="mt-4 text-xs text-slate-400">Hover over photo to change avatar</p>
        </div>
    </div>

    <!-- ── RIGHT: Form Fields ────────────────────────────────────── -->
    <div class="xl:col-span-2 space-y-6">

        <!-- Basic Info Card -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-user mr-2 text-brand"></i>Basic Information</h2>
            </div>
            <div class="p-6 space-y-5">
                <!-- Name & Username -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($current_user['name'] ?? '') ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Username <span class="text-red-400">*</span></label>
                        <input type="text" name="username" required
                               value="<?= htmlspecialchars($current_user['username']) ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                </div>

                <!-- Email & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($current_user['email']) ?>"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Phone <span class="text-slate-400 font-normal">(optional — can be used to login)</span></label>
                        <input type="text" name="phone"
                               value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>"
                               placeholder="e.g. 01700000000"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Author Info Card (shown for all, most useful for authors) -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-feather-pointed mr-2 text-brand"></i>Author Details</h2>
                <p class="text-xs text-slate-400 mt-0.5">Displayed publicly on the article bio card.</p>
            </div>
            <div class="p-6 space-y-5">
                <!-- Bio -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Short Bio</label>
                    <textarea name="bio" rows="4"
                              placeholder="Tell readers a little about yourself..."
                              class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors resize-none bg-slate-50 focus:bg-white"><?= htmlspecialchars($current_user['bio'] ?? '') ?></textarea>
                </div>
                <!-- Social Link -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Social / Portfolio URL</label>
                    <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-brand focus-within:border-brand transition-colors bg-slate-50 focus-within:bg-white">
                        <span class="px-3 py-2.5 bg-slate-100 border-r border-slate-300 text-slate-500 text-sm"><i class="fa-solid fa-link"></i></span>
                        <input type="url" name="social"
                               value="<?= htmlspecialchars($current_user['social_link'] ?? '') ?>"
                               placeholder="https://twitter.com/yourhandle"
                               class="flex-1 px-4 py-2.5 outline-none bg-transparent text-slate-700">
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Card -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-800"><i class="fa-solid fa-lock mr-2 text-brand"></i>Change Password</h2>
                <p class="text-xs text-slate-400 mt-0.5">Leave both fields empty to keep your current password.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password" id="newPasswordInput"
                               placeholder="Enter new password"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirm Password</label>
                        <input type="password" id="confirmPasswordInput"
                               placeholder="Re-type new password"
                               class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors">
                        <p id="pwMatchMsg" class="text-xs mt-1 hidden"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" id="saveBtn"
                    class="bg-brand hover:bg-brandDark text-white font-bold py-3 px-10 rounded-xl transition-colors flex items-center gap-2 shadow-md">
                <i class="fa-solid fa-floppy-disk"></i> Save Profile
            </button>
        </div>

    </div><!-- /right -->
</form>

<script>
// Avatar live preview
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarPreviewImg').src = e.target.result;
    };
    reader.readAsDataURL(file);
});

// Password match validation
const newPw  = document.getElementById('newPasswordInput');
const confPw = document.getElementById('confirmPasswordInput');
const msg    = document.getElementById('pwMatchMsg');
const saveBtn = document.getElementById('saveBtn');

function checkPasswords() {
    if (!confPw.value) { msg.classList.add('hidden'); return; }
    if (newPw.value === confPw.value) {
        msg.textContent = '✓ Passwords match';
        msg.className = 'text-xs mt-1 text-emerald-600';
        saveBtn.disabled = false;
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.className = 'text-xs mt-1 text-red-500';
        saveBtn.disabled = true;
    }
    msg.classList.remove('hidden');
}

newPw.addEventListener('input', checkPasswords);
confPw.addEventListener('input', checkPasswords);

// Guard submit
document.querySelector('form').addEventListener('submit', function (e) {
    if (newPw.value && newPw.value !== confPw.value) {
        e.preventDefault();
        alert('Passwords do not match. Please check again.');
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
