<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleProductController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SalePaymentController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // User management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store'])->middleware('can:create-users');
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update'])->middleware('can:update-users');
        Route::post('/{user}/update', [UserController::class, 'updateViaPost'])->middleware('can:update-users');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('can:delete-users');
    });

    // Role management
    Route::apiResource('roles', RoleController::class)->middleware([
        'index' => 'can:view-roles',
        'store' => 'can:create-roles',
        'show' => 'can:view-roles',
        'update' => 'can:update-roles',
        'destroy' => 'can:delete-roles'
    ]);

    // Category management
    Route::apiResource('categories', CategoryController::class)->middleware([
        'store' => 'can:create-categories',
        'update' => 'can:update-categories',
        'destroy' => 'can:delete-categories'
    ]);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);

    // Product management
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store'])->middleware('can:create-products');
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:update-products');
        Route::post('/{product}/update', [ProductController::class, 'updateViaPost'])->middleware('can:update-products');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('can:delete-products');
    });

    // Table management
    Route::apiResource('tables', TableController::class);

    // Sales management
    Route::apiResource('sales', SaleController::class);

    // Sale products nested routes
    Route::prefix('sales/{sale}')->group(function () {
        Route::post('products', [SaleController::class, 'addProduct']);
        Route::get('products', [SaleController::class, 'getProducts']);
        Route::delete('products/{saleProduct}', [SaleController::class, 'removeProduct']);
    });

    // Sale products standalone routes
    Route::apiResource('sale-products', SaleProductController::class)->except(['index']);

    // Payment methods
    Route::apiResource('payment-methods', PaymentMethodController::class);



    // routes/api.php
    Route::apiResource('sale-payments', SalePaymentController::class);








});


