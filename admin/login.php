<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error            = '';
$warning          = '';
$suspended_msg    = false; // triggers modal
$suspended_reason = '';

// Mid-session kick: super admin suspended this user while they were logged in
if (isset($_GET['suspended']) && $_GET['suspended'] == '1') {
    $suspended_msg    = true;
    $suspended_reason = 'Your account was suspended by an administrator while you were logged in. Contact your Super Admin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("
        SELECT id, password, role, status, login_attempts, last_attempt_at, suspended_reason
        FROM users
        WHERE username = ? OR email = ? OR phone = ?
    ");
    $stmt->execute([$login_id, $login_id, $login_id]);
    $userData = $stmt->fetch();

    if ($userData) {

        // ── Already suspended ──────────────────────────────────────
        if ($userData['status'] === 'suspended') {
            $suspended_msg    = true;
            $suspended_reason = $userData['suspended_reason'] ?? 'No reason provided.';

        } else {
            // Reset attempts if last attempt was > 30 min ago
            if ($userData['last_attempt_at'] && (time() - strtotime($userData['last_attempt_at'])) > 1800) {
                $db->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?")
                   ->execute([$userData['id']]);
                $userData['login_attempts'] = 0;
            }

            if (password_verify($password, $userData['password'])) {
                // ── SUCCESS ───────────────────────────────────────
                $db->prepare("UPDATE users SET login_attempts = 0, last_attempt_at = NULL WHERE id = ?")
                   ->execute([$userData['id']]);
                $_SESSION['user_id'] = $userData['id'];
                header("Location: index.php");
                exit;

            } else {
                // ── FAILED ATTEMPT ────────────────────────────────
                $new_attempts = $userData['login_attempts'] + 1;
                $db->prepare("UPDATE users SET login_attempts = ?, last_attempt_at = NOW() WHERE id = ?")
                   ->execute([$new_attempts, $userData['id']]);

                if ($new_attempts >= 3) {
                    // Auto-suspend
                    $auto_reason = 'Auto-suspended: 3 consecutive failed login attempts.';
                    $db->prepare("UPDATE users SET status = 'suspended', suspended_reason = ? WHERE id = ?")
                       ->execute([$auto_reason, $userData['id']]);
                    $suspended_msg    = true;
                    $suspended_reason = $auto_reason;
                } else {
                    $remaining = 3 - $new_attempts;
                    $warning = "Invalid credentials. <strong>{$remaining} attempt" . ($remaining != 1 ? 's' : '') . "</strong> remaining before your account is suspended.";
                }
            }
        }

    } else {
        $warning = "Invalid credentials.";
    }
}

$settings = get_all_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= htmlspecialchars($settings['site_name'] ?? 'AI Blog') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Josefin Sans"', 'sans-serif'] },
                    colors: { brand: '#6366f1', brandDark: '#4f46e5' }
                }
            }
        }
    </script>
    <style>
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.92) translateY(16px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-animate { animation: modalIn .25s cubic-bezier(.4,0,.2,1) both; }
    </style>
</head>
<body class="font-sans antialiased bg-slate-100 flex items-center justify-center min-h-screen p-4">

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-50 text-brand rounded-2xl mb-4 shadow-sm">
                <i class="fa-solid fa-lock fa-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Secure Access</h1>
            <p class="text-slate-400 text-sm mt-1"><?= htmlspecialchars($settings['site_name'] ?? 'Admin Panel') ?></p>
        </div>

        <!-- Warning (attempts remaining) -->
        <?php if ($warning): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-xl p-4 mb-5 text-sm flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>
                <div><?= $warning ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    <i class="fa-solid fa-user mr-1 text-slate-400"></i> Username / Email / Phone
                </label>
                <input type="text" name="login_id"
                       value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>"
                       class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white"
                       required autofocus placeholder="Enter your username or email">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    <i class="fa-solid fa-key mr-1 text-slate-400"></i> Password
                </label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput"
                           class="w-full border border-slate-300 rounded-xl px-4 py-3 pr-11 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-slate-50 focus:bg-white"
                           required placeholder="Enter your password">
                    <button type="button" onclick="togglePw()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <i class="fa-solid fa-eye" id="pwEyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit"
                    class="w-full bg-brand hover:bg-brandDark text-white font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2 shadow-sm">
                <i class="fa-solid fa-right-to-bracket"></i> Login to Dashboard
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= BASE_URL ?>" class="text-slate-400 hover:text-brand transition-colors text-sm font-medium">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Website
            </a>
        </div>
    </div>

    <!-- ── Suspension Modal ──────────────────────────────────────── -->
    <div id="suspensionModal"
         class="fixed inset-0 z-50 <?= $suspended_msg ? 'flex' : 'hidden' ?> items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm px-4">
        <div class="modal-animate bg-white rounded-2xl shadow-2xl border border-red-100 w-full max-w-md overflow-hidden">

            <!-- Red header band -->
            <div class="bg-red-500 px-6 py-5 flex items-center gap-4">
                <div class="bg-white bg-opacity-20 rounded-full w-12 h-12 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-ban text-white fa-xl"></i>
                </div>
                <div>
                    <h2 class="text-white font-bold text-lg leading-tight">Account Suspended</h2>
                    <p class="text-red-100 text-sm">Your access has been revoked</p>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                <p class="text-slate-600 text-sm mb-4 leading-relaxed">
                    Your account has been suspended by an administrator. You cannot access the dashboard until an administrator restores your account.
                </p>

                <!-- Reason box -->
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-6">
                    <p class="text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Reason</p>
                    <p class="text-red-700 font-semibold text-sm"><?= htmlspecialchars($suspended_reason ?: 'No reason provided.') ?></p>
                </div>

                <p class="text-slate-400 text-xs mb-5 text-center">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Contact your Super Admin to appeal or restore your access.
                </p>

                <button onclick="document.getElementById('suspensionModal').classList.add('hidden')"
                        class="w-full border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold py-2.5 rounded-xl transition-colors text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function togglePw() {
            const input = document.getElementById('passwordInput');
            const icon  = document.getElementById('pwEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Close modal on backdrop click
        document.getElementById('suspensionModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    </script>
</body>
</html>
