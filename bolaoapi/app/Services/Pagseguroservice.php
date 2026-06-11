<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagSeguroService
{
    private string $baseUrl;
    private string $token;
    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl       = rtrim(config('pagseguro.base_url') ?? 'https://sandbox.api.pagseguro.com', '/');
        $this->token         = config('pagseguro.token') ?? '';
        $this->webhookSecret = config('pagseguro.webhook_secret') ?? '';
    }

    
    public function criarCobrancaPix(string $referencia, string $tipo, int $valorCentavos): array
    {
        $response = Http::withToken($this->token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(30)
            ->post("{$this->baseUrl}/charges", [
                'reference_id'     => $referencia,
                'description'      => "Ficha Classe {$tipo} - Juvio",
                'amount'           => [
                    'value'    => $valorCentavos,
                    'currency' => 'BRL',
                ],
                'payment_method'   => [
                    'type'         => 'PIX',
                    'installments' => 1,
                    'capture'      => true,
                ],
                'expiration_date'  => now()->addMinutes(30)->toIso8601String(),
                'notification_urls' => [
                    config('app.url') . '/api/pagamentos/webhook',
                ],
            ]);

        if ($response->failed()) {
            // Log sem expor o token ou dados sensíveis do usuário
            Log::error('PagSeguro: falha ao criar cobrança PIX', [
                'status'     => $response->status(),
                'referencia' => $referencia,
                'tipo'       => $tipo,
                // Não logamos o body completo — pode conter dados sensíveis
            ]);

            throw new \RuntimeException('Falha ao criar cobrança PIX. Tente novamente.');
        }

        return $response->json();
    }

    /**
     * Consulta o status de uma cobrança pelo charge ID do PagSeguro.
     */
    public function consultarCobranca(string $chargeId): array
    {
        $response = Http::withToken($this->token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get("{$this->baseUrl}/charges/{$chargeId}");

        if ($response->failed()) {
            throw new \RuntimeException("PagSeguro: falha ao consultar cobrança.");
        }

        return $response->json();
    }

    /**
     * Verifica a assinatura HMAC-SHA256 do webhook enviado pelo PagSeguro.
     * O secret é configurado no painel do PagSeguro e armazenado em .env.
     *
     * NUNCA deve retornar true se o secret estiver vazio.
     */
    public function verificarAssinaturaWebhook(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret) || empty($signature)) {
            return false;
        }

        $esperado = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($esperado, strtolower(ltrim($signature, 'sha256=')));
    }
}