document.addEventListener('DOMContentLoaded', () => {

    // ---- HYBRID BLOG EDITOR LOGIC ----
    const createPostForm = document.getElementById('createPostForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const contentEditor = document.getElementById('postContentEditor');
    const hiddenContent = document.getElementById('postContent');

    if (createPostForm) {
        
        // Ensure editable div transfers content to hidden input
        createPostForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hiddenContent.value = contentEditor.innerHTML;

            const formData = new FormData(createPostForm);
            
            try {
                loadingText.innerText = 'Publishing securely...';
                loadingOverlay.style.display = 'flex';
                
                const response = await fetch('api.php?action=publish_hybrid_post', {
                    method: 'POST',
                    body: formData // FormData automatically sets correct multi-part headers
                });
                
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                
                alert('Blog Published successfully!');
                window.location.href = 'posts.php';
            } catch (err) {
                alert('Publishing failed: ' + err.message);
                loadingOverlay.style.display = 'none';
            }
        });

        // AI Excerpt Generator
        document.getElementById('btnGenExcerpt')?.addEventListener('click', async () => {
            const topic = document.getElementById('postTitle').value;
            if (!topic) return alert('Please enter a Blog Title first!');

            try {
                loadingText.innerText = 'AI is writing a snappy excerpt...';
                loadingOverlay.style.display = 'flex';
                const response = await fetch('api.php?action=ai_generate_excerpt', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ topic: topic })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                
                document.getElementById('postExcerpt').value = data.excerpt;
            } catch (err) {
                alert('AI Error: ' + err.message);
            } finally {
                loadingOverlay.style.display = 'none';
            }
        });

        // AI Content Generator
        document.getElementById('btnGenContent')?.addEventListener('click', async () => {
            const topic = document.getElementById('postTitle').value;
            if (!topic) return alert('Please enter a Blog Title first!');

            try {
                loadingText.innerText = 'AI is drafting a full article... This may take up to 20 seconds.';
                loadingOverlay.style.display = 'flex';
                const response = await fetch('api.php?action=ai_generate_content', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ topic: topic })
                });
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                
                // Inject the generated blocks
                contentEditor.innerHTML = data.content || '';
                if(data.title) document.getElementById('postTitle').value = data.title;
                if(data.excerpt) document.getElementById('postExcerpt').value = data.excerpt;
                if(data.seo_keywords) {
                    let kwInput = document.getElementById('postKeywords');
                    if(kwInput) kwInput.value = data.seo_keywords;
                }
            } catch (err) {
                alert('AI Error: ' + err.message);
            } finally {
                loadingOverlay.style.display = 'none';
            }
        });
    }
});
