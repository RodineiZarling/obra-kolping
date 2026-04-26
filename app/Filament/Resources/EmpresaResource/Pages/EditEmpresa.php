<?php

namespace App\Filament\Resources\EmpresaResource\Pages;

use App\Filament\Resources\EmpresaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpresa extends EditRecord
{
    protected static string $resource = EmpresaResource::class;

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
}
