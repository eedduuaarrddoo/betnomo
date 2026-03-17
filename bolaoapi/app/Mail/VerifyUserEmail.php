<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyUserEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->verificationUrl = env('FRONTEND_URL') . '/verify-email/' . $user->email_verification_token;
    }

   public function build(): self
{
    $html = '
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head><meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #0a0e13; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #0d1117; border: 1px solid rgba(61,214,140,0.15); border-radius: 14px; padding: 40px; }
        h2 { font-size: 1.4rem; color: #c8d3da; margin-bottom: 8px; }
        p { color: #6b7b8a; line-height: 1.7; font-size: 0.9rem; }
        .btn { display: inline-block; margin-top: 24px; padding: 13px 32px; background: #3dd68c; color: #0a0e13; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; }
        .logo { font-size: 0.85rem; font-weight: 700; color: #f0a500; letter-spacing: 0.08em; margin-bottom: 28px; }
        .footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 0.75rem; color: #3d4d5a; }
    </style>
    </head>
    <body>
        <div class="container">
            <p class="logo">Bolão da Sorte</p>
            <h2>Olá, ' . $this->user->username . '!</h2>
            <p>Obrigado por se cadastrar. Clique no botão abaixo para confirmar seu endereço de e-mail e ativar sua conta.</p>
            <a href="' . $this->verificationUrl . '" class="btn">Verificar meu e-mail</a>
            <div class="footer">
                <p>Se você não criou esta conta, ignore este e-mail.<br>Este link é de uso único.</p>
            </div>
        </div>
    </body>
    </html>';

    return $this
        ->subject('Verifique seu e-mail — Bolão da Sorte')
        ->html($html);
}
}