// Installable app: register the service worker and offer an install button on supported phones.
const manifest = document.querySelector('link[rel="manifest"]');

if (manifest && 'serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(new URL('sw.js', manifest.href)).catch(() => {});
    });
}

let installPrompt = null;
const installButtons = [...document.querySelectorAll('[data-pwa-install]')];

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    installButtons.forEach((button) => { button.hidden = false; });
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    installButtons.forEach((button) => { button.hidden = true; });
});

installButtons.forEach((button) => button.addEventListener('click', async () => {
    if (!installPrompt) return;
    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    installButtons.forEach((item) => { item.hidden = true; });
}));
