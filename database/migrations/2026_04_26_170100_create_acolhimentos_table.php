<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acolhimentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('pessoa_acolhida_id');
            $table->unsignedBigInteger('responsavel_id')->nullable();
            $table->date('data_acolhimento');
            $table->string('origem_encaminhamento')->nullable();
            $table->string('motivo_acolhimento')->nullable();
            $table->text('descricao_situacao')->nullable();
            $table->enum('status', ['em_andamento', 'encerrado', 'suspenso'])->default('em_andamento');
            $table->text('observacoes')->nullable();

            // Campos JSON para protótipo: composição familiar, procedimentos, diário
            $table->json('composicao_familiar')->nullable();
            $table->json('procedimentos')->nullable();
            $table->json('diario_acompanhamento')->nullable();

            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('pessoa_acolhida_id')->references('id')->on('pessoas_acolhidas')->cascadeOnDelete();
            $table->foreign('responsavel_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['empresa_id', 'pessoa_acolhida_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acolhimentos');
    }
};
