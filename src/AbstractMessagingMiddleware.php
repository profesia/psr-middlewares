<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Nyholm\Psr7\Stream;
use Profesia\Psr\Middleware\Extra\EmptyContextGenerator;
use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

abstract class AbstractMessagingMiddleware implements MiddlewareInterface
{
    protected const HTTP_OK = 200;

    private RequestContextGeneratingInterface $contextGenerator;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        ?RequestContextGeneratingInterface $contextGenerator = null
    ) {
        if ($contextGenerator === null) {
            $this->contextGenerator = new EmptyContextGenerator();
        } else {
            $this->contextGenerator = $contextGenerator;
        }
    }

    protected function generateContext(ServerRequestInterface $request): array
    {
        return $this->contextGenerator->generate(
            $request
        );
    }

    protected function createInvalidResponseWithHeaders(array $payload): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(
            self::HTTP_OK,
            'OK',
        );
        $response = $response->withBody(
            Stream::create(
                json_encode(
                    $payload
                )
            )
        );

        $headers = [
            'Content-Type' => [
                'application/json',
            ],
        ];

        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        return $response;
    }
}
