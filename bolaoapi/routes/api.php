<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FichaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BolaoController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::get('/boloes', [BolaoController::class, 'index']);
    Route::post('/boloes/{id}/participar',     [BolaoController::class, 'participar']);

    
    Route::prefix('fichas')->group(function () {
        Route::get('/',          [FichaController::class, 'index']);
        Route::post('gerar-qr',  [FichaController::class, 'gerarQr']);
        Route::post('confirmar', [FichaController::class, 'confirmar']);
        Route::post('validar',   [FichaController::class, 'validar']);
    });

 Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',              [BolaoController::class, 'adminDashboard']);
        Route::post('/boloes',                [BolaoController::class, 'store']);
        Route::post('/boloes/{id}/sortear',   [BolaoController::class, 'sortear']);
    });




});