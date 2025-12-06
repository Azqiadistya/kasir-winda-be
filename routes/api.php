<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StoreSettingController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::middleware('role:owner')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);

        Route::get('store-settings', [StoreSettingController::class, 'show']);
        Route::put('store-settings', [StoreSettingController::class, 'update']);

        Route::get('sync/pull', [SyncController::class, 'pull'])->middleware('throttle:sync');
        Route::post('sync/push', [SyncController::class, 'push'])->middleware('throttle:sync');
    });
});
