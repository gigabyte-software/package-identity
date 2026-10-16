<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All tables created by the Identity package are prefixed with this value.
    | Set to an empty string to use unprefixed table names.
    |
    */

    'table_prefix' => env('IDENTITY_TABLE_PREFIX', 'identity_'),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class used to look up users by email. It must use the
    | HasApiTokens trait if you need Sanctum token support.
    |
    | If this model implements Gigabyte\Identity\Contracts\Provisionable,
    | users from allowed domains will be auto-provisioned on first login.
    |
    */

    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Login Code Mailable
    |--------------------------------------------------------------------------
    |
    | The mailable class sent when a login code is requested. The constructor
    | must accept: (string $code, string $loginUrl, ?string $userName).
    |
    | Set to null to disable email sending (useful in testing).
    |
    */

    'mailable' => Gigabyte\Identity\Mail\LoginCodeMail::class,

    /*
    |--------------------------------------------------------------------------
    | Magic Link Route Name
    |--------------------------------------------------------------------------
    |
    | The named route used to build the magic-link URL embedded in the login
    | code email. The route must accept a {token} parameter. Set to null to
    | omit the magic link from the email.
    |
    */

    'magic_link_route' => env('IDENTITY_MAGIC_LINK_ROUTE', 'login.magic'),

    /*
    |--------------------------------------------------------------------------
    | Code Expiry
    |--------------------------------------------------------------------------
    */

    'code_expiry_minutes' => (int) env('IDENTITY_CODE_EXPIRY_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Reusable Login Codes
    |--------------------------------------------------------------------------
    |
    | When enabled, short-lived codes issued by sendLoginCode() remain valid
    | until expires_at instead of being deleted on first successful verification.
    | Long-lived codes (e.g. invite links) stay single-use regardless.
    |
    */

    'code_reusable_until_expiry' => (bool) env('IDENTITY_CODE_REUSABLE_UNTIL_EXPIRY', true),

    /*
    |--------------------------------------------------------------------------
    | Token Lifecycle
    |--------------------------------------------------------------------------
    */

    'token_inactivity_days' => (int) env('API_TOKEN_INACTIVITY_DAYS', 21),
    'token_name' => env('API_TOKEN_NAME', 'api-token'),

    /*
    |--------------------------------------------------------------------------
    | Demo Login
    |--------------------------------------------------------------------------
    |
    | When enabled, the demo-login endpoint authenticates a user without
    | requiring a verification code. This must NEVER be enabled in
    | production environments.
    |
    */

    'demo_login_enabled' => (bool) env('IDENTITY_DEMO_LOGIN', false),

    /*
    |--------------------------------------------------------------------------
    | Auto-Provisioning
    |--------------------------------------------------------------------------
    |
    | Users with an email on one of these domains can sign in without being
    | pre-registered. An account is auto-created on first login via the
    | Provisionable contract on the user model.
    |
    | If the user model does not implement Provisionable, this setting is
    | ignored and only existing users can log in.
    |
    */

    'allowed_domains' => array_filter(
        array_map('trim', explode(',', env('IDENTITY_ALLOWED_DOMAINS', '')))
    ),

    'default_role' => env('IDENTITY_DEFAULT_ROLE', 'super_admin'),

    /*
    |--------------------------------------------------------------------------
    | Verification Attempt Limits
    |--------------------------------------------------------------------------
    |
    | After max_attempts failed code verifications the code is deleted and
    | the user must request a new one.
    |
    */

    'max_attempts' => (int) env('IDENTITY_MAX_ATTEMPTS', 5),
    'attempts_ttl_seconds' => (int) env('IDENTITY_ATTEMPTS_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Model Override
    |--------------------------------------------------------------------------
    |
    | When true, the package registers its own PersonalAccessToken model
    | with Sanctum. This changes the token table name to
    | {table_prefix}personal_access_tokens.
    |
    | Set to false if your app manages its own Sanctum token model.
    |
    */

    'override_sanctum_model' => true,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Migration
    |--------------------------------------------------------------------------
    |
    | When true, the package loads its own migration to create the
    | personal_access_tokens table with the configured prefix.
    |
    | Set to false if your app already has a migration for this table
    | (e.g. from a prior Sanctum setup or a rename migration).
    |
    */

    'run_sanctum_migration' => true,

];
