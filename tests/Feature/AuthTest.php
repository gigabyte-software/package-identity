<?php

use Gigabyte\Identity\Models\LoginCode;
use Gigabyte\Identity\Tests\Fixtures\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

test('login endpoint sends code for existing user', function () {
    Mail::fake();
    config(['identity.mailable' => Gigabyte\Identity\Mail\LoginCodeMail::class]);

    $user = User::factory()->create(['email' => 'alice@example.com']);

    $response = $this->postJson('/api/identity/auth/login', ['email' => 'alice@example.com']);

    $response->assertOk();
    $this->assertDatabaseHas('identity_login_codes', ['email' => 'alice@example.com']);
    Mail::assertSent(Gigabyte\Identity\Mail\LoginCodeMail::class, fn ($mail) => $mail->hasTo('alice@example.com'));
});

test('login returns 200 for unknown email without revealing existence', function () {
    Mail::fake();

    $response = $this->postJson('/api/identity/auth/login', ['email' => 'nobody@unknown.com']);

    $response->assertOk();
    $this->assertDatabaseCount('identity_login_codes', 0);
    Mail::assertNothingSent();
});

test('verify returns token for valid code', function () {
    $user = User::factory()->create(['email' => 'alice@example.com']);

    LoginCode::create([
        'email' => 'alice@example.com',
        'code' => '123456',
        'token' => 'test-token',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/identity/auth/verify', [
        'email' => 'alice@example.com',
        'code' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.email', 'alice@example.com');
    $this->assertDatabaseMissing('identity_login_codes', ['email' => 'alice@example.com']);
});

test('verify rejects invalid code', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    LoginCode::create([
        'email' => 'alice@example.com',
        'code' => '123456',
        'token' => 'test-token',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/identity/auth/verify', [
        'email' => 'alice@example.com',
        'code' => '999999',
    ]);

    $response->assertUnprocessable();
});

test('verify rejects expired code', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    LoginCode::create([
        'email' => 'alice@example.com',
        'code' => '123456',
        'token' => 'test-token',
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson('/api/identity/auth/verify', [
        'email' => 'alice@example.com',
        'code' => '123456',
    ]);

    $response->assertUnprocessable();
});

test('verify enforces max attempts', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    LoginCode::create([
        'email' => 'alice@example.com',
        'code' => '123456',
        'token' => 'test-token',
        'expires_at' => now()->addMinutes(10),
    ]);

    Cache::put('login_code_attempts:alice@example.com', 5, 600);

    $response = $this->postJson('/api/identity/auth/verify', [
        'email' => 'alice@example.com',
        'code' => '123456',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Too many failed attempts. Please request a new code.');
});

test('check returns user when authenticated', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/identity/auth/check');

    $response->assertOk()
        ->assertJsonStructure(['user'])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

test('check returns 401 when unauthenticated', function () {
    $response = $this->getJson('/api/identity/auth/check');

    $response->assertUnauthorized();
});

test('demo-login returns token when enabled', function () {
    config(['identity.demo_login_enabled' => true]);

    $user = User::factory()->create(['email' => 'demo@example.com']);

    $response = $this->postJson('/api/identity/auth/demo-login', ['email' => 'demo@example.com']);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.email', 'demo@example.com');
});

test('demo-login returns 403 when disabled', function () {
    config(['identity.demo_login_enabled' => false]);

    User::factory()->create(['email' => 'demo@example.com']);

    $response = $this->postJson('/api/identity/auth/demo-login', ['email' => 'demo@example.com']);

    $response->assertForbidden();
});

test('auto-provisions user from allowed domain', function () {
    Mail::fake();
    config([
        'identity.allowed_domains' => ['allowed.com'],
        'identity.mailable' => Gigabyte\Identity\Mail\LoginCodeMail::class,
    ]);

    $response = $this->postJson('/api/identity/auth/login', ['email' => 'jane.doe@allowed.com']);

    $response->assertOk();
    $this->assertDatabaseHas('users', [
        'email' => 'jane.doe@allowed.com',
        'name' => 'Jane Doe',
    ]);
    $this->assertDatabaseHas('identity_login_codes', ['email' => 'jane.doe@allowed.com']);
});

test('does not provision user from disallowed domain', function () {
    Mail::fake();
    config(['identity.allowed_domains' => ['allowed.com']]);

    $response = $this->postJson('/api/identity/auth/login', ['email' => 'jane@disallowed.com']);

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['email' => 'jane@disallowed.com']);
    $this->assertDatabaseCount('identity_login_codes', 0);
});

test('bound DomainResolver allows provisioning for domains not in config', function () {
    Mail::fake();
    config([
        'identity.allowed_domains' => [],
        'identity.mailable' => Gigabyte\Identity\Mail\LoginCodeMail::class,
    ]);

    $resolver = new class implements Gigabyte\Identity\Contracts\DomainResolver {
        public function isDomainAllowed(string $domain): bool
        {
            return $domain === 'dynamic.com';
        }
    };

    app()->instance(Gigabyte\Identity\Contracts\DomainResolver::class, $resolver);

    $response = $this->postJson('/api/identity/auth/login', ['email' => 'bob.smith@dynamic.com']);

    $response->assertOk();
    $this->assertDatabaseHas('users', [
        'email' => 'bob.smith@dynamic.com',
        'name' => 'Bob Smith',
    ]);
    $this->assertDatabaseHas('identity_login_codes', ['email' => 'bob.smith@dynamic.com']);
});
