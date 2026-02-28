<?php

namespace App\Http\Controllers;

use App\Models\Bolao;
use App\Services\FichaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BolaoController extends Controller
{
    // Sem construtor — FichaService injetado só onde precisa

    /**
     * GET /api/boloes?classe=C
     */
    public function index(Request $request): JsonResponse
    {
        $query = Bolao::where('sorteado', false);

        if ($request->has('classe')) {
            $query->where('classe', strtoupper($request->classe));
        }

        $boloes = $query->orderBy('hora_sorteio')->get();

        return response()->json($this->formatarBoloes($boloes));
    }

    /**
     * GET /api/admin/dashboard
     */
    public function adminDashboard(): JsonResponse
    {
        $boloes = Bolao::orderBy('created_at', 'desc')->get();
        return response()->json($this->formatarBoloes($boloes));
    }

    /**
     * POST /api/admin/boloes
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'classe'            => 'required|in:A,B,C',
            'hora_abertura'     => 'required|date_format:H:i',
            'hora_sorteio'      => 'required|date_format:H:i',
            'max_participantes' => 'required|integer|min:2|max:100',
        ]);

        $bolao = Bolao::create([
            'classe'            => strtoupper($request->classe),
            'hora_abertura'     => $request->hora_abertura,
            'hora_sorteio'      => $request->hora_sorteio,
            'max_participantes' => $request->max_participantes,
            'participantes'     => [],
            'fichas_inseridas'  => [],
            'valor_total'       => 0,
            'sorteado'          => false,
            'vencedor_id'       => null,
        ]);

        return response()->json([
            'message' => 'Bolão criado com sucesso!',
            'bolao'   => $bolao,
        ], 201);
    }

    /**
     * POST /api/boloes/{id}/participar
     * FichaService injetado diretamente no método — não afeta outras rotas
     */
    public function participar(Request $request, int $id, FichaService $fichaService): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $bolao  = Bolao::findOrFail($id);
        $userId = $request->user()->id;

        if ($bolao->sorteado) {
            return response()->json(['error' => 'Este bolão já foi sorteado.'], 422);
        }

        $qtdAtual = count($bolao->participantes ?? []);
        if ($qtdAtual >= $bolao->max_participantes) {
            return response()->json(['error' => 'Este bolão está cheio.'], 422);
        }

        if ($bolao->temParticipante($userId)) {
            return response()->json(['error' => 'Você já está participando deste bolão.'], 422);
        }

        $resultado = $fichaService->validarFicha($request->token, $userId);

        if (!$resultado['valida']) {
            return response()->json(['error' => $resultado['erro']], 422);
        }

        $ficha = $resultado['ficha'];

        if ($ficha->tipo !== $bolao->classe) {
            return response()->json([
                'error' => "Este bolão exige uma ficha Classe {$bolao->classe}. Sua ficha é Classe {$ficha->tipo}.",
            ], 422);
        }

        $bolao->adicionarParticipante($userId, $ficha);
        $fichaService->usarFicha($ficha);

        return response()->json([
            'message'       => 'Participação confirmada!',
            'bolao_id'      => $bolao->id,
            'participantes' => count($bolao->fresh()->participantes),
        ]);
    }

    /**
     * POST /api/admin/boloes/{id}/sortear
     */
    public function sortear(int $id): JsonResponse
    {
        $bolao = Bolao::findOrFail($id);

        if ($bolao->sorteado) {
            return response()->json(['error' => 'Este bolão já foi sorteado.'], 422);
        }

        $participantes = $bolao->participantes ?? [];

        if (empty($participantes)) {
            return response()->json(['error' => 'Não há participantes neste bolão.'], 422);
        }

        $vencedorId = $participantes[array_rand($participantes)];

        $bolao->update([
            'sorteado'    => true,
            'vencedor_id' => $vencedorId,
        ]);

        $vencedor = \App\Models\User::find($vencedorId);

        return response()->json([
            'message'     => 'Sorteio realizado com sucesso!',
            'vencedor'    => $vencedor ? $vencedor->username : 'Desconhecido',
            'vencedor_id' => $vencedorId,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatarBoloes($boloes): array
    {
        return $boloes->map(function (Bolao $bolao) {
            $qtd   = count($bolao->participantes ?? []);
            $cheio = $qtd >= $bolao->max_participantes;

            return [
                'id'                => $bolao->id,
                'classe'            => $bolao->classe,
                'hora_abertura'     => substr($bolao->hora_abertura, 0, 5),
                'hora_sorteio'      => substr($bolao->hora_sorteio, 0, 5),
                'participantes'     => $qtd,
                'max_participantes' => $bolao->max_participantes,
                'valor_total'       => $bolao->valor_total,
                'sorteado'          => $bolao->sorteado,
                'vencedor_id'       => $bolao->vencedor_id,
                'status'            => $cheio ? 'fechado' : 'aberto',
            ];
        })->toArray();
    }
}