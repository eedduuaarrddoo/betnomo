<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();

            // Dono do pagamento
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Dados da compra
            $table->enum('tipo_ficha', ['A', 'B', 'C']);
            $table->unsignedInteger('valor'); // em CENTAVOS (evita ponto flutuante)

            // Identificadores
            $table->uuid('referencia')->unique();             // nosso UUID interno (exposto ao frontend)
            $table->string('pagseguro_charge_id', 36)->nullable()->unique(); // CHAR_xxx (nunca exposto)
            $table->string('pagseguro_qr_id', 36)->nullable();

            // Dados do PIX — armazenados no servidor, nunca gerados no frontend
            $table->text('pix_copia_cola')->nullable();

            // Estado do pagamento
            $table->enum('status', ['pendente', 'pago', 'expirado', 'falhou'])->default('pendente');
            $table->unsignedSmallInteger('tentativas_verificacao')->default(0);

            // Ficha gerada após confirmação
            $table->foreignId('ficha_id')->nullable()->constrained('fichas')->nullOnDelete();

            $table->timestamp('expira_em')->nullable();
            $table->timestamps();

            // Índices para as queries mais frequentes
            $table->index(['referencia', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('pagseguro_charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};