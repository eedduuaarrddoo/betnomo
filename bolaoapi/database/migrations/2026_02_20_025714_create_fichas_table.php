<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
{
    Schema::create('fichas', function (Blueprint $table) {
        $table->id();
        $table->enum('tipo', ['A', 'B', 'C']);
        $table->decimal('valor', 8, 2);
        $table->string('token', 64)->unique();
        $table->boolean('usada')->default(false);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fichas');
    }
}
