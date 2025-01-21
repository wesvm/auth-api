<?php

use App\Enums\TokenType;
use App\Mail\EmailVerification;
use App\Models\Token;
use App\Models\User;

it('creates a user and sends email verification', function () {
    // Fake the email sending
    Mail::fake();

    // Prepare the request data
    $data = [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@mail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // Act: Send a POST request to the store endpoint
    $response = $this->postJson('api/users', $data);

    // Assert the response
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Check your email for verification link.',
        ]);

    // Assert the user was created in the database
    expect(User::where('email', $data['email'])->exists())->toBeTrue();

    // Assert the user's password is hashed
    $user = User::where('email', $data['email'])->first();
    expect(Hash::check($data['password'], $user->password))->toBeTrue();

    // Assert the token was created for email verification
    $token = Token::where('user_id', $user->id)->first();
    expect($token)->not->toBeNull()
        ->and($token->token_type)->toBe(TokenType::EMAIL_VERIFICATION->value)
        ->and($token->is_expired)->toBeFalse()
        ->and($token->is_revoked)->toBeFalse();

    // Assert the email was sent
    Mail::assertSent(EmailVerification::class, function ($mail) use ($user, $token) {
        return $mail->hasTo($user->email) &&
            $mail->token === $token->token &&
            $mail->name === $user->name;
    });

    User::where('email', $data['email'])->delete();
});
