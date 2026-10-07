const instances = new Set();
let activeInstance = null;
let identifier = 0;
let scanFrame = null;

const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase();

class SearchableSelect {
    constructor(select) {
        this.select = select;
        this.controller = new AbortController();
        this.isOpen = false;
        this.activeIndex = -1;
        this.visibleOptions = [];
        this.originalTabIndex = select.getAttribute('tabindex');
        this.originalAriaHidden = select.getAttribute('aria-hidden');

        this.build();
        this.bind();
        this.refresh();

        select._searchableSelect = this;
        instances.add(this);
    }

    build() {
        const id = `searchable-select-${++identifier}`;

        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select';
        this.wrapper.dataset.searchableSelect = '';

        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.className = 'searchable-select-trigger';
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.setAttribute('aria-haspopup', 'listbox');
        this.trigger.setAttribute('aria-controls', `${id}-list`);

        this.valueLabel = document.createElement('span');
        this.valueLabel.className = 'searchable-select-value';

        const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        chevron.setAttribute('viewBox', '0 0 20 20');
        chevron.setAttribute('fill', 'none');
        chevron.setAttribute('stroke', 'currentColor');
        chevron.setAttribute('aria-hidden', 'true');
        chevron.classList.add('searchable-select-chevron');
        chevron.innerHTML = '<path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />';

        this.trigger.append(this.valueLabel, chevron);
        this.wrapper.append(this.trigger);
        this.select.insertAdjacentElement('afterend', this.wrapper);

        this.dropdown = document.createElement('div');
        this.dropdown.className = 'searchable-select-dropdown';
        this.dropdown.hidden = true;

        const searchWrap = document.createElement('div');
        searchWrap.className = 'searchable-select-search-wrap';
        searchWrap.innerHTML = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" stroke-width="1.7"/><path d="m13 13 3.5 3.5" stroke-linecap="round" stroke-width="1.7"/></svg>';

        this.search = document.createElement('input');
        this.search.type = 'search';
        this.search.className = 'searchable-select-search';
        this.search.autocomplete = 'off';
        this.search.spellcheck = false;
        this.search.setAttribute('aria-autocomplete', 'list');
        this.search.setAttribute('aria-controls', `${id}-list`);
        this.search.placeholder = this.searchPlaceholder();
        searchWrap.append(this.search);

        this.list = document.createElement('div');
        this.list.id = `${id}-list`;
        this.list.className = 'searchable-select-options';
        this.list.setAttribute('role', 'listbox');

        this.dropdown.append(searchWrap, this.list);
        document.body.append(this.dropdown);

        this.select.classList.add('searchable-select-native');
        this.select.setAttribute('aria-hidden', 'true');
        this.select.setAttribute('tabindex', '-1');
    }

    bind() {
        const signal = this.controller.signal;

        this.trigger.addEventListener('click', () => this.toggle(), { signal });
        this.trigger.addEventListener('keydown', (event) => {
            if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                this.open();
            }
        }, { signal });

        this.search.addEventListener('input', () => this.renderOptions(), { signal });
        this.search.addEventListener('keydown', (event) => this.handleSearchKeydown(event), { signal });
        this.select.addEventListener('change', () => this.refresh(), { signal });

