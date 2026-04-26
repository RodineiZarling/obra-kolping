<?php
namespace App\Models;

use Spatie\Permission\Models\Role as BaseRole;
use Illuminate\Support\Facades\App;

class Role extends BaseRole
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
