<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id']);
    $password = $_POST['password'];

    // Check against username, email, or phone
    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = ? OR email = ? OR phone = ?");
    $stmt->execute([$login_id, $login_id, $login_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid login credentials.';
    }
}
$settings = get_all_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($settings['site_name'] ?? 'AI Blog') ?></title>
    
    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Josefin Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: '#6366f1', // Indigo-500
                        brandDark: '#4f46e5',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 w-full max-w-md p-8 text-center">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Secure Access</h1>
        <p class="text-slate-500 mb-8">Admin Authentication</p>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 border border-red-200 rounded-lg p-3 mb-6 text-sm text-left">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5 text-left">
                <label class="block text-sm font-medium text-slate-700 mb-1">Email / Username / Phone</label>
                <input type="text" name="login_id" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required autofocus>
            </div>
            <div class="mb-6 text-left">
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" required>
            </div>
            <button type="submit" class="w-full bg-brand hover:bg-brandDark text-white font-bold py-3 px-4 rounded-lg transition-colors flex justify-center items-center gap-2">
                <i class="fa-solid fa-lock"></i> Login to Dashboard
            </button>
        </form>
        <div class="mt-6">
            <a href="<?= BASE_URL ?>" class="text-slate-500 hover:text-brand transition-colors text-sm font-medium">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Website
            </a>
        </div>
    </div>
</body>
</html>
