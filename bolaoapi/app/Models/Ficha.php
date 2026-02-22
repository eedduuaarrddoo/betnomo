<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ficha extends Model
{
    protected $fillable = ['tipo', 'valor', 'token', 'usada', 'user_id'];

    
    public static array $valores = [
        'A' => 5.00,
        'B' => 10.00,
        'C' => 20.00,
    ];

    
    protected static function booted(): void
    {
        static::creating(function (Ficha $ficha) {
            $ficha->token = Str::random(64);
            $ficha->valor = self::$valores[$ficha->tipo];
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}