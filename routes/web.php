<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntelligenceDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/intelligence', IntelligenceDashboardController::class)->name('intelligence.dashboard');
    Route::post('/intelligence/orders/{order}/status', [IntelligenceDashboardController::class, 'updateStatus'])
        ->name('intelligence.orders.update-status');
});
