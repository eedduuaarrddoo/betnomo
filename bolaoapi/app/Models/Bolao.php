<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bolao extends Model
{
    use HasFactory;

    protected $table = 'boloes';

    protected $fillable = [
        'classe',
        'hora_abertura',
        'hora_sorteio',
        'max_participantes',
        'participantes',
        'fichas_inseridas',
        'valor_total',
        'sorteado',
        'vencedor_id',
    ];

    protected $casts = [
        'participantes'   => 'array',   // JSON → array automaticamente
        'fichas_inseridas' => 'array',
        'valor_total'     => 'integer',
        'sorteado'        => 'boolean',
    ];

   
    public function getQtdParticipantesAttribute(): int
    {
        return count($this->participantes ?? []);
    }

    
    public function isAberto(): bool
    {
        return !$this->sorteado
            && $this->qtd_participantes < $this->max_participantes;
    }

    
    public function temParticipante(int $userId): bool
    {
        return in_array($userId, $this->participantes ?? []);
    }

    public function adicionarParticipante(int $userId, Ficha $ficha): void
    {
        $participantes    = $this->participantes    ?? [];
        $fichasInseridas  = $this->fichas_inseridas ?? [];

        $participantes[]   = $userId;
        $fichasInseridas[] = $ficha->id;

        $this->participantes    = $participantes;
        $this->fichas_inseridas = $fichasInseridas;
        $this->valor_total      = $this->valor_total + $ficha->valor;

        $this->save();
    }

    

    
    public function vencedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vencedor_id');
    }
}