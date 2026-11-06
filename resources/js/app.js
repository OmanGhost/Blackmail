import './bootstrap';
import './help-guide';
import './article-share';
import './pwa';

document.querySelectorAll('[data-country-contact]').forEach((field) => {
    const select = field.querySelector('select');
    const notices = [...field.querySelectorAll('[data-country-notice]')];
    const update = () => notices.forEach((notice) => {
        notice.hidden = notice.dataset.countryNotice !== select.value;
    });
    select.addEventListener('change', update);
    window.addEventListener('pageshow', update);
    select.form?.addEventListener('reset', () => setTimeout(update, 0));
    update();
});

document.querySelectorAll('[data-hero-slideshow]').forEach((hero) => {
    const slides = [...hero.querySelectorAll('.hero-slide')];
    const dots = [...hero.querySelectorAll('[data-hero-slide]')];
    const toggle = hero.querySelector('[data-hero-toggle]');
    const label = hero.querySelector('[data-hero-toggle-label]');
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let index = 0;
    let paused = motion.matches;
    let timer;
    const show = (next) => {
        index = next;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
        dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
    };
    const schedule = () => {
        clearInterval(timer);
        label.textContent = paused ? label.dataset.play : label.dataset.pause;
        toggle.setAttribute('aria-pressed', String(paused));
        if (!paused && !document.hidden) timer = setInterval(() => show((index + 1) % slides.length), 6500);
    };
    toggle.hidden = false;
    hero.querySelector('.hero-dots').hidden = false;
    toggle.addEventListener('click', () => { paused = !paused; schedule(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); paused = true; schedule(); }));
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => { paused = motion.matches; schedule(); });
    schedule();
});

// Invoice "Print / save PDF" button.
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) window.print();
});
