<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    use HasFactory;

    protected $table = 'pagamentos';

    protected $fillable = [
        'user_id',
        'tipo_ficha',
        'valor',
        'referencia',
        'pagseguro_charge_id',
        'pagseguro_qr_id',
        'pix_copia_cola',
        'status',
        'tentativas_verificacao',
        'ficha_id',
        'expira_em',
    ];

    protected $casts = [
        'valor'                  => 'integer',
        'status'                 => 'string',
        'tentativas_verificacao' => 'integer',
        'expira_em'              => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(Ficha::class);
    }
}