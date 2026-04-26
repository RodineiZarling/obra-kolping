<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessBridgeAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization', '');
        $expected = trim((string) config('app.access_bridge_token', env('ACCESS_BRIDGE_TOKEN')));

        if ($expected === '') {
            return response()->json([
                'error' => 'Bridge token not configured.'
            ], 500);
        }

        $token = '';
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        }

        if (! hash_equals($expected, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
