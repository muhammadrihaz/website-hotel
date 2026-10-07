<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            RoomSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $admin = User::query()->updateOrCreate(
                ['email' => config('hotel.seed.admin_email')],
                [
                    'name' => config('hotel.seed.admin_name'),
                    'password' => config('hotel.seed.admin_password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );
            $admin->syncRoles(['Administrator', 'Manager']);

            $this->call(DemoFrontOfficeSeeder::class);
            $this->call(DemoOperationsSeeder::class);
        }
    }
}
