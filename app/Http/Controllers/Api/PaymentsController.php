<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContasReceberParcela;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function __construct(private readonly MercadoPagoService $mp) {}

    public function createPix(Request $request, ContasReceberParcela $parcela)
    {
        $this->authorize('view', $parcela->contasReceber); // ajuste de policy conforme necessário

        $data = $this->mp->createPixPayment($parcela);
        return response()->json($data);
    }

    public function createBoleto(Request $request, ContasReceberParcela $parcela)
    {
        $this->authorize('view', $parcela->contasReceber);

        $data = $this->mp->createBoletoPayment($parcela);
        return response()->json($data);
    }
}
