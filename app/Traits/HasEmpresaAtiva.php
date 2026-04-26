<?php

namespace App\Traits;

use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

trait HasEmpresaAtiva
{
    protected function empresaAtivaSessionKey(): string
    {
        return 'empresa_ativa_id';
    }

    public function getEmpresaAtivaId(): ?int
    {
        $id = session($this->empresaAtivaSessionKey());
        return $id !== null ? (int) $id : null;
    }

    public function setEmpresaAtivaId(?int $empresaId): void
    {
        if ($empresaId === null) {
            session()->forget($this->empresaAtivaSessionKey());
            return;
        }

        // Apenas grava se o usuário tiver acesso
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $nivel = (int) ($user->nivel_acesso ?? 2);
        if (in_array($nivel, [0,1], true)) {
            session([$this->empresaAtivaSessionKey() => (int) $empresaId]);
            return;
        }

        // nível 2: valida se a empresa pertence ao usuário
        $has = $user->empresas()->whereKey($empresaId)->exists();
        if ($has) {
            session([$this->empresaAtivaSessionKey() => (int) $empresaId]);
        }
    }

    /**
     * Retorna as empresas disponíveis conforme o nível do usuário autenticado.
     */
    public function getEmpresasDisponiveis()
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        $nivel = (int) ($user->nivel_acesso ?? 2);
        if (in_array($nivel, [0,1], true)) {
            return Empresa::query()->orderBy('id')->get(['id', 'nome']);
        }

        return $user->empresas()->orderBy('empresas.id')->get(['empresas.id', 'empresas.nome']);
    }
}
