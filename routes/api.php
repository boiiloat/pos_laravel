<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::resource('users', UserController::class)->except('index'); // Excluding the 'index' route
Route::resource('roles', RoleController::class)->except('index');

// In routes/api.php
Route::post('user', [UserController::class, 'store']); // Store a new user
Route::get('user/{id}', [UserController::class, 'show']); // Get user by ID
