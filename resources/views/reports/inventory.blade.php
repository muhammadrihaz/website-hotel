<x-layouts.app title="Inventory Report">
    <div class="report-page mx-auto max-w-[1600px] space-y-6">
        <x-reports.header active="inventory" :range="$range" title="Inventory Report" description="Laporan stok, pemakaian, low stock, dan adjustment akan menggunakan histori transaksi inventory agar setiap angka dapat diaudit." :exportable="false" />

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="grid lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                <div class="relative overflow-hidden bg-slate-950 p-7 text-white sm:p-10">
                    <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full border-[34px] border-hotel-500/10" aria-hidden="true"></div>
                    <div class="relative">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-hotel-500/15 text-hotel-300 ring-1 ring-hotel-400/20">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7l8-4 8 4-8 4-8-4zm0 0v10l8 4 8-4V7M12 11v10" /></svg>
                        </span>
                        <p class="mt-6 text-xs font-bold uppercase tracking-[0.16em] text-hotel-300">Data source belum tersedia</p>
                        <h3 class="mt-2 text-2xl font-black">Inventory belum diaktifkan</h3>
                        <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">Project saat ini belum memiliki master item dan inventory transaction. Laporan sengaja tidak menampilkan angka dummy agar keputusan stok tetap aman.</p>
                    </div>
                </div>
                <div class="p-7 sm:p-10">
                    <h3 class="text-lg font-black text-slate-950">Data yang akan tersedia</h3>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach([
                            ['Current Stock', 'Saldo stok per item dan kategori'],
                            ['Stock Usage', 'Pemakaian selama periode laporan'],
                            ['Low Stock', 'Item di bawah minimum stock'],
                            ['Adjustment', 'Seluruh koreksi dengan user dan alasan'],
                        ] as [$title, $description])
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-hotel-500"></span><p class="text-sm font-bold text-slate-800">{{ $title }}</p></div>
                                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-6 rounded-2xl bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900"><strong>Langkah berikutnya:</strong> implementasi Phase 5 Inventory diperlukan sebelum report ini dapat dihitung dan diekspor.</p>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
