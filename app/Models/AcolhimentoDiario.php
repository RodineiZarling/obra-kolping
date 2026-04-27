<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AcolhimentoDiario extends Model
{
    use HasFactory, HasDocuments; // preparado para anexos futuros

    protected $table = 'acolhimento_diarios';

    protected $fillable = [
        'acolhimento_id',
        'data',
        'area',
        'descricao',
        'responsavel_id',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function acolhimento(): BelongsTo
    {
        return $this->belongsTo(Acolhimento::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    // Destinatários (pessoa acolhida e/ou familiares) via relação polimórfica many-to-many
    public function destinatariosPessoas(): MorphToMany
    {
        return $this->morphToMany(PessoaAcolhida::class, 'destinatario', 'acolhimento_diario_destinatarios', 'diario_id', 'destinatario_id');
    }

    public function destinatariosFamiliares(): MorphToMany
    {
        return $this->morphToMany(AcolhimentoFamiliar::class, 'destinatario', 'acolhimento_diario_destinatarios', 'diario_id', 'destinatario_id');
    }
}
