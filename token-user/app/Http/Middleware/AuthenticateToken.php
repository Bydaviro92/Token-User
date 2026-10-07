<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $plainTextToken = $request->bearerToken();

            if (! $plainTextToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere un token Bearer válido.',
                    'data' => null,
                ], 401);
            }

            $token = Token::with('user')
                ->where('token', hash('sha256', $plainTextToken))
                ->first();

            if (! $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'El token no es válido.',
                    'data' => null,
                ], 401);
            }

            $request->attributes->set('api_user', $token->user);

            return $next($request);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible validar el token.',
                'data' => null,
            ], 500);
        }
    }
}
