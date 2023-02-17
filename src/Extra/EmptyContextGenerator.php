<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @deprecated
 */
final class EmptyContextGenerator implements RequestContextGeneratingInterface
{
    public function generate(ServerRequestInterface $request): array
    {
        return [];
    }
}
