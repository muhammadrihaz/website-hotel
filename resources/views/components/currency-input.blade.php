@props([
    'model',
    'value' => 0,
    'placeholder' => '0',
    'disabled' => false,
])

@php($normalizedValue = max(0, (int) round((float) $value)))

<div data-currency-field data-currency-model-name="{{ $model }}" {{ $attributes->class('relative') }}>
    <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-sm font-bold text-slate-500">Rp</span>
    <input
        type="text"
        inputmode="numeric"
        autocomplete="off"
        data-currency-input
        value="{{ number_format($normalizedValue, 0, ',', '.') }}"
        placeholder="{{ $placeholder }}"
        class="form-input pl-10 tabular-nums"
        @disabled($disabled)
    >
    <input
        type="hidden"
        data-currency-model
        value="{{ $normalizedValue }}"
        wire:model.live.debounce.250ms="{{ $model }}"
    >
</div>
