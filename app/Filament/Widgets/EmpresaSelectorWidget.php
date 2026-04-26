<?php

namespace App\Filament\Widgets;

use App\Traits\HasEmpresaAtiva;
use Filament\Widgets\Widget;

class EmpresaSelectorWidget extends Widget
{
    use HasEmpresaAtiva;

    protected static string $view = 'filament.widgets.empresa-selector';

    public ?int $empresaAtiva = null;

    public function mount(): void
    {
        $this->empresaAtiva = $this->getEmpresaAtivaId();
    }

    public function updatedEmpresaAtiva($value): void
    {
        $id = $value !== null && $value !== '' ? (int) $value : null;
        $this->setEmpresaAtivaId($id);
    }

    protected function getViewData(): array
    {
        return [
            'empresas' => $this->getEmpresasDisponiveis(),
        ];
    }
}
