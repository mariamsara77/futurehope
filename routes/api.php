<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\WorkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\ActivitySyncController;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
*/

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'service' => 'futurehope-api',
]));

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/

Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/{work}', [WorkController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/members', [MemberController::class, 'index']);

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10');

Route::post('/tracking/event', [TrackingController::class, 'trackEvent'])
    ->middleware('throttle:120,1');

Route::post('/tracking/pwa', [TrackingController::class, 'syncPwaStatus'])
    ->middleware('throttle:30,1');

Route::post('/tracking/sync', [ActivitySyncController::class, 'sync'])
    ->middleware('throttle:30,1');

Route::post('/works', [WorkController::class, 'store'])
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| Login/Register use API authentication.
| They do NOT require an existing Sanctum session/token.
|
*/

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,10');

    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:10,10');

    /*
    |--------------------------------------------------------------------------
    | Google Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware(['web', 'throttle:10,1']);

    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware(['web', 'throttle:10,1']);

    Route::post('/google/exchange', [GoogleAuthController::class, 'exchange'])
        ->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Authenticated User
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/password', [AuthController::class, 'changePassword']);

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('profile')->group(function () {

        Route::get('/', [ProfileController::class, 'show']);

        Route::post('/', [ProfileController::class, 'update']);

        Route::put('/', [ProfileController::class, 'update']);

        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
    });

    /*
    |--------------------------------------------------------------------------
    | User Works
    |--------------------------------------------------------------------------
    */

    Route::get('/my-works', [WorkController::class, 'myWorks']);

    Route::get('/works/{work}/view', [WorkController::class, 'view']);

    /*
    |--------------------------------------------------------------------------
    | Work Voting
    |--------------------------------------------------------------------------
    */

    // Keep the static /works/pending route before /works/{work} so Laravel
    // does not interpret "pending" as a work ID.
    Route::get('/works/pending', [WorkController::class, 'pending']);

    Route::post('/works/{work}/vote', [WorkController::class, 'vote']);

    Route::delete('/works/{work}/vote', [WorkController::class, 'undoVote']);

    /*
    |--------------------------------------------------------------------------
    | Admin Profile Management
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:biodata-manage')
        ->prefix('admin')
        ->group(function () {

            Route::get(
                '/profiles/pending',
                [AdminProfileController::class, 'pending']
            );

            Route::post(
                '/profiles/{profile}/approve',
                [AdminProfileController::class, 'approve']
            );

            Route::post(
                '/profiles/{profile}/reject',
                [AdminProfileController::class, 'reject']
            );
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Contact Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin/contact')->group(function () {

        Route::get('/', [ContactController::class, 'index']);

        Route::get('/{contactMessage}', [ContactController::class, 'show']);
    });
});