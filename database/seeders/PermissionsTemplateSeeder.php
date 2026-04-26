<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder baseado em variáveis de ambiente ou arquivo local, sem depender de outro banco.
        // 1) Lista via .env (PERMISSIONS_LIST), separada por vírgula, pipe (|) ou quebra de linha.
        //    Ex.: PERMISSIONS_LIST="users.view, users.create | users.edit\nusers.delete"
        // 2) Arquivo JSON local com array de objetos {"name": "perm", "guard_name": "web"} ou array de strings ["users.view", ...]
        //    Caminho por .env PERMISSIONS_TEMPLATE_FILE (padrão: database/seeders/data/permissions.json, com fallback para database/seeders/data/permission.json)
        // 3) Guard padrão pode ser definido por .env PERMISSIONS_GUARD (padrão: web)

        $defaultGuard = env('PERMISSIONS_GUARD', 'web');
        $items = [];

        // Opção 1: via lista no .env
        $list = env('PERMISSIONS_LIST');
        if (is_string($list) && trim($list) !== '') {
            $names = preg_split('/[\,\|\r\n]+/', $list) ?: [];
            foreach ($names as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $items[] = [
                        'name' => $name,
                        'guard_name' => $defaultGuard,
                    ];
                }
            }
        }

        // Opção 2: via arquivo JSON local
        if (empty($items)) {
            $file = env('PERMISSIONS_TEMPLATE_FILE', base_path('database/seeders/data/permissions.json'));
            // Fallback para nome alternativo singular se o padrão não existir
            if ((!$file || !file_exists($file)) && !$this->isEnvFileProvided()) {
                $alt = base_path('database/seeders/data/permission.json');
                if (file_exists($alt)) {
                    $file = $alt;
                }
            }

            if (is_string($file) && $file !== '' && file_exists($file)) {
                try {
                    $json = file_get_contents($file);
                    $data = json_decode($json, true);
                    if (is_array($data)) {
                        foreach ($data as $row) {
                            if (is_string($row)) {
                                $name = trim($row);
                                $guard = $defaultGuard;
                            } else {
                                $name = is_array($row) ? ($row['name'] ?? null) : null;
                                $guard = is_array($row) ? ($row['guard_name'] ?? $defaultGuard) : $defaultGuard;
                            }

                            if ($name) {
                                $items[] = [
                                    'name' => $name,
                                    'guard_name' => $guard,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignora erros de leitura/parse do arquivo
                }
            }
        }

        // Insere de forma idempotente
        foreach ($items as $perm) {
            if (!isset($perm['name']) || !isset($perm['guard_name'])) {
                continue;
            }
            Permission::query()->firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => $perm['guard_name']]
            );
        }
    }

    private function isEnvFileProvided(): bool
    {
        // Considera como "fornecido" se a variável existir, mesmo vazia não conta
        $envVal = env('PERMISSIONS_TEMPLATE_FILE');
        return is_string($envVal) && trim($envVal) !== '';
    }
}
