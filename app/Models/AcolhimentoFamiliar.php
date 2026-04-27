<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcolhimentoFamiliar extends Model
{
    use HasFactory;

    protected $table = 'acolhimento_familiares';

    protected $fillable = [
        'acolhimento_id',
        'nome',
        'parentesco',
        'idade',
        'telefone',
        'ocupacao',
        'observacoes',
    ];

    public function acolhimento(): BelongsTo
    {
        return $this->belongsTo(Acolhimento::class);
    }
}
