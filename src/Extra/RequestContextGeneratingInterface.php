<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;
use Exception;

/**
 * @deprecated
 */
interface RequestContextGeneratingInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     * throws Exception
     */
    public function generate(ServerRequestInterface $request): array;
}
