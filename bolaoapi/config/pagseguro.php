<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente PagSeguro
    |--------------------------------------------------------------------------
    | sandbox: https://sandbox.api.pagseguro.com
    | produção: https://api.pagseguro.com
    */
    'base_url' => env('PAGSEGURO_BASE_URL', 'https://sandbox.api.pagseguro.com'),

    /*
    |--------------------------------------------------------------------------
    | Token de autenticação
    |--------------------------------------------------------------------------
    | Gerado em: Minha Conta → Preferências → Token de Segurança
    | NUNCA exponha este valor no frontend ou em logs.
    */
    'token' => env('PAGSEGURO_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Secret do Webhook
    |--------------------------------------------------------------------------
    | Configurado em: Minha Conta → Notificações → Webhooks
    | Usado para validar a assinatura HMAC-SHA256 de cada notificação.
    */
    'webhook_secret' => env('PAGSEGURO_WEBHOOK_SECRET'),

];