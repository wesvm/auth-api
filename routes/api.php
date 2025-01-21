<?php

use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UserController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/verify/{token}', VerifyEmailController::class);
});

Route::prefix('password')->group(function (){
   Route::post('/forgot', [PasswordController::class, 'forgot']);
   Route::post('/reset', [PasswordController::class, 'reset']);
   Route::put('/update', [PasswordController::class, 'update']);
});

Route::apiResource('users', UserController::class);

Route::group([
    'middleware' => ['auth:jwt', 'role:admin'],
    'prefix' => 'admin'
], function () {
    Route::get('/', function () {
        return 'hi admin';
    });
});
