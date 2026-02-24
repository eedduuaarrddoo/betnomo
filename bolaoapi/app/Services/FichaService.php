<?php

namespace App\Services;

use App\Models\Ficha;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FichaService
{
    const TIPOS = [
        'A' => 50,
        'B' => 25,
        'C' => 5,
    ];

    public function gerarToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (Ficha::where('token', $token)->exists());

        return $token;
    }

    public function criarFicha(string $tipo, int $userId): Ficha
    {
        $tipo = strtoupper($tipo);

        if (!array_key_exists($tipo, self::TIPOS)) {
            throw new \InvalidArgumentException("Tipo de ficha inválido: {$tipo}");
        }

        return Ficha::create([
            'tipo'    => $tipo,
            'valor'   => self::TIPOS[$tipo],
            'token'   => $this->gerarToken(),
            'usada'   => false,
            'user_id' => $userId,
        ]);
    }

   
    public function validarFicha(string $token, int $userId): array
    {
        $ficha = Ficha::where('token', $token)->first();

        if (!$ficha) {
            return ['valida' => false, 'ficha' => null, 'erro' => 'Token de ficha não encontrado.'];
        }

        if ($ficha->usada) {
            return ['valida' => false, 'ficha' => $ficha, 'erro' => 'Esta ficha já foi utilizada.'];
        }

        if ($ficha->user_id !== $userId) {
            return ['valida' => false, 'ficha' => $ficha, 'erro' => 'Esta ficha não pertence ao jogador informado.'];
        }

        return ['valida' => true, 'ficha' => $ficha, 'erro' => null];
    }

    public function usarFicha(Ficha $ficha): Ficha
    {
        $ficha->update(['usada' => true]);
        return $ficha->fresh();
    }

    public function gerarPayloadPix(string $tipo, string $referencia): string
    {
        $tipo     = strtoupper($tipo);
        $valor    = self::TIPOS[$tipo] ?? 5;
        $valorStr = number_format($valor, 2, '.', '');

        $chavePix   = config('app.pix_chave',  'juvio@pix.com');
        $nomeLoja   = config('app.pix_nome',   'JUVIO');
        $cidadeLoja = config('app.pix_cidade', 'SAO PAULO');

        $txid = substr(preg_replace('/[^A-Za-z0-9]/', '', $referencia), 0, 25);

        $pixKey = $this->tlv('00', 'BR.GOV.BCB.PIX') . $this->tlv('01', $chavePix);

        $payload =
            $this->tlv('00', '01') .
            $this->tlv('26', $pixKey) .
            $this->tlv('52', '0000') .
            $this->tlv('53', '986') .
            $this->tlv('54', $valorStr) .
            $this->tlv('58', 'BR') .
            $this->tlv('59', substr($nomeLoja, 0, 25)) .
            $this->tlv('60', substr($cidadeLoja, 0, 15)) .
            $this->tlv('62', $this->tlv('05', $txid));

        $payload .= $this->tlv('63', $this->crc16($payload . '6304'));

        return $payload;
    }

  
    public function gerarQrCodeBase64(string $pixPayload): string
    {
        $svg = QrCode::format('svg')
            ->size(220)
            ->errorCorrection('M')
            ->generate($pixPayload);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function tlv(string $id, string $value): string
    {
        return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $str): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $crc ^= ord($str[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1;
            }
        }
        return strtoupper(dechex($crc & 0xFFFF));
    }
}