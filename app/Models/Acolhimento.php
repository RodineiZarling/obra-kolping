<?php

namespace App\Models;

use App\Models\Concerns\AppliesEmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Acolhimento extends Model
{
    use HasFactory, AppliesEmpresaScope;

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
        'composicao_familiar',
        'procedimentos',
        'diario_acompanhamento',
    ];

    protected $casts = [
        'data_acolhimento' => 'date',
        'composicao_familiar' => 'array',
        'procedimentos' => 'array',
        'diario_acompanhamento' => 'array',
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
