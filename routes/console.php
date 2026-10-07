<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-owner {--name=} {--email=}', function () {
    $name = trim((string) ($this->option('name') ?: $this->ask('Nama owner')));
    $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email owner'))));
    $password = (string) $this->secret('Password owner (minimal 12 karakter)');
    $passwordConfirmation = (string) $this->secret('Ulangi password owner');

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $passwordConfirmation,
    ], [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:12', 'confirmed'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return Command::FAILURE;
    }

    $ownerRole = Role::query()
        ->where('name', 'Owner')
        ->where('guard_name', 'web')
        ->first();

    if (! $ownerRole) {
        $this->error('Role Owner belum tersedia. Jalankan "php artisan db:seed --force" terlebih dahulu.');

        return Command::FAILURE;
    }

    DB::transaction(function () use ($name, $email, $password, $ownerRole): void {
        $owner = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($owner->trashed()) {
            $owner->restore();
        }

        $owner->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => $owner->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();

        $owner->syncRoles([$ownerRole]);
    });

    $this->info("Akun Owner {$email} siap digunakan.");

    return Command::SUCCESS;
})->purpose('Create or restore the first production owner account');
