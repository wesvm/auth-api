<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:jwt')->only('update');
    }

    /**
     * Send password reset link
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();

        return transactional(function () use ($user) {
            Token::where('user_id', $user->id)
                ->ofType(TokenType::RESET_PASSWORD)
                ->active()
                ->update(['is_revoked' => true]);

            $plainToken = Str::random(64);
            $hashedToken = hash('sha256', $plainToken);

            Token::create([
                'token' => $hashedToken,
                'token_type' => TokenType::RESET_PASSWORD,
                'expires_at' => now()->addHour(),
                'user_id' => $user->id,
            ]);

            Mail::to($user->email)->send(
                new ResetPasswordMail($user->name, $plainToken)
            );

            return jsonResponse(
                message: 'Password reset link sent to your email'
            );
        });
    }

    /**
     * Reset password with token
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        return transactional(function () use ($request) {
            $hashedToken = hash('sha256', $request->token);

            $token = Token::ofType(TokenType::RESET_PASSWORD)
                ->where('token', $hashedToken)
                ->active()
                ->first();

            if (!$token) {
                return jsonResponse(
                    status: 400,
                    message: 'Invalid or expired reset token'
                );
            }

            if (!$token->isValid()) {
                $token->update(['is_expired' => true]);
                return jsonResponse(
                    status: 400,
                    message: 'Reset token has expired'
                );
            }

            $user = $token->user;
            $user->password = Hash::make($request->password);
            $user->save();
            $token->invalidate();

            // TODO: Invalidate all JWT actives (blacklist)

            return jsonResponse(
                message: 'Password reset successfully. Please login with your new password.'
            );
        });
    }

    /**
     * Update password (authenticated user)
     */
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return jsonResponse(
                status: 400,
                message: 'Current password is incorrect'
            );
        }

        if (Hash::check($request->new_password, $user->password)) {
            return jsonResponse(
                status: 400,
                message: 'New password must be different from current password'
            );
        }

        return transactional(function () use ($user, $request) {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return jsonResponse(
                message: 'Password updated successfully'
            );
        });
    }
}
