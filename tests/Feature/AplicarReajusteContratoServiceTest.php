<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\IndiceReajuste;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\AplicarReajusteContratoService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AplicarReajusteContratoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplica_reajuste_apos_12_meses_e_registra_historico_e_atualiza_valor_vigente(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-15',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'indice_reajuste_id' => $indice->id,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $reajuste = $service->executar($contrato, '01/2027');

        $this->assertDatabaseCount('contrato_locacao_reajustes', 1);
        $this->assertDatabaseHas('contrato_locacao_reajustes', [
            'id' => $reajuste->id,
            'contrato_locacao_id' => $contrato->id,
            'competencia_aplicacao' => '2027-01',
            'indice_reajuste_id' => $indice->id,
            'percentual_aplicado' => 10.00,
            'valor_anterior' => 1000.00,
            'valor_novo' => 1100.00,
        ]);

        $this->assertSame('1100.00', $contrato->refresh()->valor_aluguel);
    }

    public function test_bloqueia_reajuste_antes_de_12_meses_desde_o_inicio(): void
    {
        $this->expectException(DomainException::class);

        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'indice_reajuste_id' => $indice->id,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $service->executar($contrato, '12/2026');
    }

    public function test_idempotente_na_mesma_competencia_nao_duplica_historico(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'indice_reajuste_id' => $indice->id,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $r1 = $service->executar($contrato, '01/2027');
        $r2 = $service->executar($contrato->refresh(), '01/2027');

        $this->assertSame($r1->id, $r2->id);
        $this->assertDatabaseCount('contrato_locacao_reajustes', 1);
    }

    public function test_rejeita_contrato_sem_indice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $service->executar($contrato, '01/2027');
    }

    public function test_permire_aplicar_reajuste_com_indice_informado_mesmo_se_contrato_nao_tiver_indice_padrao(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-15',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $reajuste = $service->executar($contrato, '01/2027', null, $indice->id);

        $this->assertDatabaseHas('contrato_locacao_reajustes', [
            'id' => $reajuste->id,
            'contrato_locacao_id' => $contrato->id,
            'competencia_aplicacao' => '2027-01',
            'indice_reajuste_id' => $indice->id,
            'percentual_aplicado' => 10.00,
            'valor_anterior' => 1000.00,
            'valor_novo' => 1100.00,
        ]);
    }

    public function test_percentual_manual_tem_prioridade_sobre_o_percentual_do_indice_informado(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-15',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'indice_reajuste_id' => $indice->id,
            'ativo' => true,
        ]);

        $service = new AplicarReajusteContratoService();
        $reajuste = $service->executar($contrato, '01/2027', null, $indice->id, '12.50');

        $this->assertDatabaseHas('contrato_locacao_reajustes', [
            'id' => $reajuste->id,
            'contrato_locacao_id' => $contrato->id,
            'competencia_aplicacao' => '2027-01',
            'indice_reajuste_id' => $indice->id,
            'percentual_aplicado' => 12.50,
            'valor_anterior' => 1000.00,
            'valor_novo' => 1125.00,
        ]);
    }

    public function test_lanca_erro_se_reajuste_existente_na_mesma_competencia_for_inconsistente(): void
    {
        $this->expectException(RuntimeException::class);

        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $indice = IndiceReajuste::create(['nome' => 'Índice X', 'percentual' => '10.00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'indice_reajuste_id' => $indice->id,
            'ativo' => true,
        ]);

        // Cria histórico inconsistente para a mesma competência.
        $contrato->reajustes()->create([
            'competencia_aplicacao' => '2027-01',
            'indice_reajuste_id' => $indice->id,
            'percentual_aplicado' => '10.00',
            'valor_anterior' => '1000.00',
            'valor_novo' => '9999.99',
        ]);

        $service = new AplicarReajusteContratoService();
        $service->executar($contrato->refresh(), '2027-01');
    }
}
