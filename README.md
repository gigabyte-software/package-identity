# Laravel Identity

Passwordless code-based authentication for Laravel. Provides a complete API layer for email-code and magic-link login flows backed by Sanctum API tokens with sliding inactivity expiry.

No passwords are stored or transmitted. Users authenticate by entering a 6-digit code sent to their email address, or by clicking a magic link.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Laravel Sanctum 4.x

## Installation

```bash
composer require gigabyte/laravel-identity
```

The package auto-discovers its service provider. Publish the configuration file:

```bash
php artisan vendor:publish --tag=identity-config
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

| Variable | Purpose | Default |
|----------|---------|---------|
| `IDENTITY_TABLE_PREFIX` | Database table prefix | `identity_` |
| `IDENTITY_MAGIC_LINK_ROUTE` | Named route for magic link URL | `login.magic` |
| `IDENTITY_CODE_EXPIRY_MINUTES` | Code expiry in minutes | `10` |
| `API_TOKEN_INACTIVITY_DAYS` | Sliding token expiry in days | `30` |
| `API_TOKEN_NAME` | Sanctum token name | `api-token` |
| `IDENTITY_DEMO_LOGIN` | Enable demo login (dev only) | `false` |
| `IDENTITY_ALLOWED_DOMAINS` | Comma-separated auto-provision domains | _(none)_ |
| `IDENTITY_MAX_ATTEMPTS` | Max failed verification attempts | `5` |
| `IDENTITY_ATTEMPTS_TTL` | Attempt counter TTL in seconds | `600` |

### User Model

Set `identity.user_model` in the published config to point to your User model. The model must use the `HasApiTokens` trait.

### Auto-Provisioning

To enable auto-provisioning of users from allowed email domains, your User model must implement `Gigabyte\Identity\Contracts\Provisionable`:

```php
use Gigabyte\Identity\Contracts\Provisionable;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends Authenticatable implements Provisionable
{
    public static function provisionFromEmail(string $email, string $name): ?Authenticatable
    {
        return static::create([
            'name'  => $name,
            'email' => $email,
            'role'  => 'user',
        ]);
    }
}
```

Then set `IDENTITY_ALLOWED_DOMAINS` to a comma-separated list of domains.

### DomainResolver (Database-Driven Domains)

For applications that need to resolve allowed domains from a database (or any dynamic source), bind an implementation of `Gigabyte\Identity\Contracts\DomainResolver` in your service provider:

```php
use Gigabyte\Identity\Contracts\DomainResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DomainResolver::class, MyDomainResolver::class);
    }
}
```

Your resolver must implement a single method:

```php
use Gigabyte\Identity\Contracts\DomainResolver;

class MyDomainResolver implements DomainResolver
{
    public function isDomainAllowed(string $domain): bool
    {
        return DB::table('allowed_domains')->where('domain', $domain)->exists();
    }
}
```

The package checks `config('identity.allowed_domains')` first. If the domain is not found there, it falls back to the bound `DomainResolver` (if any). If neither allows the domain, provisioning is denied.

This is fully backwards-compatible — if no `DomainResolver` is bound, behaviour is identical to previous versions.

### Sanctum Token Model

By default, the package overrides Sanctum's `PersonalAccessToken` model to use prefixed table names. To disable this and manage Sanctum yourself, set `identity.override_sanctum_model` to `false` in the config.

### Custom Mailable

The package ships a plain default email. To use your own branded email, create a mailable whose constructor accepts `(string $code, string $loginUrl, ?string $userName)` and set `identity.mailable` in the config.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/identity/auth/login` | Request 6-digit login code (sent via email) |
| POST | `/api/identity/auth/verify` | Verify code and exchange for API token |
| POST | `/api/identity/auth/demo-login` | Demo login (dev only, requires config) |
| GET | `/api/identity/auth/check` | Validate token and extend sliding expiry |

### Authentication Flow

1. Client sends `POST /api/identity/auth/login` with `{ "email": "..." }`
2. If the user exists (or is auto-provisioned), a 6-digit code is emailed with a magic link
3. Client sends `POST /api/identity/auth/verify` with `{ "email": "...", "code": "..." }`
4. Server returns `{ "token": "...", "user": { ... } }`
5. Client includes the token as `Authorization: Bearer <token>` on subsequent requests

### Rate Limiting

| Endpoint | Limit |
|----------|-------|
| `/auth/login` | 3 per minute per IP |
| `/auth/verify` | 5 per minute per IP |
| Code attempts | 5 failed attempts per email |

## Web Login Integration

This package provides the API layer and `IdentityService`. For web (session-based) login, create your own controller that uses the service:

```php
use Gigabyte\Identity\Services\IdentityService;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        private readonly IdentityService $identity,
    ) {}

    public function store(Request $request)
    {
        $user = $this->identity->resolveUser($request->email);

        if ($user) {
            $this->identity->sendLoginCode($user);
        }

        return redirect()->route('verify-code');
    }

    public function verify(Request $request)
    {
        $user = $this->identity->verifyCode($request->email, $request->code);

        if (! $user) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
```

## Middleware Exports

Use `EnsureTokenIsActive` on any route group to enforce sliding token expiry:

```php
use Gigabyte\Identity\Http\Middleware\EnsureTokenIsActive;

Route::middleware(['auth:sanctum', EnsureTokenIsActive::class])->group(function () {
    // Your routes here
});
```

## Testing

```bash
composer install
vendor/bin/pest
```

## License

MIT
