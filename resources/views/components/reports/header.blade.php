@props([
    'active',
    'range',
    'title',
    'description',
    'exportable' => true,
])

@php
    $reports = [
        'occupancy' => ['label' => 'Occupancy', 'route' => 'reports.occupancy'],
        'revenue' => ['label' => 'Revenue', 'route' => 'reports.revenue'],
        'reservations' => ['label' => 'Reservations', 'route' => 'reports.reservations'],
        'housekeeping' => ['label' => 'Housekeeping', 'route' => 'reports.housekeeping'],
        'maintenance' => ['label' => 'Maintenance', 'route' => 'reports.maintenance'],
        'inventory' => ['label' => 'Inventory', 'route' => 'reports.inventory'],
    ];
@endphp

<section class="report-no-print space-y-5">
    <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-hotel-700">
                <span class="h-2 w-2 rounded-full bg-hotel-500"></span>
                Reporting & Analytics
            </div>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="btn-secondary gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8V3h10v5M7 17h10v4H7zM5 8h14a2 2 0 012 2v7h-4v-4H7v4H3v-7a2 2 0 012-2z" /></svg>
                Print
            </button>
            @if($exportable && auth()->user()->can('report.export'))
                <a href="{{ route('reports.export', ['report' => $active, ...$range->query()]) }}" class="btn-primary gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14" /></svg>
                    Export CSV
                </a>
            @endif
        </div>
    </header>

    <nav class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm" aria-label="Jenis laporan">
        <div class="flex min-w-max gap-1">
            @foreach($reports as $key => $report)
                <a href="{{ route($report['route'], $range->query()) }}"
                   @class([
                       'rounded-xl px-3.5 py-2 text-sm font-bold transition',
                       'bg-slate-950 text-white shadow-sm' => $active === $key,
                       'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => $active !== $key,
                   ])>
                    {{ $report['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    <form method="GET" action="{{ route('reports.'.$active) }}" class="panel grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
        <label>
            <span class="form-label">Tanggal mulai</span>
            <input type="date" name="start_date" value="{{ $range->start->toDateString() }}" class="form-input" required>
        </label>
        <label>
            <span class="form-label">Tanggal selesai</span>
            <input type="date" name="end_date" value="{{ $range->end->toDateString() }}" class="form-input" required>
        </label>
        <button type="submit" class="btn-primary min-h-[42px] gap-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M7 12h10m-7 6h4" /></svg>
            Terapkan filter
        </button>
    </form>
</section>

<div class="report-print-heading hidden">
    <p class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ config('hotel.property.short_name') }}</p>
    <h1 class="mt-1 text-2xl font-black text-slate-950">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Periode {{ $range->label() }}</p>
</div>
