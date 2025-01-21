<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Mail\ResetPasswordMail;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:jwt')->only('update');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users']);

        $user = User::where('email', $request->email)->first();
        $token = Str::uuid();
        Token::where('user_id', $user->id)
            ->where('token_type', TokenType::FORGOT_PASSWORD)
            ->delete();

        return transactional(function () use ($user, $token) {
            Token::create([
               'token' => $token,
               'token_type' => TokenType::FORGOT_PASSWORD,
               'is_expired' => false,
               'is_revoked' => false,
               'expires_at' => now()->addMinutes(5),
               'user_id' => $user->id,
            ]);

            Mail::to($user->email)->send(new ResetPasswordMail($user, $token));
            return jsonResponse(message: 'Token sent to your email.');
        });
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|confirmed|min:3',
        ]);

        $token = Token::where('token', $request->token)
            ->where('token_type', TokenType::FORGOT_PASSWORD)
            ->first();

        if(!$token) return jsonResponse(status: 400, message: 'Invalid token.');
        if($token->is_revoked || $token->is_expired || $token->expires_at < now() ) {
            return jsonResponse(status: 400, message: 'Token has been revoked or expired.');
        };

        return transactional(function () use ($token, $request) {
            $user = User::find($token->user_id);
            $user->password = bcrypt($request->password);
            $user->save();
            $token->delete();
            return jsonResponse(message: 'Your password has been reset.');
        });
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed|min:3',
        ]);

        $user = auth()->user();
        if(!password_verify($request->current_password, $user->password)) {
            return jsonResponse(status: 400, message: 'Invalid current password.');
        }

        return transactional(function () use ($user, $request) {
            $user->password = bcrypt($request->new_password);
            $user->save();
            return jsonResponse(message: 'Your password has been updated.');
        });
    }
}
