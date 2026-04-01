<?php

namespace Gigabyte\Identity\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$12$hash',
            'role' => 'user',
            'remember_token' => Str::random(10),
        ];
    }
}
