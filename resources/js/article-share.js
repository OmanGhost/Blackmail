document.querySelectorAll('[data-article-share]').forEach((panel) => {
    const copy = panel.querySelector('[data-copy-link]');
    const share = panel.querySelector('[data-native-share]');
    const status = panel.querySelector('[data-share-status]');
    const fallback = panel.querySelector('[data-share-fallback]');
    const copyLink = async () => {
        let copied = false;
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(panel.dataset.url);
                copied = true;
            }
        } catch { /* Offer manual copying if clipboard permission is unavailable. */ }
        fallback.hidden = copied;
        status.textContent = copied ? panel.dataset.copied : panel.dataset.fallback;
        if (!copied) {
            const input = fallback.querySelector('input');
            input.focus();
            input.select();
        }
    };
    copy.hidden = false;
    copy.addEventListener('click', copyLink);
    if (navigator.share) {
        share.hidden = false;
        share.addEventListener('click', async () => {
            try { await navigator.share({ title: panel.dataset.title, url: panel.dataset.url }); }
            catch (error) { if (error.name !== 'AbortError') await copyLink(); }
        });
    }
});
