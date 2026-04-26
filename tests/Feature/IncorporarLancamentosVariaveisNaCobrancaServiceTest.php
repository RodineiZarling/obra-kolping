<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\LocacaoLancamentoVariavel;
use App\Models\UnidadeImovel;
use App\Services\GerarCobrancaMensalService;
use App\Services\IncorporarLancamentosVariaveisNaCobrancaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncorporarLancamentosVariaveisNaCobrancaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_incorpora_lancamentos_abertos_e_nao_duplica_em_reexecucao(): void
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

        $lancamento = LocacaoLancamentoVariavel::create([
            'contrato_locacao_id' => $contrato->id,
            'unidade_imovel_id' => $unidade->id,
            'competencia' => '2026-03',
            'tipo' => 'agua',
            'descricao' => 'Água março',
            'valor' => '35.50',
            'status' => 'aberto',
        ]);

        $service = new IncorporarLancamentosVariaveisNaCobrancaService();

        $incorporados1 = $service->executar($cobranca);
        $this->assertSame(1, $incorporados1);

        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'agua',
            'origem' => 'manual',
            'valor_total' => 35.50,
        ]);

        $lancamento->refresh();
        $this->assertSame('incorporado', $lancamento->status);
        $this->assertSame($cobranca->id, $lancamento->locacao_cobranca_id);
        $this->assertNotNull($lancamento->locacao_cobranca_item_id);

        // Idempotência: ao rodar novamente não deve criar novo item nem alterar contagens.
        $itensAntes = $cobranca->itens()->count();
        $incorporados2 = $service->executar($cobranca);
        $this->assertSame(0, $incorporados2);
        $this->assertSame($itensAntes, $cobranca->refresh()->itens()->count());
    }
}
