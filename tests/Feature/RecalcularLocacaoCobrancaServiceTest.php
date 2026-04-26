<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\LocacaoCobrancaItem;
use App\Models\UnidadeImovel;
use App\Services\GerarCobrancaMensalService;
use App\Services\RecalcularLocacaoCobrancaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalcularLocacaoCobrancaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalcula_subtotal_multa_juros_e_total_sem_float(): void
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

        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'extras',
            'origem' => 'manual',
            'descricao' => 'Extra',
            'quantidade' => null,
            'valor_unitario' => '50.00',
            'valor_total' => '50.00',
            'metadados' => null,
        ]);

        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'multa',
            'origem' => 'sistema',
            'descricao' => 'Multa',
            'quantidade' => null,
            'valor_unitario' => '100.00',
            'valor_total' => '100.00',
            'metadados' => null,
        ]);

        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'juros',
            'origem' => 'sistema',
            'descricao' => 'Juros',
            'quantidade' => null,
            'valor_unitario' => '10.00',
            'valor_total' => '10.00',
            'metadados' => null,
        ]);

        $service = new RecalcularLocacaoCobrancaService();
        $cobrancaRecalculada = $service->executar($cobranca);

        $this->assertSame('1050.00', (string) $cobrancaRecalculada->valor_subtotal_itens);
        $this->assertSame('100.00', (string) $cobrancaRecalculada->valor_multa);
        $this->assertSame('10.00', (string) $cobrancaRecalculada->valor_juros);
        $this->assertSame('1160.00', (string) $cobrancaRecalculada->valor_total);

        // Idempotência: reexecutar não altera resultado.
        $cobrancaRecalculada2 = $service->executar($cobrancaRecalculada);
        $this->assertSame((string) $cobrancaRecalculada->valor_total, (string) $cobrancaRecalculada2->valor_total);
        $this->assertSame((string) $cobrancaRecalculada->valor_subtotal_itens, (string) $cobrancaRecalculada2->valor_subtotal_itens);
        $this->assertSame((string) $cobrancaRecalculada->valor_multa, (string) $cobrancaRecalculada2->valor_multa);
        $this->assertSame((string) $cobrancaRecalculada->valor_juros, (string) $cobrancaRecalculada2->valor_juros);
    }
}
