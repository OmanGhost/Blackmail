export function guidanceFlags(threat, safety) {
    return { danger: !!safety.danger, minor: !!safety.minor || threat === 'child',
        published: !!safety.published, account: !!safety.account && threat !== 'account' };
}

const root = document.querySelector('#help-wizard');
if (root) {
    let step = 1;
    let threat = '';
    const next = root.querySelector('#next');
    const previous = root.querySelector('#previous');
    const choices = [...root.querySelectorAll('[data-threat]')];
    const safety = [...root.querySelectorAll('[data-safety]')];
    const error = root.querySelector('[data-error="threat"]');
    const country = root.querySelector('select[name="country_id"]');
    const renderResult = () => {
        root.querySelector('[data-summary]').textContent = choices.find(x => x.dataset.threat === threat)?.querySelector('span').textContent || '';
        const flags = guidanceFlags(threat, Object.fromEntries(safety.map(x => [x.dataset.safety, x.checked])));
        root.querySelectorAll('[data-guidance]').forEach(x => { x.hidden = !flags[x.dataset.guidance]; });
        root.querySelectorAll('[data-advice]').forEach(x => { x.hidden = x.dataset.advice !== threat; });
        const target = root.querySelector('[data-result-country]');
        target.replaceChildren();
        const contact = [...root.querySelectorAll('[data-country-notice]')].find(x => x.dataset.countryNotice === country.value);
        if (contact) {
            const copy = contact.cloneNode(true);
            copy.removeAttribute('data-country-notice');
            copy.hidden = false;
            target.append(copy);
        }
    };
    const show = (focus = true) => {
        root.querySelectorAll('[data-step]').forEach(x => { x.hidden = Number(x.dataset.step) !== step; });
        root.querySelectorAll('[data-progress]').forEach(x => {
            if (Number(x.dataset.progress) === step) x.setAttribute('aria-current', 'step');
            else x.removeAttribute('aria-current');
        });
        const label = root.querySelector('#step-label');
        label.textContent = label.dataset.format.replace(':step', step);
        previous.hidden = step === 1;
        next.textContent = step === 4 ? next.dataset.restart : step === 3 ? next.dataset.result : next.dataset.next;
        if (step === 4) renderResult();
        if (focus) root.querySelector(`[data-step="${step}"] h2`).focus();
    };
    const updateChoices = () => choices.forEach(x => {
        const selected = x.dataset.threat === threat;
        x.setAttribute('aria-pressed', String(selected));
        x.querySelector('.check').textContent = selected ? '✓' : '';
    });
    choices.forEach(button => button.addEventListener('click', () => {
        threat = threat === button.dataset.threat ? '' : button.dataset.threat;
        updateChoices();
        error.hidden = true;
    }));
    next.addEventListener('click', () => {
        if (step === 1 && !threat) { error.hidden = false; choices[0].focus(); return; }
        if (step === 4) {
            threat = '';
            country.value = '';
            country.dispatchEvent(new Event('change'));
            safety.forEach(x => { x.checked = false; });
            root.querySelector('[data-result-country]').replaceChildren();
            updateChoices();
            step = 1;
        } else step++;
        show();
    });
    previous.addEventListener('click', () => { if (step > 1) step--; show(); });
    show(false);
}
