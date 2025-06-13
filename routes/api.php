<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User routes
    Route::prefix('users')->group(function () {         
    Route::get('/', [UserController::class, 'index']);         
    Route::post('/', [UserController::class, 'store'])->middleware('can:create-users');         
    Route::get('/{id}', [UserController::class, 'show']);                  
    
    // Regular PUT route (works with raw JSON)         
    Route::put('/{id}', [UserController::class, 'update'])->middleware('can:update-users');                  
    
    // POST route for form-data updates (method spoofing)         
    Route::post('/{id}/update', [UserController::class, 'updateViaPost'])->middleware('can:update-users');                  
    
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('can:delete-users');     
});

    // Role routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:view-roles');
        Route::post('/', [RoleController::class, 'store'])->middleware('can:create-roles');
        Route::get('{role}', [RoleController::class, 'show'])->middleware('can:view-roles');
        Route::put('{role}', [RoleController::class, 'update'])->middleware('can:update-roles');
        Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('can:delete-roles');
    });


// Categories routes - CORRECTED VERSION
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store'])->middleware('can:create-categories');
    Route::get('/{category}', [CategoryController::class, 'show']);  // Added missing /
    Route::put('/{category}', [CategoryController::class, 'update'])->middleware('can:update-categories');  // Added missing /
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->middleware('can:delete-categories');  // Added missing /
    Route::get('/{category}/products', [CategoryController::class, 'products']);  // Added missing /
});

// Alternative cleaner approach using apiResource:
Route::apiResource('categories', CategoryController::class)->middleware([
    'store' => 'can:create-categories',
    'update' => 'can:update-categories', 
    'destroy' => 'can:delete-categories'
]);
Route::get('categories/{category}/products', [CategoryController::class, 'products']);


    //products
    // Products routes - Updated with POST method spoofing
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store'])->middleware('can:create-products');
        Route::get('/{product}', [ProductController::class, 'show']);
        
        // Regular PUT route (works with raw JSON)
        Route::put('/{product}', [ProductController::class, 'update'])->middleware('can:update-products');
        
        // POST route for form-data updates (method spoofing)
        Route::post('/{product}/update', [ProductController::class, 'updateViaPost'])->middleware('can:update-products');
        
        Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('can:delete-products');
    });




});