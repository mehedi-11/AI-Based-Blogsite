<?php
require_once '../includes/admin_header.php';
require_permission('ai_writer'); // Use same permission flag so current DB doesn't break

// Fetch categories for the dropdown
$categoriesStmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
$allCategories = $categoriesStmt->fetchAll();

// Fetch all authors & admins for author dropdown
$authorsStmt = $db->query("SELECT id, name, username, role FROM users ORDER BY role ASC, name ASC");
$allAuthors = $authorsStmt->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900"><i class="fa-solid fa-pen-nib text-brand mr-2"></i> Formal Publisher</h1>
    <p class="text-slate-500 mt-1">Draft formal blog articles with hybrid human-AI capabilities.</p>
</div>

<div id="alertBox" class="hidden rounded-lg p-4 mb-6"></div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 max-w-5xl overflow-hidden">
    <div class="p-6 md:p-8">
        <form id="createPostForm" enctype="multipart/form-data">
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Blog Title</label>
                <input type="text" id="postTitle" name="title" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors text-lg" placeholder="Enter an engaging title..." required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Author</label>
                <?php if ($user['role'] === 'author'): ?>
                    <!-- Authors always publish as themselves -->
                    <input type="hidden" name="author_id" value="<?= $_SESSION['user_id'] ?>">
                    <div class="w-full border border-slate-200 bg-slate-50 rounded-lg px-4 py-3 text-slate-600 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-brand"></i>
                        <?= htmlspecialchars($user['name'] ?: $user['username']) ?> <span class="text-slate-400 text-sm">(You)</span>
                    </div>
                <?php else: ?>
                    <select id="postAuthor" name="author_id" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-white">
                        <?php foreach ($allAuthors as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $a['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name'] ?: $a['username']) ?> (<?= ucfirst(str_replace('_', ' ', $a['role'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="mb-6 bg-slate-50 p-6 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center relative hover:bg-slate-100 transition-colors">
                <label class="block text-sm font-bold text-slate-700 mb-2 text-center w-full cursor-pointer">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x text-slate-400 mb-2 block"></i>
                    Featured Image Upload
                </label>
                <input type="file" id="postImage" name="image" class="w-full max-w-xs text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brandLight file:text-brand hover:file:bg-brand hover:file:text-white transition-colors cursor-pointer" accept="image/*">
            </div>

            <div class="mb-6 relative group">
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-sm font-bold text-slate-700">Short Details (Max 50 Words)</label>
                    <button type="button" id="btnGenExcerpt" class="text-xs font-semibold bg-indigo-50 text-brand hover:bg-brand hover:text-white py-1.5 px-3 rounded-full transition-colors flex items-center gap-1 border border-brandLight">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Gen
                    </button>
                </div>
                <textarea id="postExcerpt" name="excerpt" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors" rows="3" placeholder="A brief summary..."></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">Category (Select from Database)</label>
                <select id="postCategory" name="category_id" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors bg-white" required>
                    <option value="" disabled selected>Select a category...</option>
                    <?php foreach($allCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-2 text-sm text-slate-500"><i class="fa-solid fa-info-circle mr-1"></i> New categories can be created in the Categories panel.</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">SEO Keywords (Comma separated)</label>
                <input type="text" id="postKeywords" name="seo_keywords" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand focus:border-brand transition-colors text-lg" placeholder="e.g. AI, future, technology">
            </div>

            <div class="mb-6 relative">
                <label class="block text-sm font-bold text-slate-700 mb-2">Blog Details</label>
                <!-- Rich text controls -->
                <div class="border border-slate-300 border-b-0 rounded-t-lg p-2 bg-slate-50 flex gap-1 flex-wrap items-center text-slate-600 text-sm">
                   <!-- Font Size & Line Spacing -->
                   <select onchange="document.execCommand('fontSize', false, this.value); this.selectedIndex=0;" class="p-1 border rounded bg-white shadow-sm hover:bg-slate-50 outline-none">
                       <option value="">Font Size</option>
                       <option value="2">Small</option>
                       <option value="3">Normal</option>
                       <option value="5">Large</option>
                       <option value="7">Huge</option>
                   </select>
                   <select onchange="let s=window.getSelection(); if(s.rangeCount>0){ let el=s.anchorNode; if(el.nodeType===3) el=el.parentNode; el.style.lineHeight=this.value; } this.selectedIndex=0;" class="p-1 border rounded bg-white shadow-sm hover:bg-slate-50 outline-none">
                       <option value="">Spacing</option>
                       <option value="1">Single (1.0)</option>
                       <option value="1.5">1.5 Lines</option>
                       <option value="2.0">Double (2.0)</option>
                   </select>
                   <div class="h-6 w-px bg-slate-300 mx-1"></div>

                   <!-- Text Formatting -->
                   <button type="button" onclick="document.execCommand('bold',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Bold"><i class="fa-solid fa-bold"></i></button>
                   <button type="button" onclick="document.execCommand('italic',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Italic"><i class="fa-solid fa-italic"></i></button>
                   <button type="button" onclick="document.execCommand('underline',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Underline"><i class="fa-solid fa-underline"></i></button>
                   <button type="button" onclick="document.execCommand('strikeThrough',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Strikethrough"><i class="fa-solid fa-strikethrough"></i></button>
                   <button type="button" onclick="document.execCommand('subscript',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Subscript"><i class="fa-solid fa-subscript"></i></button>
                   <button type="button" onclick="document.execCommand('superscript',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Superscript"><i class="fa-solid fa-superscript"></i></button>
                   <div class="h-6 w-px bg-slate-300 mx-1"></div>

                   <!-- Alignment -->
                   <button type="button" onclick="document.execCommand('justifyLeft',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                   <button type="button" onclick="document.execCommand('justifyCenter',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
                   <button type="button" onclick="document.execCommand('justifyRight',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Align Right"><i class="fa-solid fa-align-right"></i></button>
                   <button type="button" onclick="document.execCommand('justifyFull',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Justify"><i class="fa-solid fa-align-justify"></i></button>
                   <div class="h-6 w-px bg-slate-300 mx-1"></div>

                   <!-- Lists and Indent -->
                   <button type="button" onclick="document.execCommand('insertOrderedList',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Ordered List"><i class="fa-solid fa-list-ol"></i></button>
                   <button type="button" onclick="document.execCommand('insertUnorderedList',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Unordered List"><i class="fa-solid fa-list-ul"></i></button>
                   <button type="button" onclick="document.execCommand('indent',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Tab Space (Indent)"><i class="fa-solid fa-indent"></i></button>
                   <button type="button" onclick="document.execCommand('outdent',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Remove Tab (Outdent)"><i class="fa-solid fa-outdent"></i></button>
                   <div class="h-6 w-px bg-slate-300 mx-1"></div>

                   <!-- Color -->
                   <label class="cursor-pointer w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center relative shadow-sm border border-transparent hover:border-slate-300" title="Text Color">
                       <i class="fa-solid fa-font"></i>
                       <input type="color" onchange="document.execCommand('foreColor', false, this.value)" class="opacity-0 absolute inset-0 w-full h-full cursor-pointer">
                   </label>
                   <label class="cursor-pointer w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center relative shadow-sm border border-transparent hover:border-slate-300" title="Highlight Color">
                       <i class="fa-solid fa-highlighter"></i>
                       <input type="color" onchange="document.execCommand('hiliteColor', false, this.value)" class="opacity-0 absolute inset-0 w-full h-full cursor-pointer" value="#ffff00">
                   </label>
                   <div class="h-6 w-px bg-slate-300 mx-1"></div>
                   
                   <!-- Link -->
                   <button type="button" onclick="let url=prompt('Enter link URL:'); if(url) document.execCommand('createLink', false, url);" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Insert Link"><i class="fa-solid fa-link"></i></button>
                   <button type="button" onclick="document.execCommand('unlink',false,null)" class="w-8 h-8 hover:bg-slate-200 rounded flex items-center justify-center transition-colors" title="Remove Link"><i class="fa-solid fa-link-slash"></i></button>

                   <div class="flex-grow mt-2 md:mt-0"></div>
                   <button type="button" id="btnGenContent" class="bg-gradient-to-r from-violet-500 to-blue-500 hover:from-violet-600 hover:to-blue-600 text-white font-semibold py-1.5 px-4 rounded-md shadow-sm transition-all flex items-center gap-2 text-sm ml-auto">
                       <i class="fa-solid fa-robot"></i> Fill with AI
                   </button>
                </div>
                <div id="postContentEditor" class="w-full border border-slate-300 rounded-b-lg p-4 min-h-[400px] bg-white overflow-y-auto focus:outline-none focus:ring-2 focus:ring-brand transition-shadow prose max-w-none" contenteditable="true"></div>
                <input type="hidden" id="postContent" name="content">
            </div>

            <hr class="my-8 border-slate-200">

            <button type="submit" id="publishBtn" class="w-full bg-slate-900 hover:bg-brand text-white font-bold py-4 px-6 rounded-xl transition-colors flex justify-center items-center gap-2 text-lg shadow-md">
                <i class="fa-solid fa-paper-plane"></i> Publish Blog
            </button>
        </form>
    </div>
</div>

<!-- Modal for Loading Overlays -->
<div id="loadingOverlay" class="fixed inset-0 bg-slate-900 bg-opacity-70 z-[100] hidden items-center justify-center flex-col backdrop-blur-sm">
    <i class="fa-solid fa-circle-notch fa-spin text-brand text-5xl mb-4"></i>
    <h2 class="text-white text-xl font-bold font-sans" id="loadingText">Processing...</h2>
</div>

<?php 
require_once '../includes/admin_footer.php'; 
?>
