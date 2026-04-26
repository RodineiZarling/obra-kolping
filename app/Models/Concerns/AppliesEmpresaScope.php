<?php

namespace App\Models\Concerns;

use App\Traits\HasEmpresaAtiva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait AppliesEmpresaScope
{
    use HasEmpresaAtiva;

    /**
     * Aplica filtro por empresa conforme regras de nível e empresa ativa na sessão.
     * Espera que a tabela possua a coluna `empresa` (ou `empresa_id`).
     */
    public function scopeEmpresaFilter(Builder $query): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return $query;
        }

        $empresaColumn = $this->getTable() . '.empresa';
        if (! array_key_exists('empresa', $this->getAttributes())) {
            // fallback comum: empresa_id
            $empresaColumn = $this->getTable() . '.empresa_id';
        }

        $nivel = (int) ($user->nivel_acesso ?? 2);
        $ativa = session('empresa_ativa_id');

        if (in_array($nivel, [0, 1], true)) {
            // nível 0/1: se tiver ativa -> filtra, senão retorna tudo
            if ($ativa !== null && $ativa !== '') {
                return $query->where($empresaColumn, (int) $ativa);
            }
            return $query;
        }

        // nível 2: sempre limitar às empresas vinculadas
        $empresaIds = $user->empresas()->pluck('empresas.id');
        if ($ativa !== null && $ativa !== '') {
            $ativa = (int) $ativa;
            // valida se pertence ao usuário
            if ($empresaIds->contains($ativa)) {
                return $query->where($empresaColumn, $ativa);
            }
        }

        return $query->whereIn($empresaColumn, $empresaIds);
    }
}
