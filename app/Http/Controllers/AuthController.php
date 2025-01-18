<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
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
        $this->middleware('auth:api')->except('login');
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

        if (!$token = auth()->attempt($credentials)) {
            return jsonResponse(status: 401, message: 'Bad credentials.');
        }

        $user = auth()->user();
        if (!$user->email_verified_at) {
            return jsonResponse(status: 403, message: 'Email not verified.');
        }

        return $this->respondWithToken($token, $user);
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

        return jsonResponse(message: 'Successfully logged out');
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
     * @param string $token
     * @param User $user
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, User $user)
    {
        return jsonResponse(data: [
            'account' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
