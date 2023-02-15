<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;

final class EmptyContextGenerator implements RequestContextGeneratingInterface
{
    /**
     * @inheritDoc
     */
    public function generate(ServerRequestInterface $request): array
    {
        return [];
    }
}
