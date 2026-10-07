@php
    $summary = $reportData['summary'];
    $daily = $reportData['daily'];
    $maxRevenue = max((float) $daily->max('net_revenue'), 1);
    $chartWidth = max(760, $daily->count() * 34);
@endphp

<x-layouts.app title="Revenue Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header
            active="revenue"
            :range="$range"
            title="Revenue Report"
            description="Ringkasan nilai reservasi berdasarkan tanggal check-in. Pembayaran kamar dan deposit jaminan refundable dicatat terpisah."
        />

        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">
            <strong>Basis laporan:</strong> nilai reservasi non-cancelled dan non-no-show. Deposit jaminan tidak dihitung sebagai pendapatan kamar.
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-reports.metric-card label="Net revenue" :value="'Rp'.number_format($summary['net_revenue'], 0, ',', '.')" :description="$summary['reservations'].' reservasi sah'" tone="hotel" />
            <x-reports.metric-card label="Room revenue" :value="'Rp'.number_format($summary['room_revenue'], 0, ',', '.')" description="Nilai kamar sebelum tambahan" tone="sky" />
            <x-reports.metric-card label="Pembayaran kamar" :value="'Rp'.number_format($summary['payment'], 0, ',', '.')" description="Nominal kamar yang sudah dibayar" tone="emerald" />
            <x-reports.metric-card label="Saldo reservasi" :value="'Rp'.number_format($summary['outstanding'], 0, ',', '.')" description="Net revenue dikurangi pembayaran" tone="amber" />
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.7fr)]">
            <div class="panel overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h3 class="font-bold text-slate-950">Tren nilai reservasi</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Net revenue per tanggal check-in</p>
                </div>
                <div class="overflow-x-auto px-5 pb-5 pt-7 sm:px-6">
                    <div class="flex h-56 items-end gap-2" style="width: {{ $chartWidth }}px">
                        @foreach($daily as $row)
                            <div class="group flex h-full min-w-6 flex-1 flex-col items-center justify-end gap-2">
                                <div class="relative flex w-full flex-1 items-end justify-center rounded-lg bg-slate-50">
                                    <div class="w-full rounded-lg bg-sky-500 transition group-hover:bg-sky-600" style="height: {{ max(($row['net_revenue'] / $maxRevenue) * 100, $row['net_revenue'] > 0 ? 4 : 1) }}%" title="{{ $row['date_label'] }}: Rp{{ number_format($row['net_revenue'], 0, ',', '.') }}"></div>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400">{{ $row['date_label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside class="panel p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Komposisi nilai</p>
                <dl class="mt-5 space-y-4">
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-slate-500">Additional revenue</dt><dd class="font-bold text-slate-800">Rp{{ number_format($summary['additional_revenue'], 0, ',', '.') }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-slate-500">Diskon</dt><dd class="font-bold text-rose-600">-Rp{{ number_format($summary['discount'], 0, ',', '.') }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-slate-500">Deposit jaminan</dt><dd class="font-bold text-sky-700">Rp{{ number_format($summary['security_deposit'], 0, ',', '.') }}</dd></div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4"><dt class="text-sm text-slate-500">Rata-rata reservasi</dt><dd class="font-bold text-slate-950">Rp{{ number_format($summary['average_value'], 0, ',', '.') }}</dd></div>
                </dl>
            </aside>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h3 class="font-bold text-slate-950">Rincian pendapatan harian</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1240px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Tanggal check-in</th><th class="px-4 py-3">Reservasi</th><th class="px-4 py-3 text-right">Room</th><th class="px-4 py-3 text-right">Tambahan</th><th class="px-4 py-3 text-right">Diskon</th><th class="px-4 py-3 text-right">Net</th><th class="px-4 py-3 text-right">Pembayaran</th><th class="px-4 py-3 text-right">Deposit jaminan</th><th class="px-5 py-3 text-right sm:px-6">Saldo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($daily as $row)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-bold text-slate-800 sm:px-6">{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $row['reservations'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">Rp{{ number_format($row['room_revenue'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">Rp{{ number_format($row['additional_revenue'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-rose-600">Rp{{ number_format($row['discount'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">Rp{{ number_format($row['net_revenue'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">Rp{{ number_format($row['payment'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sky-700">Rp{{ number_format($row['security_deposit'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right font-bold text-amber-700 sm:px-6">Rp{{ number_format($row['outstanding'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
