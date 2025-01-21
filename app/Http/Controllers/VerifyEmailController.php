<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Models\Token;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $token)
    {
        $storedToken = Token::where('token', $token)
            ->where('token_type', TokenType::EMAIL_VERIFICATION)
            ->first();

        if(!$storedToken) return jsonResponse(status: 400, message: 'Invalid token.');
        if($storedToken->is_revoked || $storedToken->expires_at < now() || $storedToken->is_expired)
        {
            return jsonResponse(status: 400, message: 'Token has been revoked or expired.');
        }

        $user = User::where('id', $storedToken->user_id)->first();
        return transactional(function () use ($user, $storedToken){
            $user->email_verified_at = now();
            $user->save();
            $storedToken->delete();
            return response()->json(['message' => 'Email verified successfully.']);
        });
    }
}
