<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\CalcularMultaRescisaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CalcularMultaRescisaoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_multa_rescisao_com_meses_restantes_por_mes_seguinte(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        // Jan/2026 a Dez/2026 => 12 meses.
        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-15',
            'data_fim' => '2026-12-14',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        // Rescisão em Jun/2026 => meses restantes Jul-Dez => 6.
        // multa = (3 * 1000 / 12) * 6 = 1500
        $service = new CalcularMultaRescisaoService();
        $memoria = $service->executar($contrato, '2026-06-10');

        $this->assertSame(12, $memoria['total_meses_contrato']);
        $this->assertSame(6, $memoria['meses_restantes']);
        $this->assertSame('1000.00', $memoria['valor_aluguel_vigente']);
        $this->assertSame('1500.00', $memoria['multa_rescisao']);
        $this->assertSame(150000, $memoria['multa_rescisao_centavos']);
    }

    public function test_rescisao_apos_fim_do_contrato_retorna_multa_zero(): void
    {
        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create(['locador_id' => $locador->id, 'titulo' => 'Imóvel 1']);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $service = new CalcularMultaRescisaoService();
        $memoria = $service->executar($contrato, '2027-01-05');

        $this->assertSame(0, $memoria['meses_restantes']);
        $this->assertSame('0.00', $memoria['multa_rescisao']);
    }

    public function test_rejeita_rescisao_antes_do_inicio(): void
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
            'data_fim' => '2026-12-31',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $service = new CalcularMultaRescisaoService();
        $service->executar($contrato, '2025-12-31');
    }
}
