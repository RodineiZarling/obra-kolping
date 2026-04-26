<?php

namespace App\Livewire;

use App\Traits\HasEmpresaAtiva;
use Livewire\Component;

class EmpresaTopbarSelector extends Component
{
    use HasEmpresaAtiva;

    public ?int $empresaAtiva = null;
    public $empresas = [];

    public function mount(): void
    {
        $this->empresaAtiva = $this->getEmpresaAtivaId();
        $this->empresas = $this->getEmpresasDisponiveis();
    }

    public function updatedEmpresaAtiva($value): void
    {
        $id = $value !== null && $value !== '' ? (int) $value : null;
        $this->setEmpresaAtivaId($id);
    }

    public function render()
    {
        return view('livewire.empresa-topbar-selector');
    }
}
