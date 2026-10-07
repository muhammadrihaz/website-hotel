<?php

namespace App\Domains\System\Livewire;

use App\Domains\System\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public string $search = '';

    public string $module = '';

    public string $action = '';

    public function mount(): void
    {
        Gate::authorize('audit.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('audit.view');

        return view('livewire.system.audit-log-viewer', [
            'logs' => AuditLog::query()
                ->with('user:id,name')
                ->when($this->module, fn ($query) => $query->where('module', $this->module))
                ->when($this->action, fn ($query) => $query->where('action', $this->action))
                ->when($this->search, fn ($query) => $query->where(function ($query): void {
                    $query->where('record_id', 'like', "%{$this->search}%")
                        ->orWhere('ip_address', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                }))
                ->latest()
                ->paginate(15),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
