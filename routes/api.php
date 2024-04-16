<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prueba', function () {
        return "hola mundo";
    });
    Route::get('/logout', [AuthController::class, 'logout']);
});
Route::middleware(['auth:sanctum', IsAdminMiddleware::class])->group(function () {
    Route::get('/prueba2', function () {
        return "hola mundo";
    });
});

