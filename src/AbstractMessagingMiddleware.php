<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

abstract class AbstractMessagingMiddleware implements MiddlewareInterface
{
    protected const HTTP_OK = 200;

    private array $context = [];
    private bool $contextGenerated = false;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private string $contextHeaderKey
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function generateContext(ServerRequestInterface $request): array
    {
        if ($this->contextGenerated === false) {
            foreach ($request->getHeader($this->contextHeaderKey) as $headerLine) {
                if (str_contains($headerLine, '=')) {
                    [$headerName, $headerValue] = explode('=', $headerLine);

                    $this->context[$headerName] = $headerValue;
                } else {
                    $this->context[] = $headerLine;
                }
            }
        }

        $this->contextGenerated = true;

        return $this->context;
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

    protected function getContextHeaderKey(): string
    {
        return $this->contextHeaderKey;
    }
}
