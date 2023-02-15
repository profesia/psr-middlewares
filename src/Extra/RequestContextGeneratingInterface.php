<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Profesia\Psr\Middleware\Exception\ContextGenerationException;
use Psr\Http\Message\ServerRequestInterface;

interface RequestContextGeneratingInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     * @throws ContextGenerationException
     */
    public function generate(ServerRequestInterface $request): array;
}
