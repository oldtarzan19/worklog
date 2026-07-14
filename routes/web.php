<?php

use App\Http\Controllers\Admin\RegistrationRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\WorkEntryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('work-entries', WorkEntryController::class)->only(['store', 'update', 'destroy']);
    Route::get('export', [ExportController::class, 'own'])->name('export.own');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('registrations', [RegistrationRequestController::class, 'index'])->name('registrations.index');
        Route::post('registrations/{registrationRequest}/approve', [RegistrationRequestController::class, 'approve'])->name('registrations.approve');
        Route::delete('registrations/{registrationRequest}/reject', [RegistrationRequestController::class, 'reject'])->name('registrations.reject');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('reports', ReportController::class)->name('reports.index');
        Route::get('export', [ExportController::class, 'admin'])->name('export');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
