<?php
namespace App\Models;

use Spatie\Permission\Models\Permission as BasePermission;
use Illuminate\Support\Facades\App;

class Permission extends BasePermission
{
    public function getConnectionName(): ?string
    {
        // Verificar se estamos em um contexto de tenant
        if (App::bound('tenancy.tenant')) {
            return tenant()->getConnectionName();
        }

        return config('database.default');
    }
}
