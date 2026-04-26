<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\GerarCobrancaMensalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GerarCobrancaMensalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_cobranca_idempotente_com_itens_automaticos_e_vencimento_calculado(): void
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
            'valor_condominio' => '200.00',
            'valor_taxa_lixo' => '50.00',
            'valor_iptu' => '80.00',
            'dia_vencimento' => 31,
            'responsavel_condominio' => 'LOCATARIO',
            'responsavel_taxa_lixo' => 'LOCATARIO',
            'responsavel_iptu' => 'LOCADOR',
            'ativo' => true,
        ]);

        $service = new GerarCobrancaMensalService();

        $cobranca1 = $service->executar($contrato, '2026-02');

        $this->assertDatabaseCount('locacao_cobrancas', 1);
        $this->assertDatabaseHas('locacao_cobrancas', [
            'id' => $cobranca1->id,
            'contrato_locacao_id' => $contrato->id,
            'competencia' => '2026-02',
            // 2026-02 tem 28 dias; deve ajustar para o último dia do mês.
            'vencimento' => '2026-02-28',
        ]);

        // Itens automáticos: aluguel, condominio, taxa_lixo entram; iptu não (responsável LOCADOR).
        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'aluguel',
            'origem' => 'automatico',
            'valor_total' => 1000.00,
        ]);
        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'condominio',
            'origem' => 'automatico',
            'valor_total' => 200.00,
        ]);
        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'taxa_lixo',
            'origem' => 'automatico',
            'valor_total' => 50.00,
        ]);
        $this->assertDatabaseMissing('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'iptu',
            'origem' => 'automatico',
        ]);

        // Idempotência: executar novamente não deve duplicar cobrança nem itens.
        $cobranca2 = $service->executar($contrato, '2026-02');
        $this->assertSame($cobranca1->id, $cobranca2->id);
        $this->assertDatabaseCount('locacao_cobrancas', 1);
        $this->assertDatabaseCount('locacao_cobranca_itens', 3);

        // Atualização: se o valor do condomínio mudar e o IPTU virar responsabilidade do locatário,
        // o item deve ser atualizado/criado corretamente.
        $contrato->update([
            'valor_condominio' => '250.00',
            'responsavel_iptu' => 'LOCATARIO',
        ]);

        $cobranca3 = $service->executar($contrato->refresh(), '2026-02');
        $this->assertSame($cobranca1->id, $cobranca3->id);
        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'condominio',
            'origem' => 'automatico',
            'valor_total' => 250.00,
        ]);
        $this->assertDatabaseHas('locacao_cobranca_itens', [
            'locacao_cobranca_id' => $cobranca1->id,
            'tipo' => 'iptu',
            'origem' => 'automatico',
            'valor_total' => 80.00,
        ]);
    }

    public function test_rejeita_competencia_invalida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = new GerarCobrancaMensalService();
        $contrato = new ContratoLocacao();

        $service->executar($contrato, '2026-13');
    }
}
