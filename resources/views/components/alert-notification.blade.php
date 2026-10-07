@php
    $successMessage = session('status');
    $errorMessage = session('error');
    $message = $successMessage ?? $errorMessage ?? ($errors->any() ? $errors->first() : null);
    $type = $successMessage ? 'success' : 'error';
    $title = $type === 'success' ? 'Berhasil' : 'Tindakan belum berhasil';
@endphp

@if(filled($message))
    <div
        class="pointer-events-none fixed inset-x-4 top-20 z-[100] flex justify-end sm:inset-x-auto sm:right-6 sm:w-full sm:max-w-sm"
        aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}"
        aria-atomic="true"
        wire:key="alert-notification-{{ md5($type.'|'.$message) }}"
    >
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave-end="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            role="{{ $type === 'error' ? 'alert' : 'status' }}"
            data-alert-notification
            data-alert-type="{{ $type }}"
            @class([
                'pointer-events-auto relative w-full overflow-hidden rounded-2xl border bg-white shadow-2xl shadow-slate-900/15',
                'border-emerald-200' => $type === 'success',
                'border-rose-200' => $type === 'error',
            ])
        >
            <div class="flex items-start gap-3 p-4 pr-12">
                <div @class([
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-emerald-100 text-emerald-700' => $type === 'success',
                    'bg-rose-100 text-rose-700' => $type === 'error',
                ])>
                    @if($type === 'success')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" />
                        </svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M12 8v4m0 4h.01M10.3 4.5 3.4 16.45A2 2 0 0 0 5.13 19h13.74a2 2 0 0 0 1.73-2.55L13.7 4.5a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                        </svg>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p @class([
                        'text-sm font-bold',
                        'text-emerald-900' => $type === 'success',
                        'text-rose-900' => $type === 'error',
                    ])>{{ $title }}</p>
                    <p class="mt-0.5 text-sm leading-5 text-slate-600">{{ $message }}</p>
                </div>
            </div>

            <button
                type="button"
                class="absolute right-3 top-3 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:ring-2 focus:ring-slate-200"
                @click="show = false"
                aria-label="Tutup notifikasi"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" stroke-width="2" />
                </svg>
            </button>

            <div @class([
                'notification-progress h-1 w-full',
                'bg-emerald-500' => $type === 'success',
                'bg-rose-500' => $type === 'error',
            ])></div>
        </div>
    </div>
@endif
