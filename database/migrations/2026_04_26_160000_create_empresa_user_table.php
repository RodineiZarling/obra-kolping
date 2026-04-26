<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('empresa_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['empresa_id', 'user_id']);

            $table->foreign('empresa_id')
                ->references('id')
                ->on('empresas')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Backfill: cria vínculos para usuários que já possuem o campo legado `empresa` preenchido
        try {
            $users = DB::table('users')->whereNotNull('empresa')->where('empresa', '!=', '')->pluck('empresa', 'id');
            $now = now();
            $inserts = [];
            foreach ($users as $userId => $empresaId) {
                if (! is_numeric($empresaId)) {
                    continue;
                }
                $exists = DB::table('empresa_user')
                    ->where('user_id', $userId)
                    ->where('empresa_id', (int) $empresaId)
                    ->exists();
                if (! $exists) {
                    $inserts[] = [
                        'user_id' => (int) $userId,
                        'empresa_id' => (int) $empresaId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($inserts)) {
                DB::table('empresa_user')->insert($inserts);
            }
        } catch (Throwable $e) {
            // Evita quebrar a migration em ambientes onde a estrutura/colunas diferem
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_user');
    }
};
