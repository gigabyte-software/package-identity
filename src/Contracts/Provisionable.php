<?php

namespace Gigabyte\Identity\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Implement this interface on your User model to enable auto-provisioning
 * of new users when they attempt to log in from an allowed email domain.
 *
 * If the user model does not implement this interface, auto-provisioning
 * is disabled and only pre-existing users can log in.
 */
interface Provisionable
{
    /**
     * Create a new user account from an email address.
     *
     * Called when a login attempt is made from an allowed domain and no
     * matching user exists. Return null to reject provisioning.
     */
    public static function provisionFromEmail(string $email, string $name): ?Authenticatable;
}
