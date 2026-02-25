<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FichaController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    
    Route::prefix('fichas')->group(function () {
        Route::get('/',          [FichaController::class, 'index']);
        Route::post('gerar-qr',  [FichaController::class, 'gerarQr']);
        Route::post('confirmar', [FichaController::class, 'confirmar']);
        Route::post('validar',   [FichaController::class, 'validar']);
    });
});