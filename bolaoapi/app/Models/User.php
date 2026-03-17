<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'username',
        'email',
        'password',
        'chave_pix',
        'is_admin',
        'email_verified_at',
        'email_verification_token',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    

    public function fichas()
    {
        return $this->hasMany(Ficha::class);
    }
}