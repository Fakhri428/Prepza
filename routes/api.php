<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

if ((bool) env('ENABLE_SERVICE_A_MOCK_ROUTES', false)) {
    require __DIR__.'/api-service-a-mock.php';
}
