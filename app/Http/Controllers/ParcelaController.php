<?php

namespace App\Http\Controllers;

use App\Models\ContasPagarParcela;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParcelaController extends Controller
{
    /**
     * Mark a parcela as paid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pagarParcela(Request $request)
    {
        // Validate the request
        $request->validate([
            'parcelaId' => 'required|exists:contas_pagar_parcelas,id',
        ]);

        try {
            // Find the parcela
            $parcela = ContasPagarParcela::findOrFail($request->parcelaId);

            // Check if the parcela belongs to the user's company
            if ($parcela->empresa != Auth::user()->empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para acessar esta parcela.',
                ], 403);
            }

            // Check if the parcela can be paid (status is "Em aberto" or "Vencido")
            if (!in_array($parcela->status, [1, 4])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta parcela não pode ser paga (status inválido).',
                ], 400);
            }

            // Set the status to "Pago" (2)
            $parcela->status = 2;
            $parcela->save(); // This will trigger the model's saving event to set the payment date

            return response()->json([
                'success' => true,
                'message' => 'Parcela marcada como paga com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro ao marcar a parcela como paga: ' . $e->getMessage(),
            ], 500);
        }
    }
}
