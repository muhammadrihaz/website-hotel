function bootConfirmationDialog() {
    if (window.__hotelConfirmationDialogBooted) return;

    const dialog = document.querySelector('[data-confirm-dialog]');
    if (!dialog) return;

    window.__hotelConfirmationDialogBooted = true;

    const title = dialog.querySelector('[data-confirm-dialog-title]');
    const message = dialog.querySelector('[data-confirm-dialog-message]');
    const cancelButton = dialog.querySelector('[data-confirm-dialog-cancel]');
    const acceptButton = dialog.querySelector('[data-confirm-dialog-accept]');
    let pendingTrigger = null;
    let lastFocusedElement = null;
    let restoreFocus = true;

    const close = ({ focus = true } = {}) => {
        restoreFocus = focus;
        if (dialog.open) dialog.close();
    };

    const open = (trigger) => {
        pendingTrigger = trigger;
        lastFocusedElement = document.activeElement;
        restoreFocus = true;

        const tone = ['danger', 'warning', 'primary'].includes(trigger.dataset.confirmTone)
            ? trigger.dataset.confirmTone
            : 'warning';

        dialog.dataset.tone = tone;
        title.textContent = trigger.dataset.confirmTitle || 'Konfirmasi tindakan';
        message.textContent = trigger.dataset.confirm || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        acceptButton.textContent = trigger.dataset.confirmLabel || 'Ya, lanjutkan';
        document.body.classList.add('confirmation-open');
        dialog.showModal();

        window.requestAnimationFrame(() => cancelButton.focus());
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-confirm]');
        if (!trigger) return;

        if (trigger.disabled || trigger.getAttribute('aria-disabled') === 'true') return;

        event.preventDefault();
        event.stopImmediatePropagation();
        open(trigger);
    }, true);

    cancelButton.addEventListener('click', () => close());

    acceptButton.addEventListener('click', () => {
        const trigger = pendingTrigger;
        close({ focus: false });

        if (!trigger?.isConnected) return;

        const confirmationMessage = trigger.getAttribute('data-confirm');
        trigger.removeAttribute('data-confirm');
        trigger.click();

        if (trigger.isConnected && confirmationMessage !== null) {
            trigger.setAttribute('data-confirm', confirmationMessage);
        }
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) close();
    });

    dialog.addEventListener('close', () => {
        document.body.classList.remove('confirmation-open');
        pendingTrigger = null;

        if (restoreFocus && lastFocusedElement?.isConnected) {
            lastFocusedElement.focus();
        }

        lastFocusedElement = null;
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootConfirmationDialog, { once: true });
} else {
    bootConfirmationDialog();
}
