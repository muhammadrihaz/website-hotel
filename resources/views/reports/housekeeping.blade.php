@php
    $summary = $reportData['summary'];
    $daily = $reportData['daily'];
    $productivity = $reportData['productivity'];
    $dailyMax = max((int) $daily->max(fn (array $row) => max($row['created'], $row['completed'])), 1);
    $chartWidth = max(760, $daily->count() * 34);
@endphp

<x-layouts.app title="Housekeeping Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header active="housekeeping" :range="$range" title="Housekeeping Report" description="Ukur jumlah kamar dibersihkan, waktu pengerjaan, verification, dan produktivitas staf housekeeping." />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-reports.metric-card label="Task dibuat" :value="$summary['tasks_created']" description="Masuk pada periode ini" tone="sky" />
            <x-reports.metric-card label="Kamar dibersihkan" :value="$summary['rooms_cleaned']" description="Completion pada periode ini" tone="hotel" />
            <x-reports.metric-card label="Verified" :value="$summary['verified']" description="Sudah diperiksa supervisor" tone="emerald" />
            <x-reports.metric-card label="Rata-rata waktu" :value="$summary['average_minutes'] !== null ? $summary['average_minutes'].' menit' : '—'" description="Dari start sampai complete" tone="violet" />
            <x-reports.metric-card label="Task aktif" :value="$summary['active_now']" description="Belum verified saat ini" tone="amber" />
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="font-bold text-slate-950">Arus pekerjaan harian</h3>
                <p class="mt-0.5 text-xs text-slate-500"><span class="font-bold text-sky-600">Biru</span> task dibuat · <span class="font-bold text-hotel-700">Hijau</span> task selesai</p>
            </div>
            <div class="overflow-x-auto px-5 pb-5 pt-7 sm:px-6">
                <div class="flex h-56 items-end gap-2" style="width: {{ $chartWidth }}px">
                    @foreach($daily as $row)
                        <div class="flex h-full min-w-6 flex-1 flex-col items-center justify-end gap-2">
                            <div class="flex w-full flex-1 items-end justify-center gap-0.5 rounded-lg bg-slate-50 px-0.5">
                                <div class="w-1/2 rounded-t bg-sky-400" style="height: {{ max(($row['created'] / $dailyMax) * 100, $row['created'] > 0 ? 5 : 1) }}%" title="Dibuat: {{ $row['created'] }}"></div>
                                <div class="w-1/2 rounded-t bg-hotel-500" style="height: {{ max(($row['completed'] / $dailyMax) * 100, $row['completed'] > 0 ? 5 : 1) }}%" title="Selesai: {{ $row['completed'] }}"></div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400">{{ $row['date_label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h3 class="font-bold text-slate-950">Produktivitas staf</h3><p class="mt-0.5 text-xs text-slate-500">Berdasarkan task yang completed pada periode terpilih</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Staff</th><th class="px-5 py-3">Kamar dibersihkan</th><th class="px-5 py-3">Rata-rata waktu</th><th class="px-5 py-3 text-right sm:px-6">Verified</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productivity as $row)
                            <tr class="hover:bg-slate-50/70"><td class="px-5 py-3 font-bold text-slate-800 sm:px-6">{{ $row['staff'] }}</td><td class="px-5 py-3 text-slate-600">{{ $row['rooms_cleaned'] }}</td><td class="px-5 py-3 text-slate-600">{{ $row['average_minutes'] !== null ? $row['average_minutes'].' menit' : '—' }}</td><td class="px-5 py-3 text-right sm:px-6"><span class="badge bg-emerald-50 text-emerald-700">{{ $row['verified'] }}</span></td></tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada task yang selesai pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
