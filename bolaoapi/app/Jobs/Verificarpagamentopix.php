<?php

namespace App\Jobs;

use App\Models\Pagamento;
use App\Services\FichaService;
use App\Services\PagSeguroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarPagamentoPix implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Sem retries automáticos do Laravel — controlamos o re-dispatch manualmente
     * para ter controle total do delay e evitar explosão na fila.
     */
    public int $tries   = 1;
    public int $timeout = 30;

    /**
     * 20 tentativas × 30 segundos = ~10 minutos de verificação ativa.
     * Após isso, marcamos como expirado (o webhook ainda pode chegar depois).
     */
    private const MAX_TENTATIVAS  = 20;
    private const DELAY_SEGUNDOS  = 30;

    private int $pagamentoId;

    public function __construct(int $pagamentoId)
    {
        $this->pagamentoId = $pagamentoId;
    }

    public function handle(PagSeguroService $pagSeguro, FichaService $fichaService): void
    {
        $pagamento = Pagamento::find($this->pagamentoId);

        // Pagamento não encontrado ou já processado — encerra silenciosamente
        if (!$pagamento || $pagamento->status !== 'pendente') {
            return;
        }

        // Expirou pelo tempo — nem consulta a API
        if ($pagamento->estaExpirado()) {
            $pagamento->update(['status' => 'expirado']);
            return;
        }

        $tentativa = $pagamento->tentativas_verificacao + 1;
        $pagamento->increment('tentativas_verificacao');

        try {
            $dados  = $pagSeguro->consultarCobranca($pagamento->pagseguro_charge_id);
            $status = strtoupper($dados['status'] ?? '');

            if ($status === 'PAID') {
                $this->confirmarPagamento($pagamento, $fichaService);
                return;
            }

            if (in_array($status, ['CANCELED', 'DECLINED', 'VOIDED'])) {
                $pagamento->update(['status' => 'falhou']);
                return;
            }

            // Ainda WAITING — reagenda se não atingiu o limite
            $this->reagendarOuExpirar($pagamento, $tentativa);

        } catch (\Throwable $e) {
            Log::warning('VerificarPagamentoPix: erro na tentativa', [
                'pagamento_id' => $this->pagamentoId,
                'tentativa'    => $tentativa,
                'error'        => $e->getMessage(),
            ]);

            // Em caso de erro de rede, tenta novamente (não marca como falhou ainda)
            $this->reagendarOuExpirar($pagamento, $tentativa);
        }
    }

    private function confirmarPagamento(Pagamento $pagamento, FichaService $fichaService): void
    {
        DB::transaction(function () use ($pagamento, $fichaService) {
            // Recarrega com lock dentro da transação para evitar race condition
            // com o webhook que pode chegar simultaneamente
            $pagamento = Pagamento::lockForUpdate()->find($pagamento->id);

            if (!$pagamento || $pagamento->status !== 'pendente') {
                return; // webhook já confirmou — idempotência garantida
            }

            $ficha = $fichaService->criarFicha($pagamento->tipo_ficha, $pagamento->user_id);

            $pagamento->update([
                'status'   => 'pago',
                'ficha_id' => $ficha->id,
            ]);
        });
    }

    private function reagendarOuExpirar(Pagamento $pagamento, int $tentativa): void
    {
        if ($tentativa < self::MAX_TENTATIVAS) {
            self::dispatch($this->pagamentoId)
                ->delay(now()->addSeconds(self::DELAY_SEGUNDOS))
                ->onQueue('pagamentos');
        } else {
            $pagamento->update(['status' => 'expirado']);
        }
    }
}