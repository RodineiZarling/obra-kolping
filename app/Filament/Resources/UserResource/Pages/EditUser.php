<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => static::getResource()::hasActionPermission('Deletar')),
        ];
    }

    // Metodo correto para verificar autorização no Filament
    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::hasActionPermission('Editar');

    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Obtém o modelo Role usando o ID enviado no formulário
        $role = Role::find($data['role']);

        if ($role) {
            // Sincroniza a Role pelo nome
            $this->record->syncRoles($role);
        }

        // Remove o campo 'role' para evitar conflitos ao salvar registros
        unset($data['role']);

        return $data;
    }



}
