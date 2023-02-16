<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Assets;

use Profesia\Psr\Middleware\Extra\ServerVariablesStorageInterface;

class NullServerVariablesStorage implements ServerVariablesStorageInterface
{
    public function store(string $key, string $value): void
    {
        // TODO: Implement store() method.
    }
}
