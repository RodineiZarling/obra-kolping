<?php

namespace App\Models;

use App\Models\Concerns\AppliesEmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PessoaAcolhida extends Model
{
    use HasFactory, AppliesEmpresaScope;

    protected $table = 'pessoas_acolhidas';

    protected $fillable = [
        'empresa_id',
        'nome',
        'cpf',
        'rg',
        'data_nascimento',
        'sexo',
        'telefone',
        'email',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'situacao',
        'observacoes',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function acolhimentos(): HasMany
    {
        return $this->hasMany(Acolhimento::class, 'pessoa_acolhida_id');
    }
}
