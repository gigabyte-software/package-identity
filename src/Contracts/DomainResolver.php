<?php

namespace Gigabyte\Identity\Contracts;

interface DomainResolver
{
    public function isDomainAllowed(string $domain): bool;
}
