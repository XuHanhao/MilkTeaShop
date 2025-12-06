<?php

use App\Http\Controllers\Api\V1\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Customer\FavoriteController;
use App\Http\Controllers\Api\V1\Customer\BalanceController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\Staff\InventoryController as StaffInventoryController;
use App\Http\Controllers\Api\V1\Staff\OrderController as StaffOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('auth/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
    Route::put('auth/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

    Route::get('announcements', [AdminAnnouncementController::class, 'publicIndex']);

    Route::get('menu/categories', [CategoryController::class, 'index']);
    Route::get('menu/products', [ProductController::class, 'index']);
    Route::get('menu/products/{product}', [ProductController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('member/favorites', [FavoriteController::class, 'store']);
        Route::get('member/favorites', [FavoriteController::class, 'index']);
        Route::delete('member/favorites/{product}', [FavoriteController::class, 'destroy']);

        Route::get('member/balance', [BalanceController::class, 'show']);
        Route::post('member/balance/recharge', [BalanceController::class, 'recharge']);

        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::post('orders', [CustomerOrderController::class, 'store']);
        Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
    });

    Route::prefix('staff')->middleware(['auth:sanctum', 'role:staff|manager|admin'])->group(function (): void {
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);
        Route::get('orders', [StaffOrderController::class, 'index']);
        Route::post('orders/{order}/status', [StaffOrderController::class, 'updateStatus']);

        Route::get('inventory', [StaffInventoryController::class, 'index']);
        Route::post('inventory', [StaffInventoryController::class, 'store']);
        Route::post('inventory/{inventory}/adjust', [StaffInventoryController::class, 'adjust']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
        Route::apiResource('users', AdminUserController::class);
        Route::apiResource('announcements', AdminAnnouncementController::class)->except(['show']);
        Route::post('users/{user}/balance-adjust', [AdminUserController::class, 'adjustBalance']);
    });
    

    Route::get('admin/dashboard/summary', [AdminUserController::class, 'dashboard'])
        ->middleware(['auth:sanctum', 'role:admin|manager']);
});

