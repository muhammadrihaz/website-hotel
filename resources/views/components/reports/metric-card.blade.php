@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'hotel' => 'bg-hotel-50 text-hotel-700 ring-hotel-100',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    ];
@endphp

<article class="panel p-4 sm:p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $value }}</p>
            @if($description)
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $description }}</p>
            @endif
        </div>
        <span class="mt-0.5 h-3 w-3 shrink-0 rounded-full ring-4 {{ $tones[$tone] ?? $tones['slate'] }}"></span>
    </div>
</article>
