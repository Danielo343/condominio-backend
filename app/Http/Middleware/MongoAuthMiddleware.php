<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PersonalAccessToken;

class MongoAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tokenHeader = $request->bearerToken();

        if (!$tokenHeader || !str_contains($tokenHeader, '|')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado. Token no proporcionado.'
            ], 401);
        }

        [$tokenId, $tokenSecret] = explode('|', $tokenHeader, 2);

        // Búsqueda híbrida del token en MongoDB Atlas
        $tokenRecord = PersonalAccessToken::where('token_id', $tokenId)->first();

        if (!$tokenRecord) {
            $tokenRecord = PersonalAccessToken::where('_id', $tokenId)->first();
        }

        if (!$tokenRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token de sesión no válido o expirado.'
            ], 401);
        }

        if ($tokenRecord->token !== hash('sha256', $tokenSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token de sesión inválido.'
            ], 401);
        }

        $user = User::find($tokenRecord->tokenable_id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado.'
            ], 401);
        }

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}