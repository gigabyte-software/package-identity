<?php

use Gigabyte\Identity\Tests\Fixtures\User;

test('token is valid when last used within inactivity window', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'last_used_at' => now()->subDays(20),
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id);
});

test('token expires after 21 days of inactivity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'last_used_at' => now()->subDays(22),
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'Token expired due to inactivity.');

    $this->assertDatabaseMissing('identity_personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

test('token inactivity window is configurable', function () {
    config(['identity.token_inactivity_days' => 7]);

    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'last_used_at' => now()->subDays(8),
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertUnauthorized();
});

test('activity extends the session by updating last_used_at', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'last_used_at' => now()->subDays(15),
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertOk();

    $token->accessToken->refresh();
    expect($token->accessToken->last_used_at->isToday())->toBeTrue();
});

test('token with null last_used_at is not expired by inactivity check', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'last_used_at' => null,
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertOk();
});

test('sanctum absolute expiration does not override sliding window', function () {
    config(['sanctum.expiration' => 60]);

    $user = User::factory()->create();
    $token = $user->createToken('api-token');

    $token->accessToken->forceFill([
        'created_at' => now()->subHours(2),
        'last_used_at' => now()->subMinutes(5),
    ])->save();

    $response = $this->withToken($token->plainTextToken)
        ->getJson('/api/identity/auth/check');

    $response->assertOk();
});

test('default token inactivity is 21 days', function () {
    expect(config('identity.token_inactivity_days'))->toBe(21);
});
