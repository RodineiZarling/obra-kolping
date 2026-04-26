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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('cadastro')->useCurrent();
            $table->timestamp('modificado')->useCurrentOnUpdate()->nullable();
            $table->string('nome', length: 200);
            $table->string('fantasia', length: 200)->nullable();
            $table->string('cpfcnpj', length: 18);
            $table->string('rgie')->nullable();
            $table->string('rua', length: 200)->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro', length: 200)->nullable();
            $table->string('cidade', length: 200)->nullable();
            $table->string('uf', length: 2)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('email', length: 200)->nullable();
            $table->string('telefone', length: 15)->nullable();
            $table->string('celular', length: 15)->nullable();
            $table->string('logo', length: 200)->nullable();
            $table->string('status', length: 1)->default('1')->comment('1= Ativo, 0= inativo');
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
