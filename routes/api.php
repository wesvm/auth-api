<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::group([
    'middleware' => ['auth:jwt', 'role:admin'],
    'prefix' => 'admin'
], function () {
    Route::get('/', function () {
        return 'hi admin';
    });
});
