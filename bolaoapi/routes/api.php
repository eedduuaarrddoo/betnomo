<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BolaoController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\PagamentoController;
use Illuminate\Support\Facades\Route;

// ── Públicas ──────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);

// Webhook do PagSeguro — sem auth de usuário, segurança via HMAC no controller
Route::post('/pagamentos/webhook', [PagamentoController::class, 'webhook'])
    ->name('pagamentos.webhook');

// ── Autenticadas ──────────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Bolões (usuário comum)
    Route::get('/boloes', [BolaoController::class, 'index']);
    Route::post('/boloes/{id}/participar', [BolaoController::class, 'participar']);

    // Fichas
    Route::prefix('fichas')->group(function () {
        Route::get('/',                [FichaController::class, 'index']);
        Route::post('gerar-qr',        [FichaController::class, 'gerarQr']);
        Route::post('confirmar',       [FichaController::class, 'confirmar']);
        Route::post('validar',         [FichaController::class, 'validar']);

        // Compra via PagSeguro (substitui o fluxo manual de gerar-qr + confirmar)
        Route::post('iniciar-compra',          [PagamentoController::class, 'iniciarCompra']);
        Route::get('compra-status/{referencia}', [PagamentoController::class, 'status']);
    });

    // Admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',            [BolaoController::class, 'adminDashboard']);
        Route::post('/boloes',              [BolaoController::class, 'store']);
        Route::post('/boloes/{id}/sortear', [BolaoController::class, 'sortear']);
        Route::get('/usuarios',             [AuthController::class, 'listUsers']);
    });

});