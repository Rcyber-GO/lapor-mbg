<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('can:create-report')->group(function () {
        Route::get('/laporan/buat', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/laporan', [ReportController::class, 'store'])->name('reports.store');
    });
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->whereNumber('report')->name('reports.show');

    // Data pendukung hanya dikelola Koordinator Komunitas.
    Route::middleware('can:manage-master-data')->group(function () {
        Route::resource('sekolah', SchoolController::class)
            ->except('show')
            ->parameters(['sekolah' => 'school'])
            ->names('schools');

        Route::resource('distribusi', DistributionController::class)
            ->except('show')
            ->parameters(['distribusi' => 'distribution'])
            ->names('distributions');
    });
});
