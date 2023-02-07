<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Extra;


interface ServerVariablesStorageInterface
{
    public function store(string $key, string $value): void;
}
