<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Mail\EmailVerification;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VerifyEmailController extends Controller
{
    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): JsonResponse
    {
        return transactional(function () use ($token){
            $hashedToken = hash('sha256', $token);
            $verificationToken = Token::ofType(TokenType::EMAIL_VERIFICATION)
                ->where('token', $hashedToken)
                ->active()
                ->first();

            if (!$verificationToken) {
                return jsonResponse(
                    status: 400,
                    message: 'Invalid or expired verification token'
                );
            }

            if (!$verificationToken->isValid()) {
                $verificationToken->update(['is_expired' => true]);
                return jsonResponse(
                    status: 400,
                    message: 'Verification token has expired'
                );
            }

            $user = $verificationToken->user;

            if ($user->email_verified_at) {
                return jsonResponse(
                    status: 400,
                    message: 'Email already verified'
                );
            }

            $user->email_verified_at = now();
            $user->save();
            $verificationToken->invalidate();
            return jsonResponse(
                message: 'Email verified successfully. You can now login.'
            );
        });
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        return transactional(function () use ($request) {
            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return jsonResponse(
                    status: 400,
                    message: 'Email already verified'
                );
            }

            Token::where('user_id', $user->id)
                ->ofType(TokenType::EMAIL_VERIFICATION)
                ->active()
                ->update(['is_revoked' => true]);

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
                message: 'Verification email sent. Please check your inbox.'
            );
        });
    }
}
