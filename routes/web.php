<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Akses root selalu dimulai dari halaman login dan mengakhiri sesi lama.
Route::get('/', function (Request $request) {
    if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login')->withHeaders([
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('students', StudentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/fingerprint', [AttendanceController::class, 'fingerprintForm'])->name('fingerprint.index');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export', [AttendanceController::class, 'export'])
        ->name('attendance.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
