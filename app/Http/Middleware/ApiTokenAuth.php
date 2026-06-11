<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json([
                'message' => 'Не передан API-токен',
            ], 401);
        }

        $token = ApiToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (!$token || !$token->user) {
            return response()->json([
                'message' => 'Недействительный API-токен',
            ], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json([
                'message' => 'Срок действия API-токена истёк',
            ], 401);
        }

        if ($token->user->banned_at) {
            return response()->json([
                'message' => 'Аккаунт заблокирован',
            ], 403);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
