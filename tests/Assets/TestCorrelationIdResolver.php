<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Assets;

use Profesia\CorrelationId\Resolver\CorrelationIdResolverInterface;

class TestCorrelationIdResolver implements CorrelationIdResolverInterface
{
    const CORRELATION_ID = 'c0c622a2-2d2d-4c1b-9add-e27f10ef909a';

    public function __construct(
        private ?string $generatedId = null
    ) {
    }

    public function resolve(): string
    {
        if ($this->generatedId === null) {
            return self::CORRELATION_ID;
        }

        return $this->generatedId;
    }

    public function store(?string $value = null): void
    {
        if ($value === null) {
            $value = $this->resolve();
        }

        $this->generatedId = $value;
    }
}
