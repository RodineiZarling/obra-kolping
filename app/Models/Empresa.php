<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nome',
        'fantasia',
        'cpfcnpj',
        'rgie',
        'creci',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'postal_code',
        'email',
        'telefone',
        'celular',
        'logo',
        'status',
    ];

    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class, 'empresa_user', 'empresa_id', 'user_id')
            ->withTimestamps();
    }
}
