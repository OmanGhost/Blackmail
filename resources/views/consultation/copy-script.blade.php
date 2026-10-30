<script>
document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
    const status = document.getElementById('copy-status');
    try {
        if (!navigator.clipboard) throw new Error('clipboard unavailable');
        await navigator.clipboard.writeText(button.dataset.copy);
        if (status) status.textContent = 'تم النسخ بنجاح';
    } catch {
        const input = document.createElement('textarea');
        input.value = button.dataset.copy;
        input.setAttribute('aria-label', 'حدد النص وانسخه');
        input.className = 'mt-4 w-full rounded-xl border p-3';
        button.parentElement.appendChild(input);
        input.focus(); input.select();
        if (status) status.textContent = 'حدد النص الظاهر وانسخه لحفظه';
    }
}));
</script>
