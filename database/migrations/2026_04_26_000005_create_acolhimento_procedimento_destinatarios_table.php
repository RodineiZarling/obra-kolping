<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acolhimento_procedimento_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedimento_id')->constrained('acolhimento_procedimentos')->cascadeOnDelete();
            $table->string('destinatario_type');
            $table->unsignedBigInteger('destinatario_id');
            $table->timestamps();

            $table->index(['destinatario_type', 'destinatario_id'], 'apd_dest_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acolhimento_procedimento_destinatarios');
    }
};
