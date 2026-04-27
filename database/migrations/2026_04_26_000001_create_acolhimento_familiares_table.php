<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acolhimento_familiares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acolhimento_id')->constrained('acolhimentos')->cascadeOnDelete();
            $table->string('nome');
            $table->string('parentesco')->nullable();
            $table->unsignedInteger('idade')->nullable();
            $table->string('telefone')->nullable();
            $table->string('ocupacao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acolhimento_familiares');
    }
};
