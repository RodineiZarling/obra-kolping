<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Document $document): bool
    {
        if (isset($user->nivel_acesso) && (int) $user->nivel_acesso === 0) {
            return true;
        }

        $documentable = $document->documentable;
        if ($documentable) {
            try {
                if (Gate::forUser($user)->check('view', $documentable)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Se não houver policy para a entidade, mantém o comportamento padrão (permitir).
            }
        }

        return true;
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        if (isset($user->nivel_acesso) && (int) $user->nivel_acesso === 0) {
            return true;
        }

        $documentable = $document->documentable;
        if ($documentable) {
            try {
                if (Gate::forUser($user)->check('delete', $documentable)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Se não houver policy para a entidade, mantém o comportamento padrão (permitir).
            }
        }

        return true;
    }
}
