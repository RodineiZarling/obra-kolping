<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\MercadoPagoConfig;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $service)
    {
        // Validação simples por segredo custom (MVP). Em produção, considere validar assinatura oficial do MP.
        $secret = optional(MercadoPagoConfig::where('empresa', 1)->first())->webhook_secret;
        if ($secret) {
            $provided = $request->header('X-Webhook-Secret');
            if (!$provided || !hash_equals($secret, $provided)) {
                Log::warning('Webhook Mercado Pago rejeitado por segredo inválido.');
                return response()->json(['message' => 'unauthorized'], 401);
            }
        }

        $payload = $request->all();
        try {
            $service->handleWebhookPayment($payload);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar webhook Mercado Pago', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'error'], 500);
        }

        return response()->json(['message' => 'ok']);
    }
}
