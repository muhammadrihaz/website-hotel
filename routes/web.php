<?php

use App\Domains\Dashboard\Http\Controllers\DashboardController;
use App\Domains\Reporting\Http\Controllers\ReportController;
use App\Domains\System\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('front-office')->name('front-office.')->group(function (): void {
        Route::view('/reservations', 'pages.front-office.reservations')
            ->middleware('permission:reservation.view')
            ->name('reservations.index');
        Route::view('/room-board', 'pages.front-office.room-board')
            ->middleware('permission:room.view')
            ->name('room-board.index');
        Route::view('/check-in', 'pages.front-office.check-in')
            ->middleware('permission:checkin.execute')
            ->name('check-in.index');
        Route::view('/in-house', 'pages.front-office.in-house')
            ->middleware('permission:reservation.view')
            ->name('in-house.index');
        Route::view('/check-out', 'pages.front-office.check-out')
            ->middleware('permission:checkout.execute')
            ->name('check-out.index');
        Route::view('/guests', 'pages.front-office.guests')
            ->middleware('permission:guest.view')
            ->name('guests.index');
    });

    Route::prefix('operations')->name('operations.')->group(function (): void {
        Route::view('/housekeeping', 'pages.operations.housekeeping')
            ->middleware('permission:housekeeping.view')
            ->name('housekeeping.index');
        Route::view('/guest-requests', 'pages.operations.guest-requests')
            ->middleware('permission:guest_request.view')
            ->name('guest-requests.index');
        Route::view('/maintenance', 'pages.operations.maintenance')
            ->middleware('permission:maintenance.view')
            ->name('maintenance.index');
        Route::view('/lost-found', 'pages.operations.lost-found')
            ->middleware('permission:lost_found.view')
            ->name('lost-found.index');
    });

    Route::prefix('staff')->name('staff.')->group(function (): void {
        Route::view('/shift-handover', 'pages.staff.shift-handover')
            ->middleware('permission:shift_handover.view')
            ->name('shift-handover.index');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:report.view')->group(function (): void {
        Route::get('/occupancy', [ReportController::class, 'show'])->defaults('report', 'occupancy')->name('occupancy');
        Route::get('/revenue', [ReportController::class, 'show'])->defaults('report', 'revenue')->name('revenue');
        Route::get('/reservations', [ReportController::class, 'show'])->defaults('report', 'reservations')->name('reservations');
        Route::get('/housekeeping', [ReportController::class, 'show'])->defaults('report', 'housekeeping')->name('housekeeping');
        Route::get('/maintenance', [ReportController::class, 'show'])->defaults('report', 'maintenance')->name('maintenance');
        Route::get('/inventory', [ReportController::class, 'show'])->defaults('report', 'inventory')->name('inventory');
        Route::get('/{report}/export', [ReportController::class, 'export'])
            ->middleware('permission:report.export')
            ->whereIn('report', ['occupancy', 'revenue', 'reservations', 'housekeeping', 'maintenance'])
            ->name('export');
    });

    Route::prefix('system')->name('system.')->group(function (): void {
        Route::view('/users', 'pages.system.users')
            ->middleware('permission:user.view')
            ->name('users.index');
        Route::view('/roles', 'pages.system.roles')
            ->middleware('permission:role.view')
            ->name('roles.index');
        Route::view('/room-types', 'pages.rooms.room-types')
            ->middleware('permission:room_type.view')
            ->name('room-types.index');
        Route::view('/rooms', 'pages.rooms.rooms')
            ->middleware('permission:room.view')
            ->name('rooms.index');
        Route::view('/audit-logs', 'pages.system.audit-logs')
            ->middleware('permission:audit.view')
            ->name('audit-logs.index');
    });
});
