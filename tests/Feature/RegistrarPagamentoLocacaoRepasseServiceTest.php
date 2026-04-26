<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\ContasPagar;
use App\Models\FormaPagamento;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\ApurarRepasseLocadorService;
use App\Services\GerarCobrancaMensalService;
use App\Services\RegistrarPagamentoLocacaoRepasseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RegistrarPagamentoLocacaoRepasseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_pagamento_sem_diferenca_e_zerar_ajuste_e_motivo(): void
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

        $repasse = (new ApurarRepasseLocadorService())->executar($cobranca, '10.00', '50.00', '25.00');
        $repasse->refresh();
        $this->assertSame('825.00', $repasse->valor_liquido_repassar);

        $formaPagamento = FormaPagamento::create([
            'empresa' => 1,
            'nome' => 'PIX',
            'status' => 1,
        ]);

        /** @var RegistrarPagamentoLocacaoRepasseService $service */
        $service = app(RegistrarPagamentoLocacaoRepasseService::class);
        $resultado = $service->executar($repasse, [
            'forma_pagamento_id' => $formaPagamento->id,
            'data_pagamento' => '2026-03-15',
            'valor_liquido_final' => 825.00,
        ]);

        $resultado->refresh();
        $this->assertSame('825.00', $resultado->valor_liquido_final);
        $this->assertSame('0.00', $resultado->valor_ajuste_manual);
        $this->assertNull($resultado->motivo_ajuste_manual);
        $this->assertSame('repassado', (string) $resultado->status);

        $conta = ContasPagar::findOrFail($resultado->contas_pagar_id);
        $this->assertEqualsWithDelta(825.00, (float) $conta->valor_total, 0.01);
        $this->assertSame(2, (int) $conta->status);
    }

    public function test_exige_motivo_quando_houver_diferenca(): void
    {
        $this->expectException(RuntimeException::class);

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
        $repasse = (new ApurarRepasseLocadorService())->executar($cobranca, '10.00', '50.00', '25.00');

        $formaPagamento = FormaPagamento::create([
            'empresa' => 1,
            'nome' => 'PIX',
            'status' => 1,
        ]);

        /** @var RegistrarPagamentoLocacaoRepasseService $service */
        $service = app(RegistrarPagamentoLocacaoRepasseService::class);
        $service->executar($repasse, [
            'forma_pagamento_id' => $formaPagamento->id,
            'data_pagamento' => '2026-03-15',
            'valor_liquido_final' => 824.00,
        ]);
    }

    public function test_registra_pagamento_com_diferenca_e_persiste_ajuste_e_motivo(): void
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
        $repasse = (new ApurarRepasseLocadorService())->executar($cobranca, '10.00', '50.00', '25.00');
        $repasse->refresh();

        $formaPagamento = FormaPagamento::create([
            'empresa' => 1,
            'nome' => 'PIX',
            'status' => 1,
        ]);

        /** @var RegistrarPagamentoLocacaoRepasseService $service */
        $service = app(RegistrarPagamentoLocacaoRepasseService::class);
        $resultado = $service->executar($repasse, [
            'forma_pagamento_id' => $formaPagamento->id,
            'data_pagamento' => '2026-03-15',
            'valor_liquido_final' => 825.30,
            'motivo_ajuste_manual' => 'Arredondamento no depósito',
        ]);

        $resultado->refresh();
        $this->assertSame('825.30', $resultado->valor_liquido_final);
        $this->assertSame('0.30', $resultado->valor_ajuste_manual);
        $this->assertSame('Arredondamento no depósito', (string) $resultado->motivo_ajuste_manual);
        $this->assertSame('repassado', (string) $resultado->status);

        $conta = ContasPagar::findOrFail($resultado->contas_pagar_id);
        $this->assertEqualsWithDelta(825.30, (float) $conta->valor_total, 0.01);
        $this->assertSame(2, (int) $conta->status);
    }
}
