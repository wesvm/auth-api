<?php

it('logs in with valid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'login' => 'elon@mail.com',
        'password' => 'admin',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Ok']);
});

it('logs in with invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'login' => 'elon@mail.com',
        'password' => 'some',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Bad credentials.']);
});

it('logs out a user successfully', function () {
    $response = $this->postJson('/api/auth/login', [
        'login' => 'elon@mail.com',
        'password' => 'admin',
    ]);
    $response->assertStatus(200);

    $token = $response->json('data.access_token');
    $logoutResponse = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/auth/logout');
    $logoutResponse->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out.']);
});
