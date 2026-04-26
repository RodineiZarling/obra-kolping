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
use Tests\TestCase;

class LocacaoCobrancaStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_salva_status_da_cobranca_conforme_status_do_contas_receber(): void
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
        $conta = (new EmitirContasReceberDaCobrancaService())->executar($cobranca);

        // Ao emitir, o título financeiro normalmente está em aberto (status 1), e a cobrança deve refletir isso.
        $this->assertDatabaseHas('locacao_cobrancas', [
            'id' => $cobranca->id,
            'contas_receber_id' => $conta->id,
            'status' => 'aberta',
        ]);

        // Marca como recebido no financeiro e valida que a cobrança persiste como paga.
        $conta->status = 2;
        $conta->save();

        $this->assertDatabaseHas('locacao_cobrancas', [
            'id' => $cobranca->id,
            'status' => 'paga',
        ]);

        // Marca como parcial e valida persistência.
        $conta->status = 5;
        $conta->save();

        $this->assertDatabaseHas('locacao_cobrancas', [
            'id' => $cobranca->id,
            'status' => 'parcial',
        ]);
    }
}
