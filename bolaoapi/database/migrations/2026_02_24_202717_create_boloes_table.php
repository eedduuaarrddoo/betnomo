<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoloesTable extends Migration
{
    public function up()
    {
        Schema::create('boloes', function (Blueprint $table) {
            $table->id();

            $table->enum('classe', ['A', 'B', 'C']);

            $table->time('hora_abertura');
            $table->time('hora_sorteio');

            // Jogadores
            $table->unsignedInteger('max_participantes');
            $table->json('participantes')->default('[]');   // array de user_id

            // Fichas
            $table->json('fichas_inseridas')->default('[]'); // array de ficha_id
            $table->unsignedInteger('valor_total')->default(0); // soma dos valores das fichas

            // Resultado
            $table->boolean('sorteado')->default(false);
            $table->foreignId('vencedor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('boloes');
    }
}