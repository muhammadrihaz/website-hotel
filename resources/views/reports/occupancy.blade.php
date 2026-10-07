@php
    $summary = $reportData['summary'];
    $daily = $reportData['daily'];
    $chartWidth = max(760, $daily->count() * 34);
@endphp

<x-layouts.app title="Occupancy Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header
            active="occupancy"
            :range="$range"
            title="Occupancy Report"
            description="Pantau kamar terisi, ketersediaan room-night, dan tren okupansi berdasarkan reservasi aktif pada periode terpilih."
        />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-reports.metric-card label="Rata-rata okupansi" :value="number_format($summary['average_occupancy'], 1, ',', '.').'%'" description="Rata-rata seluruh hari" tone="hotel" />
            <x-reports.metric-card label="Room-night terjual" :value="number_format($summary['sold_room_nights'], 0, ',', '.')" :description="'Dari '.number_format($summary['room_nights'], 0, ',', '.').' kapasitas'" tone="sky" />
            <x-reports.metric-card label="Room-night tersedia" :value="number_format($summary['available_room_nights'], 0, ',', '.')" description="Kapasitas yang belum terisi" tone="emerald" />
            <x-reports.metric-card label="Puncak okupansi" :value="number_format($summary['peak']['percentage'] ?? 0, 1, ',', '.').'%'" :description="$summary['peak']['date_label'] ?? 'Belum ada data'" tone="violet" />
        </section>

        <section class="panel overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="font-bold text-slate-950">Tren okupansi harian</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Confirmed, checked-in, dan checked-out yang menempati tanggal tersebut</p>
                </div>
                <span class="badge self-start bg-hotel-50 text-hotel-700 sm:self-auto">{{ $summary['total_rooms'] }} kamar aktif</span>
            </div>
            <div class="overflow-x-auto px-5 pb-5 pt-7 sm:px-6">
                <div class="flex h-56 items-end gap-2" style="width: {{ $chartWidth }}px">
                    @foreach($daily as $row)
                        <div class="group flex h-full min-w-6 flex-1 flex-col items-center justify-end gap-2">
                            <div class="relative flex w-full flex-1 items-end justify-center rounded-lg bg-slate-50">
                                <div class="w-full rounded-lg bg-hotel-500 transition group-hover:bg-hotel-600" style="height: {{ max($row['percentage'], 2) }}%" title="{{ $row['date_label'] }}: {{ number_format($row['percentage'], 1, ',', '.') }}%"></div>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400">{{ $row['date_label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="font-bold text-slate-950">Rincian harian</h3>
                <p class="mt-0.5 text-xs text-slate-500">Basis kapasitas menggunakan jumlah kamar aktif saat laporan dibuat.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3 sm:px-6">Tanggal</th><th class="px-5 py-3">Tersedia</th><th class="px-5 py-3">Terisi</th><th class="px-5 py-3 text-right sm:px-6">Okupansi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($daily as $row)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-bold text-slate-800 sm:px-6">{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('l, d M Y') }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $row['available'] }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $row['occupied'] }}</td>
                                <td class="px-5 py-3 text-right sm:px-6"><span class="badge bg-hotel-50 text-hotel-700">{{ number_format($row['percentage'], 1, ',', '.') }}%</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
