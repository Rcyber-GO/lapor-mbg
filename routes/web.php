<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\ForwardingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportReviewController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDistributionController;
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
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/buat', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/laporan', [ReportController::class, 'store'])->name('reports.store');
        Route::get('/sekolah/{school}/distribusi', SchoolDistributionController::class)
            ->whereNumber('school')
            ->name('schools.distributions');
    });
    Route::get('/laporan-masuk', [ReportController::class, 'incoming'])
        ->middleware('can:viewAny,App\\Models\\Report')
        ->name('reports.incoming');
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
    Route::get('/laporan/{report}/bukti', [ReportController::class, 'evidence'])->whereNumber('report')->name('reports.evidence');

    // Perbaikan laporan oleh pemiliknya.
    Route::middleware('can:update,report')->whereNumber('report')->group(function () {
        Route::get('/laporan/{report}/ubah', [ReportController::class, 'edit'])->name('reports.edit');
        Route::put('/laporan/{report}', [ReportController::class, 'update'])->name('reports.update');
    });

    // Peninjauan oleh Pemantau Komunitas.
    Route::middleware('can:review,report')->whereNumber('report')->group(function () {
        Route::post('/laporan/{report}/status', [ReportReviewController::class, 'updateStatus'])->name('reports.status');
        Route::post('/laporan/{report}/catatan', [ReportReviewController::class, 'storeNote'])->name('reports.notes.store');
    });

    // Pencatatan penerusan oleh Koordinator Komunitas.
    Route::post('/laporan/{report}/penerusan', [ForwardingController::class, 'store'])
        ->middleware('can:forward,report')
        ->whereNumber('report')
        ->name('reports.forwardings.store');

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
