<div>
    <div class="mb-6"><h2 class="text-2xl font-black text-slate-950">Audit Logs</h2><p class="mt-1 text-sm text-slate-500">Jejak perubahan sensitif bersifat append-only dan tidak memiliki aksi hapus.</p></div>
    <div class="panel mb-5 grid gap-3 p-4 md:grid-cols-3">
        <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="User, record ID, atau IP…">
        <select wire:model.live="module" class="form-input"><option value="">Semua module</option>@foreach($modules as $moduleOption)<option value="{{ $moduleOption }}">{{ str($moduleOption)->replace('_', ' ')->title() }}</option>@endforeach</select>
        <select wire:model.live="action" class="form-input"><option value="">Semua action</option>@foreach($actions as $actionOption)<option value="{{ $actionOption }}">{{ str($actionOption)->headline() }}</option>@endforeach</select>
    </div>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Aktivitas</th><th class="px-5 py-3">Record</th><th class="px-5 py-3">IP</th><th class="px-5 py-3">Perubahan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr wire:key="audit-{{ $log->id }}" class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4"><p class="font-semibold text-slate-800">{{ $log->created_at->format('d M Y') }}</p><p class="text-xs text-slate-500">{{ $log->created_at->format('H:i:s') }} WIB</p></td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $log->user->name }}</td>
                            <td class="px-5 py-4"><span class="badge bg-hotel-50 text-hotel-700">{{ str($log->action)->headline() }}</span><p class="mt-1 text-xs text-slate-500">{{ str($log->module)->replace('_', ' ')->title() }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ $log->record_id ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="max-w-sm px-5 py-4">
                                @if($log->old_values || $log->new_values)
                                    <details><summary class="cursor-pointer text-xs font-semibold text-hotel-700">Lihat detail</summary><div class="mt-2 grid gap-2"><pre class="max-h-48 overflow-auto rounded-lg bg-slate-950 p-3 text-[11px] text-slate-200">{{ json_encode(['before' => $log->old_values, 'after' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">Belum ada audit log yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>@endif
    </section>
</div>
