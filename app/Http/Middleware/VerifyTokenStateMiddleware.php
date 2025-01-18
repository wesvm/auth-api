<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTokenStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = Token::where('token', $request->bearerToken())
            ->where('user_id', auth()->id())->first();

        if (!$token) {
            return jsonResponse(status: 401, message: 'Unauthenticated.');
        }

        if ($token->is_revoked || $token->is_expired) {
            return jsonResponse(status: 401, message: 'Token has been revoked or expired.');
        }

        if ($token->expires_at < now()) {
            $token->update([
                'is_expired' => true,
            ]);
            auth()->logout();
            return jsonResponse(status: 401, message: 'Token has expired.');
        }

        return $next($request);
    }
}
