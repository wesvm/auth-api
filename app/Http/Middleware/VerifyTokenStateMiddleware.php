<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class VerifyTokenStateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $bearerToken = $request->bearerToken();

            if (!$bearerToken) {
                return jsonResponse(
                    status: 401,
                    message: 'Token not provided'
                );
            }

            $user = auth()->user();

            if (!$user) {
                return jsonResponse(
                    status: 401,
                    message: 'Unauthenticated'
                );
            }

            return $next($request);

        } catch (TokenExpiredException $e) {
            return jsonResponse(
                status: 401,
                message: 'Token has expired'
            );
        } catch (TokenInvalidException $e) {
            return jsonResponse(
                status: 401,
                message: 'Token is invalid'
            );
        } catch (\Exception $e) {
            return jsonResponse(
                status: 401,
                message: 'Authorization token could not be parsed'
            );
        }
    }
}
