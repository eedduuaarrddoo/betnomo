<?php

namespace App\Http\Controllers;

use App\Services\FichaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FichaController extends Controller
{
    private FichaService $fichaService;

    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }

    /**
     * POST /api/fichas/gerar-qr
     * Body: { "tipo": "A" | "B" | "C" }
     */
    public function gerarQr(Request $request): JsonResponse
    {
        $request->validate(['tipo' => 'required|in:A,B,C']);

        $tipo       = strtoupper($request->tipo);
        $referencia = (string) Str::uuid();

        $pixPayload = $this->fichaService->gerarPayloadPix($tipo, $referencia);
        $qrBase64   = $this->fichaService->gerarQrCodeBase64($pixPayload);

        return response()->json([
            'tipo'        => $tipo,
            'valor'       => FichaService::TIPOS[$tipo],
            'referencia'  => $referencia,
            'pix_payload' => $pixPayload,
            'qr_base64'   => $qrBase64,
        ]);
    }

    /**
     * POST /api/fichas/confirmar
     * Body: { "tipo": "A" | "B" | "C", "referencia": "<uuid>" }
     */
    public function confirmar(Request $request): JsonResponse
    {
        $request->validate([
            'tipo'       => 'required|in:A,B,C',
            'referencia' => 'required|string|max:36',
        ]);

        $ficha = $this->fichaService->criarFicha($request->tipo, $request->user()->id);

        return response()->json([
            'message' => 'Ficha criada com sucesso!',
            'ficha'   => [
                'id'    => $ficha->id,
                'tipo'  => $ficha->tipo,
                'valor' => $ficha->valor,
                'token' => $ficha->token,
            ],
        ], 201);
    }

    /**
     * POST /api/fichas/validar
     * Body: { "token": "<uuid>" }
     */
    public function validar(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $resultado = $this->fichaService->validarFicha(
            $request->token,
            $request->user()->id
        );

        if (!$resultado['valida']) {
            return response()->json(['error' => $resultado['erro']], 422);
        }

        return response()->json(['message' => 'Ficha valida.', 'ficha' => $resultado['ficha']]);
    }
}