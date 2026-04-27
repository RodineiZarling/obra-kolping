<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acolhimento_diario_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diario_id')->constrained('acolhimento_diarios')->cascadeOnDelete();
            $table->string('destinatario_type');
            $table->unsignedBigInteger('destinatario_id');
            $table->timestamps();

            $table->index(['destinatario_type', 'destinatario_id'], 'add_dest_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acolhimento_diario_destinatarios');
    }
};
