<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\TwoFactorRequest;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerification;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:jwt')->except(['register', 'login', 'authenticate2fa']);
    }

    /**
     * Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return transactional(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
            ]);

            $plainToken = Str::random(64);
            $hashedToken = hash('sha256', $plainToken);

            Token::create([
                'token' => $hashedToken,
                'token_type' => TokenType::EMAIL_VERIFICATION,
                'expires_at' => now()->addHours(24),
                'user_id' => $user->id,
            ]);

            Mail::to($user->email)->send(
                new EmailVerification($user->name, $plainToken)
            );

            return jsonResponse(
                status: 201,
                message: 'User registered successfully. Please check your email to verify your account.'
            );
        });
    }

    /**
     * Login with credentials
     */
    public function login(AuthRequest $request): JsonResponse
    {
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        $token = auth()->attempt($credentials);
        if (!$token) {
            return jsonResponse(
                status: 401,
                message: 'Invalid credentials'
            );
        }

        $user = auth()->user();
        if (!$user->email_verified_at) {
            auth()->logout();
            return jsonResponse(
                status: 403,
                message: 'Email not verified'
            );
        }

        if ($user->two_factor_enabled && $user->two_factor_secret) {
            auth()->logout();

            Token::where('user_id', $user->id)
                ->ofType(TokenType::MFA)
                ->active()
                ->update(['is_revoked' => true]);

            $ticket = Token::create([
                'token' => Str::uuid()->toString(),
                'token_type' => TokenType::MFA,
                'expires_at' => now()->addMinutes(5),
                'user_id' => $user->id,
            ]);

            return jsonResponse(
                message: 'Two-factor authentication required',
                data: [
                    'ticket' => $ticket->token,
                    'expires_in' => 300,
                ]
            );
        }

        return $this->respondWithToken($token, $user);
    }

    /**
     * Authenticate with 2FA code
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function authenticate2fa(TwoFactorRequest $request): JsonResponse
    {
        $ticket = Token::ofType(TokenType::MFA)
            ->where('token', $request->ticket)
            ->active()
            ->first();

        if (!$ticket) {
            return jsonResponse(
                status: 401,
                message: 'Invalid or expired ticket'
            );
        }

        if (!$ticket->isValid()) {
            return jsonResponse(
                status: 401,
                message: 'Ticket has expired'
            );
        }

        $user = $ticket->user;
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return jsonResponse(
                status: 400,
                message: 'Invalid 2FA code'
            );
        }

        $ticket->invalidate();
        $token = auth()->login($user);
        return $this->respondWithToken($token, $user);
    }

    /**
     * Get authenticated user
     */
    public function me(): JsonResponse
    {
        return jsonResponse(
            data: new UserResource(auth()->user())
        );
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        $user = auth()->user();
        Token::where('user_id', $user->id)
            ->ofType(TokenType::MFA)
            ->active()
            ->update(['is_revoked' => true]);

        auth()->logout();

        return jsonResponse(
            message: 'Successfully logged out'
        );
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        $newToken = auth()->refresh();
        $user = auth()->user();

        return $this->respondWithToken($newToken, $user);
    }

    /**
     * Format token response
     */
    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return jsonResponse(
            data: [
                'account' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        );
    }
}
