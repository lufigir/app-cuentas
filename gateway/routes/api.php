<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::put('/accounts/{id}', [AccountController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
});
