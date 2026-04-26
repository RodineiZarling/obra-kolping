<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasRoles, HasFactory, Notifiable;

    //Sobre escreva o método getRoleNames para garantir que roles seja inicializado
    public function getRoleNames()
    {
        if ($this->roles === null) {
            $this->roles = $this->roles()->get();
        }

        return $this->roles->pluck('name');
    }

    // Sobre escreva o método hasRole para verificar se roles está inicializado
    public function hasRole($roles, $guard = null)
    {
        if (is_string($roles) && ($this->roles === null)) {
            $this->roles = $this->roles()->get();
        }

        if ($this->roles === null) {
            return false;
        }

        if (is_string($roles)) {
            return $guard
                ? $this->roles->where('guard_name', $guard)->contains('name', $roles)
                : $this->roles->contains('name', $roles);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }
            return false;
        }

        return $roles->intersect($guard ? $this->roles->where('guard_name', $guard) : $this->roles)->isNotEmpty();
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cpfcnpj',
        'codigo_caixa',
        'status',
        'empresa',
        'nivel_acesso',
        'empresa_ativa',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        // Evite qualquer referência a $this->roles antes de retornar o relacionamento
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    /**
     * Unidades (empresas) vinculadas ao usuário (nível 2).
     */
    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user', 'user_id', 'empresa_id')
            ->withTimestamps();
    }

}
