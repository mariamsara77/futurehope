<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemberController;

// ========== Public Routes ==========
Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/{work}', [WorkController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/members', [MemberController::class, 'index']);

// ========== Auth ==========
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

// ========== Protected Routes ==========
Route::middleware('auth:sanctum')->group(function () {

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']); // form-data এর জন্য
        Route::put('/', [ProfileController::class, 'update']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    // Works
    Route::post('/works', [WorkController::class, 'store']);
    Route::post('/works/{work}/vote', [WorkController::class, 'vote']);
    Route::get('/my-works', [WorkController::class, 'myWorks']);
});