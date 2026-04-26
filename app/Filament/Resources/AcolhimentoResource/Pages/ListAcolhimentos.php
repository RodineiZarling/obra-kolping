<?php

namespace App\Filament\Resources\AcolhimentoResource\Pages;

use App\Filament\Resources\AcolhimentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcolhimentos extends ListRecords
{
    protected static string $resource = AcolhimentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
