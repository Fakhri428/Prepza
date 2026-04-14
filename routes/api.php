<?php

use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/intelligence/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'service-b-intelligence',
        'time' => now()->toIso8601String(),
    ]);
});

Route::post('/intelligence/process-orders', function (Request $request) {
    $dryRun = (bool) $request->boolean('dry_run', false);
    $exitCode = Artisan::call('queue:process-orders', [
        '--dry-run' => $dryRun,
    ]);

    return response()->json([
        'status' => $exitCode === 0 ? 'ok' : 'error',
        'exit_code' => $exitCode,
        'dry_run' => $dryRun,
        'output' => Artisan::output(),
    ], $exitCode === 0 ? 200 : 500);
});

Route::get('/intelligence/orders', function () {
    $orders = IntelligenceOrder::query()
        ->with('items')
        ->latest('last_synced_at')
        ->limit(100)
        ->get();

    return response()->json([
        'data' => $orders,
    ]);
});

Route::get('/intelligence/trends', function () {
    $trends = IntelligenceTrend::query()
        ->latest('detected_at')
        ->limit(20)
        ->get();

    return response()->json([
        'data' => $trends,
    ]);
});

if ((bool) env('ENABLE_SERVICE_A_MOCK_ROUTES', false)) {
    require __DIR__.'/api-service-a-mock.php';
}
