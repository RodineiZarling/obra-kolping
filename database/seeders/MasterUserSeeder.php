<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        // Cria/atualiza o usuário Master com senha segura (hash) e evita duplicidade por e-mail
        User::updateOrCreate(
            ['email' => 'rodinei@kings.dev.br'],
            [
                'name' => 'Master',
                'password' => Hash::make('k17hs42awR@'),
                'nivel_acesso' => 0,
            ]
        );
    }
}
