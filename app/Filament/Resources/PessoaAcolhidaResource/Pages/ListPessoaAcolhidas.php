<?php

namespace App\Filament\Resources\PessoaAcolhidaResource\Pages;

use App\Filament\Resources\PessoaAcolhidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPessoaAcolhidas extends ListRecords
{
    protected static string $resource = PessoaAcolhidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
