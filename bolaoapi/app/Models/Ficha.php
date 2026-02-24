<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ficha extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'valor',
        'token',
        'usada',
        'user_id',
    ];

    protected $casts = [
        'usada' => 'boolean',
        'valor' => 'integer',
    ];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}