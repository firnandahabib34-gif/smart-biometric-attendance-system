<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Smart Biometric Attendance System
|--------------------------------------------------------------------------
|
| Aturan route:
| - Halaman "/" → auto redirect ke login (atau dashboard jika sudah login)
| - Dashboard → hanya bisa diakses setelah login
| - Tidak ada landing page (single admin)
|
*/

// ============================================================
// Route "/" → redirect otomatis
// ============================================================
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

// ============================================================
// Route Dashboard (wajib login)
// ============================================================
Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('students', StudentController::class)->except('show');
    Route::patch('/students/{student}/fingerprint', [StudentController::class, 'registerFingerprint'])
        ->name('students.fingerprint');

    Route::get('/fingerprint', [AttendanceController::class, 'fingerprintForm'])->name('fingerprint.index');
    Route::post('/fingerprint/scan', [AttendanceController::class, 'recordFingerprint'])->name('fingerprint.scan');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/bulk', [AttendanceController::class, 'bulkStore'])->name('attendance.bulk');
    Route::post('/attendance/generate', [AttendanceController::class, 'generateMonth'])
        ->name('attendance.generate');
    Route::get('/attendance/export', [AttendanceController::class, 'export'])
        ->name('attendance.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// ============================================================
// Route Profile (bawaan Breeze)
// ============================================================
Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============================================================
// Route Auth (login, logout, register, dll — bawaan Breeze)
// ============================================================
require __DIR__.'/auth.php';