        this.select.form?.addEventListener('reset', () => {
            window.setTimeout(() => this.refresh(), 0);
        }, { signal });
    }

    searchPlaceholder() {
        const label = this.select.labels?.[0]?.textContent?.trim();
        const name = label || this.select.getAttribute('aria-label');

        return name ? `Cari ${name.toLocaleLowerCase()}...` : 'Ketik untuk mencari...';
    }

    refresh() {
        if (!this.select.isConnected || !this.wrapper.isConnected) return;

        const selected = this.select.selectedOptions[0];
        const text = selected?.textContent?.trim() || 'Pilih opsi';
        const isPlaceholder = !selected || selected.value === '';

        this.valueLabel.textContent = text;
        this.valueLabel.classList.toggle('is-placeholder', isPlaceholder);
        this.trigger.disabled = this.select.disabled;
        this.trigger.setAttribute('aria-label', `${this.searchPlaceholder().replace(/^Cari |\.\.\.$/g, '')}: ${text}`);

        if (this.isOpen) {
            this.renderOptions();
            this.positionDropdown();
        }
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        if (this.select.disabled || this.isOpen) return;
        if (activeInstance && activeInstance !== this) activeInstance.close();

        activeInstance = this;
        this.isOpen = true;
        this.search.value = '';
        this.trigger.setAttribute('aria-expanded', 'true');
        this.dropdown.hidden = false;
        this.renderOptions();
        this.positionDropdown();

        window.requestAnimationFrame(() => this.search.focus());
    }

    close({ focus = false } = {}) {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.activeIndex = -1;
        this.trigger.setAttribute('aria-expanded', 'false');
        this.dropdown.hidden = true;
        this.search.removeAttribute('aria-activedescendant');
        if (activeInstance === this) activeInstance = null;
        if (focus && this.trigger.isConnected) this.trigger.focus();
    }

    renderOptions() {
        const query = normalize(this.search.value.trim());
        const fragment = document.createDocumentFragment();
        const matches = Array.from(this.select.options).filter((option) => {
            if (option.hidden) return false;
            return !query || normalize(`${option.textContent} ${option.value}`).includes(query);
        });

        this.visibleOptions = [];
        this.activeIndex = -1;
        let currentGroup = null;

        matches.forEach((option) => {
            const group = option.parentElement instanceof HTMLOptGroupElement
                ? option.parentElement.label
                : null;

            if (group && group !== currentGroup) {
                const heading = document.createElement('div');
                heading.className = 'searchable-select-group';
                heading.textContent = group;
                fragment.append(heading);
                currentGroup = group;
            } else if (!group) {
                currentGroup = null;
            }

            const item = document.createElement('button');
            const disabled = option.disabled || option.parentElement?.disabled;
            const selected = option.selected;

            item.type = 'button';
            item.className = 'searchable-select-option';
            item.textContent = option.textContent.trim();
            item.disabled = disabled;
            item.dataset.value = option.value;
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', String(selected));

            if (selected) {
                item.dataset.selected = 'true';
                item.innerHTML = `<span>${this.escapeHtml(option.textContent.trim())}</span><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true"><path d="m4.5 10 3.5 3.5L15.5 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>`;
            }

            item.addEventListener('mouseenter', () => {
                const index = this.visibleOptions.indexOf(item);
                this.setActiveIndex(index);
            }, { signal: this.controller.signal });
            item.addEventListener('click', () => this.choose(option), { signal: this.controller.signal });

            fragment.append(item);
            if (!disabled) this.visibleOptions.push(item);
        });

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = 'Tidak ada pilihan yang cocok.';
            fragment.append(empty);
        }

        this.list.replaceChildren(fragment);
    }

    escapeHtml(value) {
        const span = document.createElement('span');
        span.textContent = value;
        return span.innerHTML;
    }

    choose(option) {
        const changed = this.select.value !== option.value;
        this.select.value = option.value;
        this.refresh();
        this.close({ focus: true });

        if (changed) {
            this.select.dispatchEvent(new Event('input', { bubbles: true }));
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    handleSearchKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            this.close({ focus: true });
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            const lastIndex = this.visibleOptions.length - 1;
            let next = this.activeIndex + direction;
            if (next > lastIndex) next = 0;
            if (next < 0) next = lastIndex;
            this.setActiveIndex(next);
            return;
        }

        if (event.key === 'Enter' && this.activeIndex >= 0) {
            event.preventDefault();
            this.visibleOptions[this.activeIndex]?.click();
        }
    }

    setActiveIndex(index) {
        this.visibleOptions.forEach((item, itemIndex) => {
            item.dataset.active = String(itemIndex === index);
        });

        this.activeIndex = index;
        const active = this.visibleOptions[index];
        if (!active) return;

        if (!active.id) active.id = `searchable-select-option-${++identifier}`;
        this.search.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    }

    positionDropdown() {
        if (!this.isOpen || !this.trigger.isConnected) return;

        const rect = this.trigger.getBoundingClientRect();
        const viewportPadding = 8;
        const gap = 6;
        const minimumWidth = 240;
        const width = Math.min(Math.max(rect.width, minimumWidth), window.innerWidth - (viewportPadding * 2));
        const left = Math.min(
            Math.max(rect.left, viewportPadding),
            window.innerWidth - width - viewportPadding,
        );
        const availableBelow = window.innerHeight - rect.bottom - gap - viewportPadding;
        const availableAbove = rect.top - gap - viewportPadding;
        const openAbove = availableBelow < 220 && availableAbove > availableBelow;

        this.dropdown.style.width = `${width}px`;
        this.dropdown.style.left = `${left}px`;

        if (openAbove) {
            this.dropdown.style.top = 'auto';
            this.dropdown.style.bottom = `${window.innerHeight - rect.top + gap}px`;
            this.list.style.maxHeight = `${Math.max(120, Math.min(256, availableAbove - 58))}px`;
        } else {
            this.dropdown.style.top = `${rect.bottom + gap}px`;
            this.dropdown.style.bottom = 'auto';
            this.list.style.maxHeight = `${Math.max(120, Math.min(256, availableBelow - 58))}px`;
        }
    }

    destroy() {
        this.close();
        this.controller.abort();
        this.wrapper.remove();
        this.dropdown.remove();
        this.select.classList.remove('searchable-select-native');

        if (this.originalTabIndex === null) this.select.removeAttribute('tabindex');
        else this.select.setAttribute('tabindex', this.originalTabIndex);

        if (this.originalAriaHidden === null) this.select.removeAttribute('aria-hidden');
        else this.select.setAttribute('aria-hidden', this.originalAriaHidden);

        delete this.select._searchableSelect;
        instances.delete(this);
    }
}

