<?php

namespace App\Domains\System\Actions;

use App\Domains\System\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $action,
        string $module,
        Model|string|null $record = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $user = null,
    ): AuditLog {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::query()->create([
            'user_id' => $user?->getKey() ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_type' => $record instanceof Model ? $record::class : null,
            'record_id' => $record instanceof Model ? (string) $record->getKey() : $record,
            'old_values' => $this->sanitize($oldValues) ?: null,
            'new_values' => $this->sanitize($newValues) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /** @param array<string, mixed> $values */
    private function sanitize(array $values): array
    {
        foreach (['password', 'password_confirmation', 'remember_token', 'token'] as $sensitiveKey) {
            unset($values[$sensitiveKey]);
        }

        return $values;
    }
}
