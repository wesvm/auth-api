<?php

use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UserController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('2fa/verify', [AuthController::class, 'authenticate2fa']);
    Route::get('verify-email/{token}', [VerifyEmailController::class, 'verifyEmail']);
    Route::post('resend-verification', [VerifyEmailController::class, 'resendVerification']);

    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::prefix('2fa')->group(function () {
        Route::get('status', [TwoFactorController::class, 'status']);
        Route::post('generate', [TwoFactorController::class, 'generate']);
        Route::post('enable', [TwoFactorController::class, 'enable']);
        Route::post('disable', [TwoFactorController::class, 'disable']);
        Route::post('recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    });

    Route::prefix('password')->group(function (){
        Route::post('forgot', [PasswordController::class, 'forgot']);
        Route::post('reset', [PasswordController::class, 'reset']);
        Route::post('update', [PasswordController::class, 'update']);
    });
});

Route::middleware('auth:jwt')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);

    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}', [UserController::class, 'update']);

    //only admin
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('role:admin');
});
