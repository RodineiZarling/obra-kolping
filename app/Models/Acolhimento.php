<?php

namespace App\Models;

use App\Models\Concerns\AppliesEmpresaScope;
use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acolhimento extends Model
{
    use HasFactory, AppliesEmpresaScope, HasDocuments;

    protected $table = 'acolhimentos';

    protected $fillable = [
        'empresa_id',
        'pessoa_acolhida_id',
        'responsavel_id',
        'data_acolhimento',
        'origem_encaminhamento',
        'motivo_acolhimento',
        'descricao_situacao',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_acolhimento' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(PessoaAcolhida::class, 'pessoa_acolhida_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    // Relacionamentos normalizados
    public function familiares(): HasMany
    {
        return $this->hasMany(AcolhimentoFamiliar::class);
    }

    public function procedimentos(): HasMany
    {
        return $this->hasMany(AcolhimentoProcedimento::class);
    }

    public function diarios(): HasMany
    {
        return $this->hasMany(AcolhimentoDiario::class);
    }

    // Trait HasDocuments fornece o relacionamento documents() e o diretório padrão
}
