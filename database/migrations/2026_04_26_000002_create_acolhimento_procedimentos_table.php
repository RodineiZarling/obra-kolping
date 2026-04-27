<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acolhimento_procedimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acolhimento_id')->constrained('acolhimentos')->cascadeOnDelete();
            $table->date('data')->nullable();
            $table->string('tipo')->nullable(); // atendimento, encaminhamento, visita, acompanhamento, outro
            $table->string('descricao');
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acolhimento_procedimentos');
    }
};
