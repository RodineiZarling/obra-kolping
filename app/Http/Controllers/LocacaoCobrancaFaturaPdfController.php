<?php

namespace App\Http\Controllers;

use App\Models\LocacaoCobranca;
use App\Services\GerarFaturaLocacaoPdfService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LocacaoCobrancaFaturaPdfController
{
    public function __invoke(LocacaoCobranca $locacaoCobranca, GerarFaturaLocacaoPdfService $service): Response
    {
        try {
            return $service->executar($locacaoCobranca);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Evita vazar detalhes no download; a UI (Filament) pode mostrar erros quando o usuário aciona a action.
            throw new HttpException(500, 'Não foi possível gerar a fatura em PDF.');
        }
    }
}
