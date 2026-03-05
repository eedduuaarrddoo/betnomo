<?php

namespace App\Http\Controllers;

use App\Models\Bolao;
use App\Models\User;
use App\Services\FichaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BolaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $isAdmin = $user && (bool) $user->is_admin;

        $query = $isAdmin
            ? Bolao::query()
            : Bolao::where('sorteado', false);

        if ($request->has('classe')) {
            $query->where('classe', strtoupper($request->classe));
        }

        $boloes = $query->orderBy('hora_sorteio')->get();

        return response()->json($this->formatarBoloes($boloes));
    }

    public function adminDashboard(): JsonResponse
    {
        $boloes = Bolao::orderBy('created_at', 'desc')->get();
        return response()->json($this->formatarBoloes($boloes));
    }

    public function store(Request $request): JsonResponse
    {
        // Agora aceita datetime-local vindo do frontend (ex: "2026-03-10T14:30")
        $request->validate([
            'classe'            => 'required|in:A,B,C',
            'hora_abertura'     => 'required|date',
            'hora_sorteio'      => 'required|date',
            'max_participantes' => 'required|integer|min:2|max:100',
        ]);

        $bolao = Bolao::create([
            'classe'            => strtoupper($request->classe),
            'hora_abertura'     => Carbon::parse($request->hora_abertura),
            'hora_sorteio'      => Carbon::parse($request->hora_sorteio),
            'max_participantes' => $request->max_participantes,
            'participantes'     => [],
            'fichas_inseridas'  => [],
            'valor_total'       => 0,
            'sorteado'          => false,
            'vencedor_id'       => null,
        ]);

        return response()->json([
            'message' => 'Bolao criado com sucesso!',
            'bolao'   => $bolao,
        ], 201);
    }

    public function participar(Request $request, int $id, FichaService $fichaService): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $bolao  = Bolao::findOrFail($id);
        $userId = $request->user()->id;

        if ($bolao->sorteado) {
            return response()->json(['error' => 'Este bolao ja foi sorteado.'], 422);
        }

        $qtdAtual = count($bolao->participantes ?? []);
        if ($qtdAtual >= $bolao->max_participantes) {
            return response()->json(['error' => 'Este bolao esta cheio.'], 422);
        }

        if ($bolao->temParticipante($userId)) {
            return response()->json(['error' => 'Voce ja esta participando deste bolao.'], 422);
        }

        $resultado = $fichaService->validarFicha($request->token, $userId);

        if (!$resultado['valida']) {
            return response()->json(['error' => $resultado['erro']], 422);
        }

        $ficha = $resultado['ficha'];

        if ($ficha->tipo !== $bolao->classe) {
            return response()->json([
                'error' => "Este bolao exige ficha Classe {$bolao->classe}. Sua ficha e Classe {$ficha->tipo}.",
            ], 422);
        }

        $bolao->adicionarParticipante($userId, $ficha);
        $fichaService->usarFicha($ficha);

        return response()->json([
            'message'       => 'Participacao confirmada!',
            'bolao_id'      => $bolao->id,
            'participantes' => count($bolao->fresh()->participantes),
        ]);
    }

    public function sortear(int $id): JsonResponse
    {
        $bolao = Bolao::findOrFail($id);

        if ($bolao->sorteado) {
            return response()->json(['error' => 'Este bolao ja foi sorteado.'], 422);
        }

        $participantes = $bolao->participantes ?? [];

        if (count($participantes) < 2) {
            return response()->json(['error' => 'O bolao precisa de pelo menos 2 participantes para sortear.'], 422);
        }

        $vencedorId = $participantes[array_rand($participantes)];

        $fichasIds = $bolao->fichas_inseridas ?? [];
        if (!empty($fichasIds)) {
            \App\Models\Ficha::whereIn('id', $fichasIds)->update(['user_id' => $vencedorId]);
        }

        $bolao->update([
            'sorteado'    => true,
            'vencedor_id' => $vencedorId,
        ]);

        $vencedor = User::find($vencedorId);

        return response()->json([
            'message'     => 'Sorteio realizado com sucesso!',
            'vencedor'    => $vencedor ? $vencedor->username : null,
            'vencedor_id' => $vencedorId,
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────────────────────

    private function formatarBoloes($boloes): array
    {
        $user    = auth()->user();
        $isAdmin = $user && (bool) $user->is_admin;

        // Pre-carrega todos os users envolvidos para evitar N+1
        $todosIds = $boloes->flatMap(fn($b) => $b->participantes ?? [])
            ->merge($boloes->pluck('vencedor_id')->filter())
            ->unique()
            ->values();

        $usuarios = User::whereIn('id', $todosIds)->pluck('username', 'id');

        return $boloes->map(function (Bolao $bolao) use ($isAdmin, $usuarios) {
            $participantesIds = $bolao->participantes ?? [];
            $qtd              = count($participantesIds);
            $fichasIds        = $bolao->fichas_inseridas ?? [];
            $cheio            = $qtd >= $bolao->max_participantes;

            // Acao definida pelo backend
            if ($bolao->sorteado) {
                $acao = null;
            } elseif ($isAdmin) {
                $acao = 'sortear';
            } else {
                $acao = 'participar';
            }

            // Nomes dos participantes
            $participantesNomes = array_values(array_map(
                fn($id) => $usuarios[$id] ?? 'Desconhecido',
                $participantesIds
            ));

            // Nome do vencedor
            $vencedorNome = $bolao->vencedor_id
                ? ($usuarios[$bolao->vencedor_id] ?? null)
                : null;

            // Formatar datas com Carbon — compativel com time e datetime
            $abertura = $bolao->hora_abertura
                ? Carbon::parse($bolao->hora_abertura)->format('d/m/Y H:i')
                : null;
            $sorteio  = $bolao->hora_sorteio
                ? Carbon::parse($bolao->hora_sorteio)->format('d/m/Y H:i')
                : null;

            return [
                'id'                  => $bolao->id,
                'classe'              => $bolao->classe,
                'hora_abertura'       => $abertura,
                'hora_sorteio'        => $sorteio,
                'participantes'       => $qtd,
                'participantes_nomes' => $participantesNomes,    // usernames
                'max_participantes'   => $bolao->max_participantes,
                'fichas_count'        => count($fichasIds),      // qtd de moedas
                'valor_total'         => $bolao->valor_total,
                'sorteado'            => $bolao->sorteado,
                'vencedor_id'         => $bolao->vencedor_id,
                'vencedor'            => $vencedorNome,
                'status'              => $cheio ? 'fechado' : 'aberto',
                'acao'                => $acao,
            ];
        })->toArray();
    }
}