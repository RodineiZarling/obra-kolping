<?php

namespace App\Console\Commands;

use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarMensalidadesCommand extends Command
{
    protected $signature = 'mensalidades:gerar {--date=}';

    protected $description = 'Gera automaticamente as contas a receber das mensalidades devidas para o mês atual.';

    public function handle(): int
    {
        $dateInput = $this->option('date');
        $today = $dateInput ? Carbon::parse($dateInput) : Carbon::today();

        $count = 0;
        Mensalidade::where('status', 1)->chunkById(200, function ($chunk) use (&$count, $today) {
            foreach ($chunk as $mensalidade) {
                $cr = $mensalidade->generateForMonthIfDue($today);
                if ($cr) {
                    $count++;
                    $this->info('Gerado CR ID ' . $cr->id . ' para mensalidade #' . $mensalidade->id);
                }
            }
        });

        $this->info("Total gerado: {$count}");
        return Command::SUCCESS;
    }
}
