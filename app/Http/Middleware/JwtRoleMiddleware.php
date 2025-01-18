<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();
        if (!$user || !isset($user->role) || !in_array($user->role, $roles)) {
            return jsonResponse(status: 403, message: 'Insufficient permissions.');
        }

        return $next($request);
    }
}
