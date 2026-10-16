<?php

namespace Gigabyte\Identity\Services;

use Gigabyte\Identity\Contracts\DomainResolver;
use Gigabyte\Identity\Contracts\Provisionable;
use Gigabyte\Identity\Models\LoginCode;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class IdentityService
{
    public function resolveUser(string $email): ?Authenticatable
    {
        $email = Str::lower($email);
        $userModel = config('identity.user_model');

        return $userModel::where('email', $email)->first()
            ?? $this->provisionIfAllowedDomain($email);
    }

    public function sendLoginCode(Authenticatable $user): LoginCode
    {
        LoginCode::where('email', $user->email)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        $loginCode = LoginCode::create([
            'email' => $user->email,
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(config('identity.code_expiry_minutes', 10)),
        ]);

        $magicRoute = config('identity.magic_link_route');
        $loginUrl = $magicRoute
            ? route($magicRoute, ['token' => $token])
            : null;

        $mailableClass = config('identity.mailable');

        if ($mailableClass) {
            Mail::to($user->email)->send(new $mailableClass(
                code: $code,
                loginUrl: $loginUrl ?? '',
                userName: $user->name,
            ));
        }

        return $loginCode;
    }

    /**
     * Validate a 6-digit code. Returns the authenticated user on success, null on failure.
     * Callers are responsible for attempt-tracking if needed.
     */
    public function verifyCode(string $email, string $code): ?Authenticatable
    {
        $email = Str::lower($email);
        $loginCode = LoginCode::where('email', $email)
            ->where('code', $code)
            ->first();

        if (! $loginCode || $loginCode->isExpired()) {
            LoginCode::pruneExpired();

            return null;
        }

        return $this->consumeLoginCode($loginCode);
    }

    public function verifyMagicToken(string $token): ?Authenticatable
    {
        $loginCode = LoginCode::where('token', $token)->first();

        if (! $loginCode || $loginCode->isExpired()) {
            LoginCode::pruneExpired();

            return null;
        }

        return $this->consumeLoginCode($loginCode, false);
    }

    /**
     * A same-app path carried on a magic link as `?redirect=`.
     *
     * Only a root-relative path is accepted. Absolute URLs and
     * protocol-relative paths are refused so the parameter cannot
     * send the browser to another site. The path is decoded a few
     * times first, so an encoded `//` cannot slip through.
     */
    public function intendedWebPath(mixed $redirect): ?string
    {
        if (! is_string($redirect)) {
            return null;
        }

        $redirect = trim($redirect);
        if ($redirect === '') {
            return null;
        }

        $candidate = $redirect;
        for ($pass = 0; $pass < 3; $pass++) {
            if (! $this->isSameAppPath($candidate)) {
                return null;
            }

            $decoded = rawurldecode($candidate);
            if ($decoded === $candidate) {
                return $redirect;
            }

            $candidate = $decoded;
        }

        return $this->isSameAppPath($candidate) ? $redirect : null;
    }

    /**
     * The path must stay on this app. A URL inside the query or fragment
     * is data, not the navigation target.
     */
    private function isSameAppPath(string $redirect): bool
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $redirect) === 1) {
            return false;
        }

        $path = $redirect;
        $query = strpos($redirect, '?');
        $fragment = strpos($redirect, '#');
        $cut = $query === false ? $fragment : ($fragment === false ? $query : min($query, $fragment));
        if ($cut !== false) {
            $path = substr($redirect, 0, $cut);
        }

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return false;
        }

        return ! str_contains($path, '\\') && ! str_contains($path, '://');
    }

    /**
     * Resolve a user for demo login (no code required).
     * Returns null when demo login is disabled via config.
     */
    public function demoResolveUser(string $email): ?Authenticatable
    {
        if (! config('identity.demo_login_enabled', false)) {
            return null;
        }

        return $this->resolveUser($email);
    }

    // ------------------------------------------------------------------
    // Attempt tracking helpers (used by the API layer)
    // ------------------------------------------------------------------

    public function hasExceededAttempts(string $email): bool
    {
        $email = Str::lower($email);
        $max = config('identity.max_attempts', 5);

        return Cache::get($this->attemptsKey($email), 0) >= $max;
    }

    public function recordFailedAttempt(string $email): void
    {
        $email = Str::lower($email);
        $key = $this->attemptsKey($email);
        $ttl = config('identity.attempts_ttl_seconds', 600);

        Cache::put($key, Cache::get($key, 0) + 1, $ttl);
    }

    public function clearAttempts(string $email): void
    {
        Cache::forget($this->attemptsKey(Str::lower($email)));
    }

    public function deleteCodesForEmail(string $email): void
    {
        LoginCode::where('email', Str::lower($email))->delete();
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private function provisionIfAllowedDomain(string $email): ?Authenticatable
    {
        $userModel = config('identity.user_model');

        if (! is_subclass_of($userModel, Provisionable::class)) {
            return null;
        }

        $domain = Str::lower(Str::after($email, '@'));
        $allowedDomains = config('identity.allowed_domains', []);

        $allowed = ! empty($allowedDomains) && in_array($domain, $allowedDomains, true);

        if (! $allowed) {
            $resolver = app()->bound(DomainResolver::class)
                ? app(DomainResolver::class)
                : null;
            $allowed = $resolver?->isDomainAllowed($domain) ?? false;
        }

        if (! $allowed) {
            return null;
        }

        $localPart = Str::before($email, '@');
        $nameParts = array_map(
            fn ($part) => Str::ucfirst(Str::lower($part)),
            preg_split('/[\.\-_]/', $localPart),
        );
        $name = implode(' ', $nameParts);

        return $userModel::provisionFromEmail(Str::lower($email), $name);
    }

    private function consumeLoginCode(LoginCode $loginCode, bool $deleteSiblings = true): Authenticatable
    {
        $userModel = config('identity.user_model');
        $user = $userModel::where('email', $loginCode->email)->firstOrFail();

        if (! $this->shouldRetainLoginCodeUntilExpiry($loginCode)) {
            if ($deleteSiblings) {
                LoginCode::where('email', $loginCode->email)->delete();
            } else {
                $loginCode->delete();
            }
        }

        LoginCode::pruneExpired();

        return $user;
    }

    private function shouldRetainLoginCodeUntilExpiry(LoginCode $loginCode): bool
    {
        if (! config('identity.code_reusable_until_expiry', true)) {
            return false;
        }

        return $this->isShortLivedLoginCode($loginCode);
    }

    /**
     * Short-lived codes come from sendLoginCode(); long-lived rows (e.g. invites)
     * are treated as single-use even when reuse-until-expiry is enabled.
     */
    private function isShortLivedLoginCode(LoginCode $loginCode): bool
    {
        $expiryMinutes = (int) config('identity.code_expiry_minutes', 10);
        $fudgeMinutes = 2;

        if (! $loginCode->created_at) {
            return false;
        }

        $lifetimeMinutes = $loginCode->created_at->diffInMinutes($loginCode->expires_at);

        return $lifetimeMinutes <= ($expiryMinutes + $fudgeMinutes);
    }

    private function attemptsKey(string $email): string
    {
        return 'login_code_attempts:'.$email;
    }
}
