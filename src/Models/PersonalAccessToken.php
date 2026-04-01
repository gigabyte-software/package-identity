<?php

namespace Gigabyte\Identity\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function getTable(): string
    {
        return config('identity.table_prefix', 'identity_').'personal_access_tokens';
    }
}
