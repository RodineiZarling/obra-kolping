<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratoLocacaoPercentualHonorariosPadraoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preenche_percentual_honorarios_do_contrato_com_base_no_padrao_da_unidade(): void
    {
        config()->set('locacao.honorarios_percentual', '15.00');

        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create([
            'imovel_id' => $imovel->id,
            'identificador' => 'Ap 101',
            'percentual_honorarios_padrao' => '7.25',
        ]);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => null,
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $this->assertSame('7.25', $contrato->percentual_honorarios);
    }
}
