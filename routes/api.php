<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;

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

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function(){
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Broadcasting auth endpoint
    Route::post('/broadcasting/auth', [\App\Http\Controllers\Api\BroadcastingController::class, 'auth']);

    Route::get('/profile', [OrderController::class, 'profile']);
    Route::get('/orders', [OrderController::class, 'orders']);
    Route::post('/orders', [OrderController::class, 'create']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

