<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:jwt')->except('login');
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return JsonResponse
     */
    public function login(AuthRequest $request)
    {
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email' : 'username';

        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        $token = auth()->attempt($credentials);
        if (!$token) {
            return jsonResponse(status: 401, message: 'Bad credentials.');
        }

        $user = auth()->user();
        if (!$user->email_verified_at) {
            return jsonResponse(status: 403, message: 'Email not verified.');
        }

        $storedToken = Token::create([
            'token' => $token,
            'token_type' => TokenType::BEARER,
            'expires_at' => now()->addMinutes(config('jwt.ttl')),
            'user_id' => $user->id,
        ]);

        return $this->respondWithToken($storedToken, $user);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me()
    {
        return jsonResponse(data: new UserResource(auth()->user()));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        Token::where('user_id', auth()->id())->delete();
        return jsonResponse(message: 'Successfully logged out.');
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh(), auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param Token $token
     * @param User $user
     * @return JsonResponse
     */
    protected function respondWithToken(Token $token, User $user)
    {
        return jsonResponse(data: [
            'account' => new UserResource($user),
            'access_token' => $token->token,
            'token_type' => $token->token_type,
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
