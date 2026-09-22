<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemberController;

Route::get('/works', [WorkController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/members', [MemberController::class, 'index']);

// Visitors and logged-in users can submit suggestions.
Route::post('/works', [WorkController::class, 'store']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    Route::get('/my-works', [WorkController::class, 'myWorks']);

    Route::middleware('permission:work-vote')->group(function () {
        Route::get('/works/pending', [WorkController::class, 'pending']);
        Route::post('/works/{work}/vote', [WorkController::class, 'vote']);
    });

    Route::middleware('permission:biodata-manage')->prefix('admin')->group(function () {
        Route::get('/profiles/pending', [AdminProfileController::class, 'pending']);
        Route::post('/profiles/{profile}/approve', [AdminProfileController::class, 'approve']);
        Route::post('/profiles/{profile}/reject', [AdminProfileController::class, 'reject']);
    });
});

// Public single-work route must come after /works/pending.
Route::get('/works/{work}', [WorkController::class, 'show']);
