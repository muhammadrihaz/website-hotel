@php
    $summary = $reportData['summary'];
    $categories = $reportData['categories'];
    $repeatedRooms = $reportData['repeated_rooms'];
    $maxCategory = max((int) $categories->max('tickets'), 1);
@endphp

<x-layouts.app title="Maintenance Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header active="maintenance" :range="$range" title="Maintenance Report" description="Analisis jumlah tiket, kategori masalah, kecepatan penyelesaian, issue terbuka, dan kamar dengan masalah berulang." />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-reports.metric-card label="Tiket dibuat" :value="$summary['tickets']" description="Masuk pada periode ini" tone="sky" />
            <x-reports.metric-card label="Terselesaikan" :value="$summary['resolved']" description="Resolved pada periode ini" tone="emerald" />
            <x-reports.metric-card label="Open issues" :value="$summary['open_issues']" description="Dari tiket periode ini" tone="rose" />
            <x-reports.metric-card label="Rata-rata resolusi" :value="$summary['average_resolution_hours'] !== null ? number_format($summary['average_resolution_hours'], 1, ',', '.').' jam' : '—'" description="Dari dibuat hingga resolved" tone="violet" />
            <x-reports.metric-card label="Block room" :value="$summary['blocking_rooms']" :description="$summary['repeat_rooms'].' kamar berulang'" tone="amber" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.65fr)]">
            <div class="panel overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h3 class="font-bold text-slate-950">Tiket per kategori</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[660px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Kategori</th><th class="px-5 py-3">Distribusi</th><th class="px-5 py-3">Open</th><th class="px-5 py-3 text-right sm:px-6">Resolved</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($categories as $row)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-3 font-bold text-slate-800 sm:px-6">{{ $row['category'] }}</td>
                                    <td class="px-5 py-3"><div class="flex items-center gap-3"><div class="h-2 w-28 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-violet-500" style="width: {{ ($row['tickets'] / $maxCategory) * 100 }}%"></div></div><strong>{{ $row['tickets'] }}</strong></div></td>
                                    <td class="px-5 py-3 text-rose-700">{{ $row['open'] }}</td>
                                    <td class="px-5 py-3 text-right text-emerald-700 sm:px-6">{{ $row['resolved'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada tiket maintenance pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="panel p-5 sm:p-6">
                <h3 class="font-bold text-slate-950">Repeated room issues</h3>
                <p class="mt-1 text-xs text-slate-500">Kamar dengan lebih dari satu tiket</p>
                <div class="mt-5 space-y-3">
                    @forelse($repeatedRooms as $row)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-3.5">
                            <div><p class="text-sm font-bold text-slate-800">Room {{ $row['room'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $row['open'] }} issue masih open</p></div>
                            <span class="flex h-10 min-w-10 items-center justify-center rounded-xl bg-rose-100 px-2 font-black text-rose-700">{{ $row['issues'] }}</span>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-emerald-50 px-4 py-8 text-center"><p class="font-bold text-emerald-800">Tidak ada issue berulang</p><p class="mt-1 text-xs text-emerald-700">Pada periode terpilih</p></div>
                    @endforelse
                </div>
            </aside>
        </section>
    </div>
</x-layouts.app>