function enhanceSelects(root = document) {
    instances.forEach((instance) => {
        if (!instance.select.isConnected) instance.destroy();
    });

    const selects = root.matches?.('select')
        ? [root]
        : Array.from(root.querySelectorAll?.('select') ?? []);

    selects
        .filter((select) => !select.multiple && select.dataset.searchable !== 'false')
        .forEach((select) => {
            const instance = select._searchableSelect;

            if (instance && instance.wrapper.isConnected) {
                instance.refresh();
                return;
            }

            if (instance) instance.destroy();
            new SearchableSelect(select);
        });
}

function scheduleEnhance(root = document) {
    if (scanFrame) window.cancelAnimationFrame(scanFrame);
    scanFrame = window.requestAnimationFrame(() => {
        scanFrame = null;
        enhanceSelects(root.isConnected === false ? document : root);
    });
}

document.addEventListener('pointerdown', (event) => {
    if (!activeInstance) return;
    if (activeInstance.wrapper.contains(event.target) || activeInstance.dropdown.contains(event.target)) return;
    activeInstance.close();
}, true);

window.addEventListener('resize', () => activeInstance?.positionDropdown(), { passive: true });
window.addEventListener('scroll', () => activeInstance?.positionDropdown(), { passive: true, capture: true });

const observer = new MutationObserver((mutations) => {
    const requiresRefresh = mutations.some((mutation) => {
        const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;

        if (target?.closest('.searchable-select-dropdown, [data-searchable-select]')) return false;

        return mutation.type === 'childList' || ['disabled', 'selected'].includes(mutation.attributeName);
    });

    if (requiresRefresh) {
        scheduleEnhance(document);
    }
});

function boot() {
    enhanceSelects(document);
    observer.observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['disabled', 'selected'],
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
else boot();

document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', ({ el }) => scheduleEnhance(el));
    window.Livewire?.hook('morphed', ({ el }) => scheduleEnhance(el));
});

document.addEventListener('livewire:navigated', () => scheduleEnhance(document));
