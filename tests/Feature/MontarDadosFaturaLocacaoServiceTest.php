<?php

namespace Tests\Feature;

use App\Models\ContratoLocacao;
use App\Models\Imovel;
use App\Models\LocacaoCobranca;
use App\Models\LocacaoCobrancaItem;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use App\Services\MontarDadosFaturaLocacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MontarDadosFaturaLocacaoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monta_dados_e_renderiza_blade_sem_erros(): void
    {
        config()->set('locacao.fatura.imobiliaria_nome', 'Imobiliária Teste');
        config()->set('locacao.fatura.instrucoes_pagamento', 'Instruções de teste');

        $locador = Locador::create(['nome' => 'Locador 1']);
        $imovel = Imovel::create([
            'locador_id' => $locador->id,
            'titulo' => 'Imóvel 1',
            'endereco' => 'Rua A, 123',
            'cidade' => 'Cidade',
            'estado' => 'UF',
            'cep' => '00000-000',
        ]);
        $unidade = UnidadeImovel::create(['imovel_id' => $imovel->id, 'identificador' => 'Ap 101']);
        $locatario = Locatario::create(['nome' => 'Locatário 1', 'documento' => '000.000.000-00']);

        $contrato = ContratoLocacao::create([
            'locatario_id' => $locatario->id,
            'unidade_imovel_id' => $unidade->id,
            'data_inicio' => '2026-01-01',
            'valor_aluguel' => '1000.00',
            'dia_vencimento' => 10,
            'multa_percentual' => '10.00',
            'juros_percentual_ao_mes' => '1.00',
            'ativo' => true,
        ]);

        $cobranca = LocacaoCobranca::create([
            'contrato_locacao_id' => $contrato->id,
            'unidade_imovel_id' => $unidade->id,
            'locatario_id' => $locatario->id,
            'competencia' => '2026-03',
            'status' => 'aberta',
            'vencimento' => '2026-03-10',
            'valor_subtotal_itens' => '1000.00',
            'valor_multa' => '0.00',
            'valor_juros' => '0.00',
            'valor_total' => '1000.00',
            'observacoes' => 'Obs teste',
        ]);

        LocacaoCobrancaItem::create([
            'locacao_cobranca_id' => $cobranca->id,
            'tipo' => 'aluguel',
            'origem' => 'automatico',
            'descricao' => 'Aluguel base',
            'valor_total' => '1000.00',
        ]);

        $service = new MontarDadosFaturaLocacaoService();
        $data = $service->executar($cobranca->refresh());

        $this->assertSame('2026-03', $data['cobranca']['competencia']);
        $this->assertSame('03/2026', $data['cobranca']['competencia_formatada']);
        $this->assertSame('Imobiliária Teste', $data['imobiliaria']['nome']);
        $this->assertCount(1, $data['itens']);

        $html = view('pdf.locacao.fatura', $data)->render();

        $this->assertStringContainsString('Fatura de Locação', $html);
        $this->assertStringContainsString('Locatário 1', $html);
        $this->assertStringContainsString('Imóvel 1', $html);
        $this->assertStringContainsString('03/2026', $html);
        $this->assertStringContainsString('Instruções de teste', $html);
    }
}
