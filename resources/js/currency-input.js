const fields = new Set();
let scanFrame = null;

const normalizeAmount = (value) => {
    const text = String(value ?? '').trim();

    if (/^\d+(?:\.\d{1,2})?$/.test(text)) {
        return String(Math.max(0, Math.round(Number(text))));
    }

    const digits = text.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 13);

    return digits || '0';
};

const formatAmount = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0,
}).format(Number(normalizeAmount(value)));

function refreshField(field) {
    const display = field.querySelector('[data-currency-input]');
    const model = field.querySelector('[data-currency-model]');

    if (!display || !model || document.activeElement === display) return;

    const normalized = normalizeAmount(model.value);
    model.value = normalized;
    display.value = formatAmount(normalized);
}

function bindField(field) {
    if (field.dataset.currencyReady === 'true') {
        refreshField(field);
        return;
    }

    const display = field.querySelector('[data-currency-input]');
    const model = field.querySelector('[data-currency-model]');
    if (!display || !model) return;

    field.dataset.currencyReady = 'true';
    fields.add(field);

    const sync = () => {
        const normalized = normalizeAmount(display.value);
        display.value = formatAmount(normalized);
        model.value = normalized;
        model.dispatchEvent(new Event('input', { bubbles: true }));
    };

    display.addEventListener('input', sync);
    display.addEventListener('blur', sync);
    display.addEventListener('focus', () => {
        if (normalizeAmount(display.value) === '0') display.select();
    });

    refreshField(field);
}

function enhanceCurrencyFields(root = document) {
    fields.forEach((field) => {
        if (!field.isConnected) fields.delete(field);
    });

    const closestField = root.closest?.('[data-currency-field]');
    const candidates = root.matches?.('[data-currency-field]')
        ? [root]
        : closestField
            ? [closestField]
            : Array.from(root.querySelectorAll?.('[data-currency-field]') ?? []);

    candidates.forEach(bindField);
}

function scheduleEnhance(root = document) {
    if (scanFrame) window.cancelAnimationFrame(scanFrame);
    scanFrame = window.requestAnimationFrame(() => {
        scanFrame = null;
        enhanceCurrencyFields(root.isConnected === false ? document : root);
    });
}

const observer = new MutationObserver(() => scheduleEnhance(document));

function boot() {
    enhanceCurrencyFields(document);
    observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['value'] });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
else boot();

document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', ({ el }) => scheduleEnhance(el));
    window.Livewire?.hook('morphed', ({ el }) => scheduleEnhance(el));
});

document.addEventListener('livewire:navigated', () => scheduleEnhance(document));
