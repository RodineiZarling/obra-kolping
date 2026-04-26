<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\LocacaoCobranca;
use App\Models\LocacaoCobrancaItem;
use App\Models\UnidadeImovel;
use App\Services\CalcularMultaJurosDaCobrancaService;
use App\Services\GerarCobrancaMensalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalcularMultaJurosDaCobrancaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_quando_nao_vencida_remove_itens_de_multa_e_juros_do_sistema(): void
    {
        $cobranca = $this->criarCobrancaBase('2026-03', '2026-03-10');

        // Força existência de penalidades e um valor total inconsistentes.
        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'multa',
            'origem' => 'sistema',
            'descricao' => 'Multa por atraso',
            'quantidade' => null,
            'valor_unitario' => '100.00',
            'valor_total' => '100.00',
            'metadados' => null,
        ]);
        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'juros',
            'origem' => 'sistema',
            'descricao' => 'Juros por atraso',
            'quantidade' => null,
            'valor_unitario' => '10.00',
            'valor_total' => '10.00',
            'metadados' => null,
        ]);

        $service = new CalcularMultaJurosDaCobrancaService();
        $atualizada = $service->executar($cobranca, Carbon::parse('2026-03-09'));

        $this->assertDatabaseMissing('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $atualizada->id,
            'tipo' => 'multa',
            'origem' => 'sistema',
        ]);
        $this->assertDatabaseMissing('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $atualizada->id,
            'tipo' => 'juros',
            'origem' => 'sistema',
        ]);

        // Total deve voltar a ser igual ao subtotal (sem penalidades).
        $this->assertSame((string) $atualizada->valor_subtotal_itens, (string) $atualizada->valor_total);
        $this->assertSame('0.00', (string) $atualizada->valor_multa);
        $this->assertSame('0.00', (string) $atualizada->valor_juros);
    }

    public function test_quando_vencida_cria_ou_atualiza_itens_de_multa_e_juros_de_forma_idempotente(): void
    {
        $cobranca = $this->criarCobrancaBase('2026-03', '2026-03-10');

        $service = new CalcularMultaJurosDaCobrancaService();
        $atualizada = $service->executar($cobranca, Carbon::parse('2026-04-09'));

        // 30 dias de atraso: multa 10% sobre 1000.00 = 100.00; juros 1% ao mês proporcional (30/30) = 10.00
        $this->assertSame('100.00', (string) $atualizada->valor_multa);
        $this->assertSame('10.00', (string) $atualizada->valor_juros);
        $this->assertSame('1110.00', (string) $atualizada->valor_total);

        // Idempotência: reexecutar para a mesma data não cria duplicidade e mantém valores.
        $atualizada2 = $service->executar($atualizada, Carbon::parse('2026-04-09'));
        $this->assertSame((string) $atualizada->valor_total, (string) $atualizada2->valor_total);

        $this->assertDatabaseCount('locacao_cobranca_itens', 3); // aluguel + multa + juros
    }

    public function test_quando_contrato_tem_percentuais_configurados_usa_multas_e_juros_do_contrato_com_fallback_para_padrao(): void
    {
        $cobranca = $this->criarCobrancaBase('2026-03', '2026-03-10', multaPercentual: '5.00', jurosPercentualAoMes: '2.00');

        $service = new CalcularMultaJurosDaCobrancaService();
        $atualizada = $service->executar($cobranca, Carbon::parse('2026-04-09'));

        // 30 dias de atraso: multa 5% sobre 1000.00 = 50.00; juros 2% ao mês proporcional (30/30) = 20.00
        $this->assertSame('50.00', (string) $atualizada->valor_multa);
        $this->assertSame('20.00', (string) $atualizada->valor_juros);
        $this->assertSame('1070.00', (string) $atualizada->valor_total);
    }

    public function test_nao_altera_cobranca_com_status_paga(): void
    {
        $cobranca = $this->criarCobrancaBase('2026-03', '2026-03-10');
        $cobranca->status = 'paga';
        $cobranca->save();

        $service = new CalcularMultaJurosDaCobrancaService();
        $atualizada = $service->executar($cobranca, Carbon::parse('2026-04-09'));

        $this->assertSame('paga', (string) $atualizada->status);
        $this->assertSame('0.00', (string) $atualizada->valor_multa);
        $this->assertSame('0.00', (string) $atualizada->valor_juros);

        $this->assertDatabaseMissing('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $atualizada->id,
            'tipo' => 'multa',
            'origem' => 'sistema',
        ]);
        $this->assertDatabaseMissing('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $atualizada->id,
            'tipo' => 'juros',
            'origem' => 'sistema',
        ]);
    }

    private function criarCobrancaBase(
        string $competencia,
        string $vencimento,
        ?string $multaPercentual = null,
        ?string $jurosPercentualAoMes = null,
    ): LocacaoCobranca
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contratoData = [
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
        ];

        if ($multaPercentual !== null) {
            $contratoData['multa_percentual'] = $multaPercentual;
        }
        if ($jurosPercentualAoMes !== null) {
            $contratoData['juros_percentual_ao_mes'] = $jurosPercentualAoMes;
        }

        $contrato = ContratoLocacao::create($contratoData);

        $cobranca = (new GerarCobrancaMensalService())->executar($contrato, $competencia);
        $cobranca->vencimento = $vencimento;
        $cobranca->save();

        return $cobranca->refresh();
    }
}
