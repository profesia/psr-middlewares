<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration\Extra;

use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\Extra\EnvironmentVariablesStorage;

class EnvironmentApacheVariablesStorageTest extends TestCase
{
    public function testCanStoreVariableIntoEnv(): void
    {
        $storage = new EnvironmentVariablesStorage(
            'php_sapi_name'
        );

        $key   = 'key';
        $value = 'value';

        $this->assertEquals(false, getenv($key));
        $storage->store('key', $value);

        $this->assertEquals($value, getenv($key));
    }
}
