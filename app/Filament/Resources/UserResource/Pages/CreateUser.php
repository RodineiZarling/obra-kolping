<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Client\Resources\UserResource\Pages\ValidationException;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['role'])) {
            throw ValidationException::withMessages([
                'role' => 'O papel do usuário é obrigatório.',
            ]);
        }

        // Remove o campo de "role" para evitar problemas no create
        $role = $data['role']; // Armazena a role selecionada
        unset($data['role']); // Remove antes de salvar no modelo

        return $data;
    }

    protected function afterCreate(): void
    {
        // Obtém o modelo Role usando o ID enviado no formulário
        $role = Role::find(request('data.role'));

        if ($role) {
            // Atribui a Role pelo nome (necessário para o Spatie)
            $this->record->assignRole(request($role));
        }
    }


}
