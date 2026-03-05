<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boloes', function (Blueprint $table) {
            // SQLite nao suporta CHANGE direto — recria as colunas
            $table->dateTime('hora_abertura')->change();
            $table->dateTime('hora_sorteio')->change();
        });
    }

    public function down(): void
    {
        Schema::table('boloes', function (Blueprint $table) {
            $table->time('hora_abertura')->change();
            $table->time('hora_sorteio')->change();
        });
    }
};