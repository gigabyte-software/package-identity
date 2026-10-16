<?php

namespace Gigabyte\Identity\Models;

use Illuminate\Database\Eloquent\Model;

class LoginCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'code',
        'token',
        'expires_at',
        'created_at',
    ];

    public function getTable(): string
    {
        return config('identity.table_prefix', 'identity_').'login_codes';
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function pruneExpired(): void
    {
        static::where('expires_at', '<', now())->delete();
    }
}
