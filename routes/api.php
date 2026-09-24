<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\VendedorApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthApiController::class, 'me']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::get('/vendedor/resumo', [VendedorApiController::class, 'resumo']);
});
