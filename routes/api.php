<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuthController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store'])->middleware('can:create-users');
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update'])->middleware('can:update-users');
        Route::delete('{id}', [UserController::class, 'destroy'])->middleware('can:delete-users');
    });

    // Role routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('can:view-roles');
        Route::post('/', [RoleController::class, 'store'])->middleware('can:create-roles');
        Route::get('{id}', [RoleController::class, 'show'])->middleware('can:view-roles');
        Route::put('{id}', [RoleController::class, 'update'])->middleware('can:update-roles');
        Route::delete('{id}', [RoleController::class, 'destroy'])->middleware('can:delete-roles');
    });
});