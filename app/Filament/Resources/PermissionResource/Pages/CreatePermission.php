<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;


class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;


    // Sobrescrever o método de criação para evitar a criação padrão
    public function create(bool $another = false): void
    {
        $this->creating = true;

        $this->callHook('beforeValidate');
        $this->callHook('afterValidate');

        $data = $this->form->getState();

        // Se não estamos gerando múltiplas permissões, usamos o comportamento padrão
        if (!isset($data['generate_multiple']) || !$data['generate_multiple']) {
            $this->callHook('beforeCreate');

            $record = $this->handleRecordCreation($data);

            $this->record = $record;

            $this->callHook('afterCreate');

            $this->getCreatedNotification()?->send();

            if ($another) {
                // Ensure that the form record is not set, so that a new record can be created.
                $this->form->fill();
                $this->record = null;

                $this->creating = false;

                return;
            }

            $this->redirect($this->getRedirectUrl());

            return;
        }

        // Processamento para múltiplas permissões
        $resourceName = $data['resource_name'] ?? '';
        if (empty($resourceName)) {
            Notification::make()
                ->title('Erro ao criar permissões')
                ->body('O nome do recurso é obrigatório para gerar permissões múltiplas.')
                ->danger()
                ->send();
            return;
        }

        // Tipos de permissões a criar
        $permissionTypes = [];
        if ($data['create_permission'] ?? false) $permissionTypes[] = 'Criar';
        if ($data['view_permission'] ?? false) $permissionTypes[] = 'Visualizar';
        if ($data['edit_permission'] ?? false) $permissionTypes[] = 'Editar';
        if ($data['delete_permission'] ?? false) $permissionTypes[] = 'Deletar';

        if (empty($permissionTypes)) {
            Notification::make()
                ->title('Nenhuma permissão selecionada')
                ->body('Selecione pelo menos um tipo de permissão para criar.')
                ->warning()
                ->send();
            return;
        }

        $createdPermissions = [];
        $firstPermission = null;

        // Cria as permissões selecionadas
        foreach ($permissionTypes as $type) {
            $permissionName = "{$type} {$resourceName}";
            $permission = Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            if (!$firstPermission) {
                $firstPermission = $permission;
            }

            $createdPermissions[] = $permissionName;
        }

        // Notificação de sucesso
        Notification::make()
            ->title('Permissões criadas com sucesso')
            ->body('Foram criadas ' . count($createdPermissions) . ' permissões: ' . implode(', ', $createdPermissions))
            ->success()
            ->send();

        // Redirecionar para a lista de permissões
        $this->redirect($this->getResource()::getUrl('index'));
    }

    // Este método é chamado apenas para a criação de permissão única
    protected function handleRecordCreation(array $data): Model
    {
        // Só será chamado no modo de permissão única
        return static::getModel()::create([
            'name' => $data['name'] ?? '',  // Adicionamos fallback para evitar o erro
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
