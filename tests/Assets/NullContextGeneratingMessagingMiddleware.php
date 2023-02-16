<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Assets;

use Profesia\Psr\Middleware\AbstractMessagingMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class NullContextGeneratingMessagingMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        string $contextHeaderKey
    )
    {
        parent::__construct($responseFactory, $contextHeaderKey);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->info('Testing', $this->generateContext($request));

        return $handler->handle($request);
    }
}
