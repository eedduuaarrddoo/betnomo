<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChavePixAndIsAdminToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('chave_pix')->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('chave_pix');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['chave_pix', 'is_admin']);
        });
    }
}