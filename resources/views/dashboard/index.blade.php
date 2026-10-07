<x-layouts.app title="Dashboard">
    @php
        $total = (int) ($roomMetrics->total ?? 0);
        $occupied = (int) ($roomMetrics->occupied ?? 0);
        $available = (int) ($roomMetrics->available ?? 0);
        $dirty = (int) ($roomMetrics->dirty ?? 0);
        $cleaning = (int) ($roomMetrics->cleaning ?? 0);
        $maintenance = (int) ($roomMetrics->maintenance ?? 0);
        $occupancy = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;
        $occupancyBar = min(max($occupancy, 0), 100);

        $expectedArrivals = (int) ($reservationMetrics->expected_arrivals ?? 0);
        $expectedDepartures = (int) ($reservationMetrics->expected_departures ?? 0);
        $checkinsToday = (int) ($reservationMetrics->checkins_today ?? 0);
        $checkoutsToday = (int) ($reservationMetrics->checkouts_today ?? 0);
        $outstanding = (float) ($reservationMetrics->outstanding ?? 0);

        $roomStatuses = [
            [
                'label' => 'Tersedia',
                'description' => 'Siap dijual',
                'value' => $available,
                'dot' => 'bg-emerald-500',
                'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ],
            [
                'label' => 'Kamar kotor',
                'description' => 'Perlu dibersihkan',
                'value' => $dirty,
                'dot' => 'bg-amber-500',
                'tone' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ],
            [
                'label' => 'Sedang dibersihkan',
                'description' => 'Dalam proses',
                'value' => $cleaning,
                'dot' => 'bg-sky-500',
                'tone' => 'bg-sky-50 text-sky-700 ring-sky-100',
            ],
            [
                'label' => 'Tidak tersedia',
                'description' => 'Maintenance / OOO',
                'value' => $maintenance,
                'dot' => 'bg-rose-500',
                'tone' => 'bg-rose-50 text-rose-700 ring-rose-100',
            ],
        ];

        $movements = [
            [
                'label' => 'Akan check-in',
                'description' => 'Menunggu kedatangan',
                'value' => $expectedArrivals,
                'tone' => 'bg-sky-50 text-sky-700',
                'icon' => 'arrival',
            ],
            [
                'label' => 'Check-in selesai',
                'description' => 'Diproses hari ini',
                'value' => $checkinsToday,
                'tone' => 'bg-indigo-50 text-indigo-700',
                'icon' => 'checkin',
            ],
            [
                'label' => 'Akan check-out',
                'description' => 'Jadwal check-out',
                'value' => $expectedDepartures,
                'tone' => 'bg-violet-50 text-violet-700',
                'icon' => 'departure',
            ],
            [
                'label' => 'Check-out selesai',
                'description' => 'Diproses hari ini',
                'value' => $checkoutsToday,
                'tone' => 'bg-teal-50 text-teal-700',
                'icon' => 'checkout',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-[1600px] space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-hotel-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Operasional hari ini
                </div>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Ringkasan hotel</h2>
                <p class="mt-1 text-sm text-slate-500">Status kamar dan pergerakan Front Office dalam satu tampilan.</p>
            </div>

            <div class="flex items-center gap-3 self-start rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:self-auto">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-hotel-50 text-hotel-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2v3m8-3v3M3.5 9.5h17M5 4h14a1.5 1.5 0 011.5 1.5v14A1.5 1.5 0 0119 21H5a1.5 1.5 0 01-1.5-1.5v-14A1.5 1.5 0 015 4z" />
                    </svg>
                </span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tanggal bisnis</p>
                    <p class="text-sm font-bold text-slate-800">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>
        </header>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.6fr)]">
            <div class="relative overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-7">
                <div class="absolute -right-16 -top-20 h-52 w-52 rounded-full border-[34px] border-hotel-500/10" aria-hidden="true"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-hotel-300">Okupansi</p>
                            <p class="mt-3 text-5xl font-black tracking-tight sm:text-6xl">{{ number_format($occupancy, 1, ',', '.') }}<span class="text-2xl text-slate-400">%</span></p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-hotel-300 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9.5A1.5 1.5 0 015.5 8H18a2 2 0 012 2v9M4 15h16M7 8V5.5A1.5 1.5 0 018.5 4h3A1.5 1.5 0 0113 5.5V8M4 19v2m16-2v2" />
                            </svg>
                        </span>
                    </div>

                    <div class="mt-7 h-2 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-hotel-400" style="width: {{ $occupancyBar }}%"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span class="text-slate-400">Kamar terisi</span>
                        <strong>{{ $occupied }} dari {{ $total }} kamar</strong>
                    </div>

                    <div class="mt-7 grid grid-cols-2 gap-3 border-t border-white/10 pt-5">
                        <div>
                            <p class="text-xs text-slate-400">Total kamar</p>
                            <p class="mt-1 text-xl font-black">{{ $total }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Siap dijual</p>
                            <p class="mt-1 text-xl font-black text-emerald-300">{{ $available }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-slate-950">Kesiapan kamar</h3>
                        <p class="mt-1 text-xs text-slate-500">Kondisi inventori kamar saat ini</p>
                    </div>
                    @can('room.view')
                        <a href="{{ route('front-office.room-board.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-hotel-700 transition hover:text-hotel-900">
                            Room Board
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @endcan
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($roomStatuses as $status)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $status['dot'] }}"></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-800">{{ $status['label'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $status['description'] }}</p>
                                </div>
                            </div>
                            <span class="ml-3 inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-2 text-lg font-black ring-1 {{ $status['tone'] }}">{{ $status['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-end justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-950">Pergerakan hari ini</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Arrival dan departure yang perlu dipantau</p>
                </div>
                @can('reservation.view')
                    <a href="{{ route('front-office.reservations.index') }}" class="text-sm font-bold text-hotel-700 hover:text-hotel-900">Lihat reservasi</a>
                @endcan
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($movements as $movement)
                    <div class="panel flex items-center gap-4 p-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $movement['tone'] }}">
                            @if(in_array($movement['icon'], ['arrival', 'checkin'], true))
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14" /></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21V9m0 0L8 13m4-4l4 4M5 5h14" /></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $movement['label'] }}</p>
                                <strong class="text-2xl font-black tracking-tight text-slate-950">{{ $movement['value'] }}</strong>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $movement['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if(auth()->user()->canAny(['housekeeping.view', 'maintenance.view', 'guest_request.view', 'shift_handover.view']))
            <section>
                <div class="mb-3">
                    <h3 class="font-bold text-slate-950">Perhatian operasional</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Pekerjaan aktif yang perlu ditindaklanjuti lintas shift</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @can('housekeeping.view')
                        <a href="{{ route('operations.housekeeping.index') }}" class="panel group flex items-center justify-between p-4 transition hover:border-hotel-200 hover:bg-hotel-50/40">
                            <div><p class="text-sm font-bold text-slate-800">Housekeeping</p><p class="mt-1 text-xs text-slate-500">Task belum verified</p></div>
                            <span class="flex h-11 min-w-11 items-center justify-center rounded-xl bg-amber-50 px-2 text-xl font-black text-amber-700 group-hover:bg-amber-100">{{ $operationsMetrics['housekeeping'] }}</span>
                        </a>
                    @endcan
                    @can('maintenance.view')
                        <a href="{{ route('operations.maintenance.index') }}" class="panel group flex items-center justify-between p-4 transition hover:border-hotel-200 hover:bg-hotel-50/40">
                            <div><p class="text-sm font-bold text-slate-800">Maintenance</p><p class="mt-1 text-xs text-slate-500">Issue aktif</p></div>
                            <span class="flex h-11 min-w-11 items-center justify-center rounded-xl bg-rose-50 px-2 text-xl font-black text-rose-700 group-hover:bg-rose-100">{{ $operationsMetrics['maintenance'] }}</span>
                        </a>
                    @endcan
                    @can('guest_request.view')
                        <a href="{{ route('operations.guest-requests.index') }}" class="panel group flex items-center justify-between p-4 transition hover:border-hotel-200 hover:bg-hotel-50/40">
                            <div><p class="text-sm font-bold text-slate-800">Guest Requests</p><p class="mt-1 text-xs text-slate-500">Request belum selesai</p></div>
                            <span class="flex h-11 min-w-11 items-center justify-center rounded-xl bg-sky-50 px-2 text-xl font-black text-sky-700 group-hover:bg-sky-100">{{ $operationsMetrics['guest_requests'] }}</span>
                        </a>
                    @endcan
                    @can('shift_handover.view')
                        <a href="{{ route('staff.shift-handover.index') }}" class="panel group flex items-center justify-between p-4 transition hover:border-hotel-200 hover:bg-hotel-50/40">
                            <div><p class="text-sm font-bold text-slate-800">Shift Handover</p><p class="mt-1 text-xs text-slate-500">Item unfinished</p></div>
                            <span class="flex h-11 min-w-11 items-center justify-center rounded-xl bg-violet-50 px-2 text-xl font-black text-violet-700 group-hover:bg-violet-100">{{ $operationsMetrics['handover_items'] }}</span>
                        </a>
                    @endcan
                </div>
            </section>
        @endif

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.75fr)]">
            <section class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <h3 class="font-bold text-slate-950">Aktivitas terbaru</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Perubahan penting yang tercatat di sistem</p>
                    </div>
                    @can('audit.view')
                        <a href="{{ route('system.audit-logs.index') }}" class="text-sm font-bold text-hotel-700 hover:text-hotel-900">Lihat semua</a>
                    @endcan
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentActivities as $activity)
                        <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-hotel-50 text-xs font-black uppercase text-hotel-700">
                                {{ str($activity->module)->substr(0, 2) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-800">{{ str($activity->module)->replace('_', ' ')->title() }} <span class="font-medium text-slate-400">&middot;</span> {{ str($activity->action)->headline() }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $activity->user->name }}</p>
                            </div>
                            <time class="shrink-0 text-xs font-medium text-slate-400" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center text-sm text-slate-500">Belum ada aktivitas tercatat.</div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-orange-700">Pembayaran tertunggak</p>
                            <p class="mt-2 text-2xl font-black tracking-tight text-orange-950">Rp{{ number_format($outstanding, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs text-orange-700">Saldo reservasi aktif yang belum lunas</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/70 text-orange-700 ring-1 ring-orange-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5h16v11H4zM4 10h16M16 15h1" /></svg>
                        </span>
                    </div>
                </div>

                <section class="panel p-5">
                    <h3 class="font-bold text-slate-950">Akses cepat</h3>
                    <p class="mt-1 text-xs text-slate-500">Pekerjaan Front Office yang paling sering digunakan</p>

                    <div class="mt-4 space-y-2">
                        @can('room.view')
                            <a href="{{ route('front-office.room-board.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-hotel-200 hover:bg-hotel-50">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-hotel-900">Buka Room Board</span>
                                <svg class="h-4 w-4 text-slate-400 group-hover:text-hotel-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        @endcan
                        @can('checkin.execute')
                            <a href="{{ route('front-office.check-in.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-hotel-200 hover:bg-hotel-50">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-hotel-900">Proses check-in</span>
                                <span class="badge bg-sky-100 text-sky-700">{{ $expectedArrivals }}</span>
                            </a>
                        @endcan
                        @can('checkout.execute')
                            <a href="{{ route('front-office.check-out.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-100 px-3.5 py-3 transition hover:border-hotel-200 hover:bg-hotel-50">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-hotel-900">Proses check-out</span>
                                <span class="badge bg-violet-100 text-violet-700">{{ $expectedDepartures }}</span>
                            </a>
                        @endcan
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-xs">
                        <span class="text-slate-500">Master aktif</span>
                        <strong class="text-slate-700">{{ $roomTypeCount }} tipe &middot; {{ $activeUserCount }} user</strong>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
