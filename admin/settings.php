<?php
require_once '../includes/admin_header.php';
require_permission('settings');

$msg = '';

// Fetch existing to handle empty uploads
$settings = get_all_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [
        'site_name' => $_POST['site_name']
    ];

    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Handle Logo Upload
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $filename = 'logo_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['logo_file']['name']));
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $filename)) {
            $updates['logo_url'] = 'assets/uploads/' . $filename;
        }
    }

    // Handle Favicon Upload
    if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
        $filename = 'favicon_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['favicon_file']['name']));
        if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $upload_dir . $filename)) {
            $updates['favicon_url'] = 'assets/uploads/' . $filename;
        }
    }

    // Use a safe upsert query since favicon_url might not exist yet
    $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE key_name = ?");
    $insertStmt = $db->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?)");
    $updateStmt = $db->prepare("UPDATE settings SET value = ? WHERE key_name = ?");
    
    foreach($updates as $k => $v) {
        $stmt->execute([$k]);
        if ($stmt->fetchColumn() > 0) {
            $updateStmt->execute([$v, $k]);
        } else {
            $insertStmt->execute([$k, $v]);
        }
    }
    
    $msg = "Settings updated successfully!";
    $settings = get_all_settings(); // refresh local scope
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-gear text-brand mr-2"></i> Site Configuration</h1>
    <p class="text-slate-500 mt-1">Customize the identity and branding of your platform.</p>
</div>

<?php if ($msg): ?>
    <div class="bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-lg p-4 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden max-w-3xl">
    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-bold text-slate-800">Global Branding Elements</h2>
    </div>
    <form method="POST" action="" class="p-6 md:p-8" enctype="multipart/form-data">
        <div class="mb-6">
            <label class="block text-sm font-bold text-slate-700 mb-2">Website Name</label>
            <input type="text" name="site_name" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors text-lg" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
        </div>
        
        <div class="mb-6 bg-slate-50 p-6 rounded-lg border border-dashed border-slate-300 flex flex-col sm:flex-row items-center gap-6 relative hover:bg-slate-100 transition-colors">
            <i class="fa-solid fa-image fa-3x text-slate-400"></i>
            <div class="flex-1 w-full">
                <label class="block text-sm font-bold text-slate-700 mb-2 w-full cursor-pointer">
                    Main Logo Upload
                </label>
                <input type="file" name="logo_file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brandLight file:text-brand hover:file:bg-brand hover:file:text-white transition-colors cursor-pointer" accept="image/*">
                <?php if (!empty($settings['logo_url'])): ?>
                    <p class="mt-2 text-xs text-slate-500">Current Logo: <span class="font-semibold text-brand"><?= basename($settings['logo_url']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-8 bg-slate-50 p-6 rounded-lg border border-dashed border-slate-300 flex flex-col sm:flex-row items-center gap-6 relative hover:bg-slate-100 transition-colors">
            <i class="fa-solid fa-icons fa-3x text-slate-400"></i>
            <div class="flex-1 w-full">
                <label class="block text-sm font-bold text-slate-700 mb-2 w-full cursor-pointer">
                    Favicon Upload (Browser Tab Icon)
                </label>
                <input type="file" name="favicon_file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brandLight file:text-brand hover:file:bg-brand hover:file:text-white transition-colors cursor-pointer" accept="image/*">
                <?php if (!empty($settings['favicon_url'])): ?>
                    <p class="mt-2 text-xs text-slate-500">Current Favicon: <span class="font-semibold text-brand"><?= basename($settings['favicon_url']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="border-t border-slate-200 pt-6">
            <button type="submit" class="bg-slate-900 hover:bg-brand text-white font-bold py-3 px-8 rounded-lg transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Save Settings
            </button>
        </div>
    </form>
</div>

<?php 
require_once '../includes/admin_footer.php'; 
?>
