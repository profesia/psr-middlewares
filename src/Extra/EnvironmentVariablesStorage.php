<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

/**
 * @deprecated
 */
class EnvironmentVariablesStorage implements ServerVariablesStorageInterface
{
    public function store(string $key, string $value): void
    {
        putenv($key . "={$value}");
    }
}
