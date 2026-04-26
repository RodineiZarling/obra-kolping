<?php

namespace App\Http\Controllers;

use App\Models\AccessDevice;
use App\Models\AccessEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessControlController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toISOString(),
        ]);
    }

    public function validar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device.ip' => ['required','ip'],
            'device.port' => ['required','integer'],
            'event.type' => ['required','string'],
            'event.timestamp' => ['nullable','date'],
            'event.direction' => ['nullable','in:in,out'],
            'event.credential.type' => ['nullable','string'],
            'event.credential.value' => ['nullable','string'],
            'meta.request_id' => ['nullable','string'],
        ]);

        $ip = $data['device']['ip'];
        $port = (int) $data['device']['port'];

        $device = AccessDevice::where('ip', $ip)->where('port', $port)->first();
        if (! $device || ! $device->is_active) {
            return response()->json([
                'allow' => false,
                'reason' => $device ? 'Device inativo' : 'Device não encontrado',
            ]);
        }

        $credential = $data['event']['credential']['value'] ?? null;
        $occurredAt = $data['event']['timestamp'] ?? now();

        // TODO: localizar usuário por credencial e aplicar regras de negócio reais
        $allow = true;
        $reason = $allow ? 'Permitido (stub)' : 'Negado (stub)';

        AccessEvent::create([
            'access_device_id' => $device->id,
            'user_id' => null,
            'credential' => $credential,
            'event_type' => $data['event']['type'],
            'result' => $allow ? 'granted' : 'denied',
            'reason' => $reason,
            'occurred_at' => $occurredAt,
            'raw_payload' => $request->all(),
        ]);

        return response()->json([
            'allow' => $allow,
            'reason' => $reason,
            'user_id' => null,
            'action' => ['open' => $allow, 'duration_ms' => 800],
            'policy' => ['antipassback' => false],
        ]);
    }

    public function evento(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device.ip' => ['required','ip'],
            'device.port' => ['required','integer'],
            'event.type' => ['required','string'],
            'event.result' => ['nullable','string'],
            'event.timestamp' => ['nullable','date'],
            'user_ref.credential' => ['nullable','string'],
        ]);

        $ip = $data['device']['ip'];
        $port = (int) $data['device']['port'];
        $device = AccessDevice::where('ip', $ip)->where('port', $port)->first();
        if (! $device) {
            return response()->json(['error' => 'Device não encontrado'], 404);
        }

        AccessEvent::create([
            'access_device_id' => $device->id,
            'credential' => $data['user_ref']['credential'] ?? null,
            'event_type' => $data['event']['type'],
            'result' => $data['event']['result'] ?? null,
            'reason' => null,
            'occurred_at' => $data['event']['timestamp'] ?? now(),
            'raw_payload' => $request->all(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
