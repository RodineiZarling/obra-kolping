<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\ContasPagar;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\ApurarRepasseLocadorService;
use App\Services\GerarCobrancaMensalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ApurarRepasseLocadorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apura_repasse_idempotente_por_contrato_e_competencia_e_atualiza_valores(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => null,
            'valor_aluguel' => '1000.00',
            'valor_condominio' => '0.00',
            'valor_taxa_lixo' => '0.00',
            'valor_iptu' => '0.00',
            'dia_vencimento' => 10,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCATARIO',
            'ativo' => true,
        ]);

        $cobranca = (new GerarCobrancaMensalService())->executar($contrato, '2026-03');

        $service = new ApurarRepasseLocadorService();

        $repasse1 = $service->executar($cobranca, '10.00', '50.00', '25.00');

        $this->assertDatabaseCount('locacao_repasses', 1);
        $this->assertDatabaseHas('locacao_repasses', [
            'id' => $repasse1->id,
            'contrato_locacao_id' => $contrato->id,
            'locador_id' => $locador->id,
            'competencia' => '2026-03',
            'locacao_cobranca_id' => $cobranca->id,
            'status' => 'apurado',
        ]);

        $repasse1->refresh();
        $this->assertSame('1000.00', $repasse1->valor_aluguel_base);
        $this->assertSame('100.00', $repasse1->valor_honorarios_imobiliaria);
        $this->assertSame('50.00', $repasse1->valor_taxas_proprietario);
        $this->assertSame('25.00', $repasse1->valor_outros_descontos);
        $this->assertSame('825.00', $repasse1->valor_liquido_repassar);

        // Deve criar também a conta a pagar do repasse.
        $this->assertNotNull($repasse1->contas_pagar_id);
        $this->assertDatabaseCount('contas_a_pagar', 1);

        $contasPagar1 = ContasPagar::findOrFail($repasse1->contas_pagar_id);
        $this->assertSame($locador->id, $contasPagar1->locador_id);
        $this->assertSame($contrato->id, $contasPagar1->contrato_locacao_id);
        $this->assertSame($unidade->id, $contasPagar1->unidade_imovel_id);
        $this->assertEqualsWithDelta(825.00, (float) $contasPagar1->valor_total, 0.01);
        $this->assertTrue($contasPagar1->parcelas()->exists());

        // Idempotência: reapurar não cria novo registro.
        $repasse2 = $service->executar($cobranca->refresh(), '10.00', '50.00', '25.00');
        $this->assertSame($repasse1->id, $repasse2->id);
        $this->assertDatabaseCount('locacao_repasses', 1);
        $this->assertDatabaseCount('contas_a_pagar', 1);
        $this->assertSame((int) $repasse1->contas_pagar_id, (int) $repasse2->contas_pagar_id);

        // Atualização: altera o percentual e taxas.
        $repasse3 = $service->executar($cobranca->refresh(), '12.50', '60.00', '0.00');
        $this->assertSame($repasse1->id, $repasse3->id);
        $repasse3->refresh();
        $this->assertSame('125.00', $repasse3->valor_honorarios_imobiliaria);
        $this->assertSame('60.00', $repasse3->valor_taxas_proprietario);
        $this->assertSame('815.00', $repasse3->valor_liquido_repassar);

        $contasPagar3 = ContasPagar::findOrFail($repasse3->contas_pagar_id);
        $this->assertEqualsWithDelta(815.00, (float) $contasPagar3->valor_total, 0.01);
    }

    public function test_lanca_excecao_para_competencia_invalida(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => null,
            'valor_aluguel' => '1000.00',
            'valor_condominio' => '0.00',
            'valor_taxa_lixo' => '0.00',
            'valor_iptu' => '0.00',
            'dia_vencimento' => 10,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCATARIO',
            'ativo' => true,
        ]);

        $cobranca = (new GerarCobrancaMensalService())->executar($contrato, '2026-03');
        $cobranca->competencia = '2026/03';
        $cobranca->save();

        $this->expectException(InvalidArgumentException::class);
        (new ApurarRepasseLocadorService())->executar($cobranca->refresh(), '10.00');
    }

    public function test_usa_percentual_do_contrato_quando_nao_informado_explicitamente(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => null,
            'valor_aluguel' => '1000.00',
            'valor_condominio' => '0.00',
            'valor_taxa_lixo' => '0.00',
            'valor_iptu' => '0.00',
            'dia_vencimento' => 10,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCATARIO',
            'percentual_honorarios' => '8.50',
            'ativo' => true,
        ]);

        $cobranca = (new GerarCobrancaMensalService())->executar($contrato, '2026-03');

        $repasse = (new ApurarRepasseLocadorService())->executar($cobranca, null, '0.00', '0.00');

        $this->assertSame('85.00', $repasse->valor_honorarios_imobiliaria);
        $this->assertSame('915.00', $repasse->valor_liquido_repassar);
    }

    public function test_usa_percentual_padrao_da_unidade_quando_contrato_nao_tem_percentual(): void
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
            'valor_condominio' => '0.00',
            'valor_taxa_lixo' => '0.00',
            'valor_iptu' => '0.00',
            'dia_vencimento' => 10,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCATARIO',
            'ativo' => true,
        ]);

        // Simula contrato legado (sem percentual preenchido).
        $contrato->percentual_honorarios = null;
        $contrato->save();

        $cobranca = (new GerarCobrancaMensalService())->executar($contrato->refresh(), '2026-03');

        $repasse = (new ApurarRepasseLocadorService())->executar($cobranca, null, '0.00', '0.00');

        $this->assertSame('7.25', (string) $unidade->percentual_honorarios_padrao);
        $this->assertSame('72.50', $repasse->valor_honorarios_imobiliaria);
        $this->assertSame('927.50', $repasse->valor_liquido_repassar);
    }
}
