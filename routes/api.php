<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckLogController;
use App\Http\Controllers\Api\DomainCheckController;
use App\Http\Controllers\Api\DomainController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:6,1'])->post('/register', [AuthController::class, 'register']);
Route::middleware(['throttle:12,1'])->post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/domains', [DomainController::class, 'index']);
    Route::post('/domains', [DomainController::class, 'store']);
    Route::get('/domains/{domain}', [DomainController::class, 'show']);
    Route::patch('/domains/{domain}', [DomainController::class, 'update']);
    Route::delete('/domains/{domain}', [DomainController::class, 'destroy']);

    Route::get('/domains/{domain}/checks', [DomainCheckController::class, 'index']);
    Route::post('/domains/{domain}/checks', [DomainCheckController::class, 'store']);
    Route::patch('/domain-checks/{check}', [DomainCheckController::class, 'update']);
    Route::delete('/domain-checks/{check}', [DomainCheckController::class, 'destroy']);

    Route::get('/domain-checks/{check}/logs', [CheckLogController::class, 'index']);

    Route::middleware(['superuser'])->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}', [AdminUserController::class, 'update']);
    });
});
