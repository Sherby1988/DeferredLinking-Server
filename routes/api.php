<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\DeferredLinkController;
use App\Http\Controllers\Api\LinkController;
use Illuminate\Support\Facades\Route;

// Admin routes (X-Admin-Key)
Route::middleware('admin.key')->group(function () {
    Route::post('/apps', [AppController::class, 'store']);
    Route::get('/apps/{id}', [AppController::class, 'show']);
    Route::put('/apps/{id}', [AppController::class, 'update']);
    Route::delete('/apps/{id}', [AppController::class, 'destroy']);
});

// App API routes (X-Api-Key)
Route::middleware('api.key')->group(function () {
    Route::get('/links', [LinkController::class, 'index']);
    Route::post('/links', [LinkController::class, 'store']);
    Route::get('/links/{code}', [LinkController::class, 'show']);
    Route::delete('/links/{code}', [LinkController::class, 'destroy']);
    Route::get('/links/{code}/analytics', [AnalyticsController::class, 'linkStats']);
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
    Route::post('/deferred/resolve', [DeferredLinkController::class, 'resolve']);
});

// Internal beacon (no auth)
Route::post('/internal/fingerprint-update', [DeferredLinkController::class, 'fingerprintUpdate']);
