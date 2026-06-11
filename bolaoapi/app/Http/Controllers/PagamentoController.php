<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use App\Services\FichaService;
use App\Services\PagSeguroService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PagamentoController extends Controller
{
    private PagSeguroService $pagSeguroService;
    private FichaService $fichaService;

    public function __construct(PagSeguroService $pagSeguroService, FichaService $fichaService)
    {
        $this->pagSeguroService = $pagSeguroService;
        $this->fichaService = $fichaService;
    }

    /**
     * Inicia uma compra de Ficha via PagSeguro PIX.
     * POST /api/fichas/iniciar-compra
     */
    public function iniciarCompra(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:A,B,C',
        ]);

        $tipo = strtoupper($request->tipo);
        $user = $request->user();

        if (!array_key_exists($tipo, FichaService::TIPOS)) {
            return response()->json(['error' => 'Tipo de ficha inválido.'], 400);
        }

        $valorOriginal = FichaService::TIPOS[$tipo]; // R$ 5, 25, 50
        $valorCentavos = $valorOriginal * 100;       // em centavos
        $referencia = (string) Str::uuid();

        // 1. Cria o registro pendente localmente
        $pagamento = Pagamento::create([
            'user_id'               => $user->id,
            'tipo_ficha'            => $tipo,
            'valor'                 => $valorCentavos,
            'referencia'            => $referencia,
            'status'                => 'pendente',
            'tentativas_verificacao'=> 0,
            'expira_em'             => now()->addMinutes(30),
        ]);

        try {
            // 2. Dispara a chamada ao PagSeguro
            $cobranca = $this->pagSeguroService->criarCobrancaPix($referencia, $tipo, $valorCentavos);

            // 3. Extrai dados resilientes do PagSeguro (compatível com Orders API e Charges API)
            $chargeId = $cobranca['id'] ?? null;
            if (isset($cobranca['charges'][0]['id'])) {
                $chargeId = $cobranca['charges'][0]['id'];
            }

            $pixCopiaCola = null;
            if (isset($cobranca['payment_method']['pix']['text'])) {
                $pixCopiaCola = $cobranca['payment_method']['pix']['text'];
            } elseif (isset($cobranca['qr_codes'][0]['text'])) {
                $pixCopiaCola = $cobranca['qr_codes'][0]['text'];
            }

            $qrLink = null;
            if (isset($cobranca['payment_method']['pix']['links'])) {
                foreach ($cobranca['payment_method']['pix']['links'] as $link) {
                    if ($link['rel'] === 'QRCODE.PNG' || $link['rel'] === 'QRCODE.BASE64') {
                        $qrLink = $link['href'];
                        break;
                    }
                }
            } elseif (isset($cobranca['qr_codes'][0]['links'])) {
                foreach ($cobranca['qr_codes'][0]['links'] as $link) {
                    if ($link['rel'] === 'QRCODE.PNG' || $link['rel'] === 'QRCODE.BASE64') {
                        $qrLink = $link['href'];
                        break;
                    }
                }
            }

            $expiraEmRaw = $cobranca['qr_codes'][0]['expiration_date'] 
                ?? ($cobranca['payment_method']['pix']['expiration_date'] ?? null);

            $expiraEm = $expiraEmRaw ? Carbon::parse($expiraEmRaw) : now()->addMinutes(30);

            // 4. Atualiza o pagamento no banco com as informações da transação
            $pagamento->update([
                'pagseguro_charge_id' => $chargeId,
                'pix_copia_cola'      => $pixCopiaCola,
                'pagseguro_qr_id'     => $qrLink,
                'expira_em'           => $expiraEm,
            ]);

            return response()->json([
                'referencia'     => $referencia,
                'pix_copia_cola' => $pixCopiaCola,
                'qr_code'        => $qrLink,
                'valor'          => $valorOriginal,
                'status'         => 'pendente',
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao iniciar compra PagSeguro: ' . $e->getMessage());
            $pagamento->update(['status' => 'falhou']);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Consulta o status atual de uma compra.
     * GET /api/fichas/compra-status/{referencia}
     */
    public function status(string $referencia): JsonResponse
    {
        $pagamento = Pagamento::where('referencia', $referencia)->firstOrFail();

        // Se já está pago, não há necessidade de consultar novamente
        if ($pagamento->status === 'pago') {
            return response()->json([
                'referencia' => $pagamento->referencia,
                'status'     => 'pago',
                'ficha_token'=> $pagamento->ficha ? $pagamento->ficha->token : null,
            ]);
        }

        // Se expirou ou falhou, retorna o status local direto
        if (in_array($pagamento->status, ['expirado', 'falhou'])) {
            return response()->json([
                'referencia' => $pagamento->referencia,
                'status'     => $pagamento->status,
            ]);
        }

        // Caso esteja pendente, faz a consulta direta ao PagSeguro para garantir atualização
        if ($pagamento->pagseguro_charge_id) {
            try {
                $consulta = $this->pagSeguroService->consultarCobranca($pagamento->pagseguro_charge_id);
                $psStatus = strtoupper($consulta['status'] ?? ($consulta['charges'][0]['status'] ?? ''));

                if ($psStatus === 'PAID' || $psStatus === 'AUTHORIZED') {
                    if ($pagamento->status !== 'pago') {
                        $ficha = $this->fichaService->criarFicha($pagamento->tipo_ficha, $pagamento->user_id);
                        $pagamento->update([
                            'status'   => 'pago',
                            'ficha_id' => $ficha->id,
                        ]);
                    }
                } elseif (in_array($psStatus, ['DECLINED', 'CANCELED'])) {
                    $pagamento->update(['status' => 'falhou']);
                }
            } catch (Exception $e) {
                Log::warning("Erro ao sincronizar cobrança PagSeguro {$pagamento->pagseguro_charge_id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'referencia' => $pagamento->referencia,
            'status'     => $pagamento->status,
            'ficha_token'=> $pagamento->ficha ? $pagamento->ficha->token : null,
        ]);
    }

    /**
     * Webhook de notificação do PagSeguro.
     * POST /api/pagamentos/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Api-Signature');
        $rawPayload = $request->getContent();

        $isSandboxOrLocal = config('app.env') !== 'production';
        $isValid = false;

        // Se for Sandbox/Local e a assinatura ou segredo estiverem em branco, permite bypass
        if ($isSandboxOrLocal && (empty($signature) || empty(config('pagseguro.webhook_secret')))) {
            $isValid = true;
            Log::warning('PagSeguro Webhook: Bypassing signature verification in local/sandbox environment.');
        } else {
            $isValid = $this->pagSeguroService->verificarAssinaturaWebhook($rawPayload, $signature ?? '');
        }

        if (!$isValid) {
            return response()->json(['error' => 'Assinatura inválida.'], 400);
        }

        $data = json_decode($rawPayload, true);
        if (!$data) {
            return response()->json(['error' => 'Payload inválido.'], 400);
        }

        // Localiza a cobrança pelo ID do PagSeguro ou pela referência interna
        $chargeId = $data['id'] ?? null;
        if (isset($data['charges'][0]['id'])) {
            $chargeId = $data['charges'][0]['id'];
        }

        $referencia = $data['reference_id'] ?? null;

        $pagamento = null;
        if ($chargeId) {
            $pagamento = Pagamento::where('pagseguro_charge_id', $chargeId)->first();
        }
        if (!$pagamento && $referencia) {
            $pagamento = Pagamento::where('referencia', $referencia)->first();
        }

        if (!$pagamento) {
            return response()->json(['error' => 'Pagamento não encontrado.'], 404);
        }

        if ($pagamento->status === 'pendente') {
            $psStatus = strtoupper($data['status'] ?? ($data['charges'][0]['status'] ?? ''));

            if ($psStatus === 'PAID' || $psStatus === 'AUTHORIZED') {
                $ficha = $this->fichaService->criarFicha($pagamento->tipo_ficha, $pagamento->user_id);
                $pagamento->update([
                    'status'   => 'pago',
                    'ficha_id' => $ficha->id,
                ]);
                Log::info("Pagamento {$pagamento->referencia} confirmado via webhook. Ficha gerada.");
            } elseif (in_array($psStatus, ['DECLINED', 'CANCELED'])) {
                $pagamento->update(['status' => 'falhou']);
                Log::info("Pagamento {$pagamento->referencia} falhou/cancelado via webhook.");
            }
        }

        return response()->json(['message' => 'Status processado com sucesso.']);
    }
}
