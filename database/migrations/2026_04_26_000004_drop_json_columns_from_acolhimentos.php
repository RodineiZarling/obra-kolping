<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('acolhimentos', function (Blueprint $table) {
            if (Schema::hasColumn('acolhimentos', 'composicao_familiar')) {
                $table->dropColumn('composicao_familiar');
            }
            if (Schema::hasColumn('acolhimentos', 'procedimentos')) {
                $table->dropColumn('procedimentos');
            }
            if (Schema::hasColumn('acolhimentos', 'diario_acompanhamento')) {
                $table->dropColumn('diario_acompanhamento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acolhimentos', function (Blueprint $table) {
            if (! Schema::hasColumn('acolhimentos', 'composicao_familiar')) {
                $table->json('composicao_familiar')->nullable();
            }
            if (! Schema::hasColumn('acolhimentos', 'procedimentos')) {
                $table->json('procedimentos')->nullable();
            }
            if (! Schema::hasColumn('acolhimentos', 'diario_acompanhamento')) {
                $table->json('diario_acompanhamento')->nullable();
            }
        });
    }
};
