<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    public const PERMISSIONS = [
        'dashboard.view',
        'reservation.view', 'reservation.create', 'reservation.update', 'reservation.cancel',
        'guest.view', 'guest.create', 'guest.update',
        'room.view', 'room.create', 'room.update', 'room.delete', 'room.update_status',
        'room_type.view', 'room_type.create', 'room_type.update', 'room_type.delete',
        'checkin.execute', 'checkout.execute',
        'payment.view', 'payment.create', 'payment.refund', 'payment.void',
        'housekeeping.view', 'housekeeping.update', 'housekeeping.assign', 'housekeeping.verify',
        'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.assign', 'maintenance.verify',
        'guest_request.view', 'guest_request.create', 'guest_request.update', 'guest_request.assign',
        'inventory.view', 'inventory.create', 'inventory.adjust',
        'cashier.view', 'cashier.open', 'cashier.close',
        'shift_handover.view', 'shift_handover.create', 'shift_handover.update',
        'lost_found.view', 'lost_found.create', 'lost_found.update',
        'report.view', 'report.export',
        'user.view', 'user.create', 'user.update', 'user.deactivate', 'user.assign_role', 'user.assign_owner',
        'role.view', 'role.create', 'role.update', 'role.delete',
        'permission.view', 'settings.manage', 'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = collect(self::PERMISSIONS);
        $system = $all->filter(fn (string $permission) => str($permission)->startsWith([
            'dashboard.', 'user.', 'role.', 'permission.', 'settings.', 'audit.', 'room.', 'room_type.',
        ]))->reject(fn (string $permission) => $permission === 'user.assign_owner')->all();

        $roles = [
            'Owner' => $all->all(),
            'Manager' => $all->reject(fn (string $permission) => str($permission)->startsWith([
                'user.', 'role.', 'permission.', 'settings.',
            ]))->all(),
            'Receptionist' => $all->filter(fn (string $permission) => str($permission)->startsWith([
                'dashboard.', 'reservation.', 'guest.', 'room.view', 'checkin.', 'checkout.',
                'payment.view', 'payment.create', 'cashier.view', 'shift_handover.', 'guest_request.', 'lost_found.',
            ]))->all(),
            'Housekeeping' => $all->filter(fn (string $permission) => str($permission)->startsWith([
                'dashboard.', 'room.view', 'room.update_status', 'housekeeping.', 'inventory.view', 'guest_request.view',
            ]))->reject(fn (string $permission) => $permission === 'housekeeping.verify')->all(),
            'Maintenance' => $all->filter(fn (string $permission) => str($permission)->startsWith([
                'dashboard.', 'room.view', 'room.update_status', 'maintenance.', 'inventory.view', 'guest_request.view',
            ]))->reject(fn (string $permission) => $permission === 'maintenance.verify')->all(),
            'Finance' => $all->filter(fn (string $permission) => str($permission)->startsWith([
                'dashboard.', 'reservation.view', 'guest.view', 'payment.', 'cashier.', 'report.',
            ]))->all(),
            'Administrator' => $system,
        ];

        foreach ($roles as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
