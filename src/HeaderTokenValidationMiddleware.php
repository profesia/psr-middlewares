<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HeaderTokenValidationMiddleware implements MiddlewareInterface
{
    private const HTTP_UNAUTHORIZED = 401;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private string $headerName,
        private string $token
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $credentials = $request->getHeader($this->headerName);
        $headerToken = $credentials[0] ?? null;

        if ($this->token !== $headerToken) {
            return $this->responseFactory->createResponse(
                self::HTTP_UNAUTHORIZED,
                'Unauthorized'
            );
        }

        return $handler->handle($request);
    }
}
