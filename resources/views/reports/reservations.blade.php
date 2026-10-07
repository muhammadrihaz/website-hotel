@php
    $summary = $reportData['summary'];
    $sources = $reportData['sources'];
    $maxSource = max((int) $sources->max('total'), 1);
@endphp

<x-layouts.app title="Reservation Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header active="reservations" :range="$range" title="Reservation Report" description="Analisis volume reservasi baru, conversion ke check-in, cancellation, no-show, dan distribusi booking source." />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-reports.metric-card label="Reservasi baru" :value="$summary['total']" description="Dibuat pada periode ini" tone="hotel" />
            <x-reports.metric-card label="Confirmed" :value="$summary['confirmed']" description="Menunggu kedatangan" tone="sky" />
            <x-reports.metric-card label="Check-in" :value="$summary['check_ins']" description="Aktual pada periode ini" tone="emerald" />
            <x-reports.metric-card label="Cancelled" :value="$summary['cancelled']" :description="number_format($summary['cancellation_rate'], 1, ',', '.').'% dari booking baru'" tone="rose" />
            <x-reports.metric-card label="No show" :value="$summary['no_show']" description="Tidak datang" tone="amber" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]">
            <div class="panel p-5 sm:p-6">
                <h3 class="font-bold text-slate-950">Distribusi booking source</h3>
                <p class="mt-1 text-xs text-slate-500">Kontribusi channel terhadap booking baru</p>
                <div class="mt-6 space-y-5">
                    @forelse($sources as $row)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3"><span class="text-sm font-bold text-slate-700">{{ $row['label'] }}</span><span class="text-sm font-black text-slate-950">{{ $row['total'] }}</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-hotel-500" style="width: {{ ($row['total'] / $maxSource) * 100 }}%"></div></div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">Belum ada reservasi baru pada periode ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h3 class="font-bold text-slate-950">Performa per channel</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Booking source</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Confirmed</th><th class="px-4 py-3">Check-in</th><th class="px-4 py-3">Cancelled</th><th class="px-5 py-3 sm:px-6">No show</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sources as $row)
                                <tr class="hover:bg-slate-50/70"><td class="px-5 py-3 font-bold text-slate-800 sm:px-6">{{ $row['label'] }}</td><td class="px-4 py-3">{{ $row['total'] }}</td><td class="px-4 py-3 text-sky-700">{{ $row['confirmed'] }}</td><td class="px-4 py-3 text-emerald-700">{{ $row['checked_in'] }}</td><td class="px-4 py-3 text-rose-700">{{ $row['cancelled'] }}</td><td class="px-5 py-3 text-amber-700 sm:px-6">{{ $row['no_show'] }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Tidak ada data untuk ditampilkan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
