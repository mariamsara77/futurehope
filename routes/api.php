<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\ActivitySyncController;

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'service' => 'futurehope-api',
]));

Route::get('/works', [WorkController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/members', [MemberController::class, 'index']);
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10');
Route::middleware('auth:sanctum')->prefix('admin/contact')->group(function () {
    Route::get('/', [ContactController::class, 'index']);
    Route::get('/{contactMessage}', [ContactController::class, 'show']);
});

Route::post('/tracking/event', [TrackingController::class, 'trackEvent'])->middleware('throttle:120,1');
Route::post('/tracking/pwa', [TrackingController::class, 'syncPwaStatus'])->middleware('throttle:30,1');
Route::post('/tracking/sync', [ActivitySyncController::class, 'sync'])->middleware('throttle:30,1');

// Visitors and logged-in users can submit suggestions.
Route::post('/works', [WorkController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware(['web', 'throttle:10,1']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->middleware(['web', 'throttle:10,1']);
    Route::post('/google/exchange', [GoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');

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

    // Authenticated users may open published works; unpublished works are
    // visible only to their owner or members with the work-vote permission.
    Route::get('/works/{work}/view', [WorkController::class, 'view']);

    Route::middleware('permission:work-vote')->group(function () {
        Route::get('/works/pending', [WorkController::class, 'pending']);
        Route::post('/works/{work}/vote', [WorkController::class, 'vote']);
    });

    Route::middleware('permission:biodata-manage')->prefix('admin')->group(function () {
        Route::get('/profiles/pending', [AdminProfileController::class, 'pending']);
        Route::post('/profiles/{profile}/approve', [AdminProfileController::class, 'approve']);
        Route::post('/profiles/{profile}/reject', [AdminProfileController::class, 'reject']);
    });

    Route::middleware('permission:user-manage')->prefix('admin/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{user}', [AdminUserController::class, 'show']);
        Route::post('/{user}', [AdminUserController::class, 'update']);
        Route::put('/{user}', [AdminUserController::class, 'update']);
        Route::delete('/{user}', [AdminUserController::class, 'destroy']);
    });
});

// Public single-work route returns published works only.
Route::get('/works/{work}', [WorkController::class, 'show']);
