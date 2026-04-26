<?php

namespace App\Console\Commands;

use App\Services\GeradorCobrancasLocacaoService;
use Illuminate\Console\Command;

class GerarCobrancasLocacaoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locacao:gerar-cobrancas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera automaticamente cobranças de locação pendentes para todos os contratos ativos';

    /**
     * Execute the console command.
     */
    public function handle(GeradorCobrancasLocacaoService $service): int
    {
        $this->info('Iniciando geração de cobranças de locação pendentes...');

        $stats = $service->gerarPendentesTodosContratos();

        $this->table(
            ['Processados', 'Gerados', 'Erros'],
            [[$stats['processados'], $stats['gerados'], $stats['erros']]]
        );

        $this->info('Processo concluído.');

        return Command::SUCCESS;
    }
}
