<?php

namespace App\Http\Controllers;

use App\Models\Bolao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BolaoController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $query = Bolao::where('sorteado', false);

        if ($request->has('classe')) {
            $query->where('classe', strtoupper($request->classe));
        }

        $boloes = $query->orderBy('hora_sorteio')->get();

        $data = $boloes->map(function (Bolao $bolao) {
            $qtdParticipantes = count($bolao->participantes ?? []);
            $cheio            = $qtdParticipantes >= $bolao->max_participantes;

            return [
                'id'               => $bolao->id,
                'classe'           => $bolao->classe,
                'hora_abertura'    => substr($bolao->hora_abertura, 0, 5),
                'hora_sorteio'     => substr($bolao->hora_sorteio, 0, 5),
                'participantes'    => $qtdParticipantes,
                'max_participantes' => $bolao->max_participantes,
                'valor_total'      => $bolao->valor_total,
                'status'           => $cheio ? 'fechado' : 'aberto',
            ];
        });

        return response()->json($data);
    }
}