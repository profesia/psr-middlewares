<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

class EnvironmentVariablesStorage implements ServerVariablesStorageInterface
{
    public function __construct(
        private string|false $phpSapiName
    ) {
    }

    public function store(string $key, string $value): void
    {
        putenv($key . "={$value}");

        /*if ($this->phpSapiName !== false && str_starts_with($this->phpSapiName, 'apache') === true) {
            apache_setenv($key, $value);
        }*/
    }
}
