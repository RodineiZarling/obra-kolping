<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\EmitirContasReceberDaCobrancaService;
use App\Services\GerarCobrancaMensalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EmitirContasReceberDaCobrancaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_emite_contas_receber_idempotente_e_atualiza_vinculo_na_cobranca(): void
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

        $service = new EmitirContasReceberDaCobrancaService();

        $conta1 = $service->executar($cobranca);

        $this->assertDatabaseCount('contas_a_receber', 1);
        $this->assertDatabaseHas('contas_a_receber', [
            'id' => $conta1->id,
            'contrato_locacao_id' => $contrato->id,
            'unidade_imovel_id' => $unidade->id,
            'competencia' => '2026-03',
            'origem' => 'locacao',
            'locacao_cobranca_id' => $cobranca->id,
        ]);

        $this->assertDatabaseHas('locacao_cobrancas', [
            'id' => $cobranca->id,
            'contas_receber_id' => $conta1->id,
        ]);

        // Deve gerar ao menos 1 parcela (1x) para o título emitido.
        $this->assertDatabaseCount('contas_receber_parcelas', 1);
        $this->assertDatabaseHas('contas_receber_parcelas', [
            'contas_receber_id' => $conta1->id,
            'parcela' => 1,
            'total_parcelas' => 1,
        ]);

        // Idempotência: não cria novo título.
        $conta2 = $service->executar($cobranca->refresh());
        $this->assertSame($conta1->id, $conta2->id);
        $this->assertDatabaseCount('contas_a_receber', 1);
        $this->assertDatabaseCount('contas_receber_parcelas', 1);

        // Atualização: se o total mudar, o título deve refletir.
        $cobranca->refresh();
        $cobranca->valor_total = '1200.00';
        $cobranca->save();

        $conta3 = $service->executar($cobranca->refresh());
        $this->assertSame($conta1->id, $conta3->id);
        $this->assertDatabaseHas('contas_a_receber', [
            'id' => $conta1->id,
            'valor' => 1200.00,
            'valor_total' => 1200.00,
        ]);
    }

    public function test_lanca_excecao_quando_contas_receber_ja_esta_vinculado_a_outra_cobranca(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade1 = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $unidade2 = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 102']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato1 = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade1->id,
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

        $contrato2 = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade2->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => null,
            'valor_aluguel' => '1100.00',
            'valor_condominio' => '0.00',
            'valor_taxa_lixo' => '0.00',
            'valor_iptu' => '0.00',
            'dia_vencimento' => 10,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCATARIO',
            'ativo' => true,
        ]);

        $gerar = new GerarCobrancaMensalService();
        $cobranca1 = $gerar->executar($contrato1, '2026-03');
        $cobranca2 = $gerar->executar($contrato2, '2026-03');

        $service = new EmitirContasReceberDaCobrancaService();
        $conta = $service->executar($cobranca1);

        // Força um vínculo inconsistente (simula dado corrompido) e garante validação defensiva.
        $cobranca2->contas_receber_id = $conta->id;
        $cobranca2->save();

        $this->expectException(InvalidArgumentException::class);
        $service->executar($cobranca2->refresh());
    }
}
