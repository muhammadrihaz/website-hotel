<dialog
    class="confirmation-dialog"
    data-confirm-dialog
    data-tone="warning"
    aria-labelledby="confirmation-dialog-title"
    aria-describedby="confirmation-dialog-message"
>
    <section class="confirmation-dialog-card">
        <div class="confirmation-dialog-accent" aria-hidden="true"></div>

        <div class="confirmation-dialog-content">
            <div class="confirmation-dialog-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 8v4m0 4h.01M10.3 4.5 3.4 16.45A2 2 0 0 0 5.13 19h13.74a2 2 0 0 0 1.73-2.55L13.7 4.5a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <p class="confirmation-dialog-kicker">Konfirmasi tindakan</p>
                <h2 id="confirmation-dialog-title" class="confirmation-dialog-title" data-confirm-dialog-title>Apakah Anda yakin?</h2>
                <p id="confirmation-dialog-message" class="confirmation-dialog-message" data-confirm-dialog-message>Tindakan ini akan mengubah data operasional.</p>
            </div>
        </div>

        <div class="confirmation-dialog-actions">
            <button type="button" class="confirmation-dialog-cancel" data-confirm-dialog-cancel autofocus>Batal</button>
            <button type="button" class="confirmation-dialog-confirm" data-confirm-dialog-accept>Ya, lanjutkan</button>
        </div>
    </section>
</dialog>
