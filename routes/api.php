<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->prefix('device/fingerprint')->group(function () {
    Route::post('/scan', [AttendanceController::class, 'recordDeviceFingerprint'])
        ->name('device.fingerprint.scan');
    Route::post('/enroll', [AttendanceController::class, 'registerDeviceFingerprint'])
        ->name('device.fingerprint.enroll');
});
