<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('empresa')->nullable()->after('id');
            $table->string('cpfcnpj', 14)->nullable()->after('name');
            $table->integer('codigo_caixa')->nullable()->after('cpfcnpj');
            $table->string('nivel_acesso')->nullable()->after('codigo_caixa');
            $table->unsignedTinyInteger('status')->default(1)->comment('1= Ativo, 0= Inativo'); // Int(1) com comentário

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
