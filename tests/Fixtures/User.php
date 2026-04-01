<?php

namespace Gigabyte\Identity\Tests\Fixtures;

use Gigabyte\Identity\Contracts\Provisionable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable2;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable2 implements Provisionable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function provisionFromEmail(string $email, string $name): ?Authenticatable
    {
        return static::create([
            'name' => $name,
            'email' => $email,
            'role' => 'user',
        ]);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
