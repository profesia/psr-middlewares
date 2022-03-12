<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use JsonException;

class JsonDecodingMiddleware implements MiddlewareInterface
{
    private const HEADER_NAME      = 'Content-Type';
    private const CONTENT_TYPE     = 'application/json';
    private const HTTP_BAD_REQUEST = 400;

    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $content = $request->getBody()->getContents();
        if ($content === '') {
            return $handler->handle($request);
        }

        $contentType = $request->getHeaderLine(self::HEADER_NAME);

        if (str_contains($contentType, self::CONTENT_TYPE) === false) {
            return $handler->handle($request);
        }

        try {
            $decodedJson = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $requestBody = $request->getBody();
            if ($requestBody->isSeekable()) {
                $requestBody->rewind();

                $request = $request->withBody(
                    $requestBody
                );
            }

            return $handler->handle(
                $request
                    ->withParsedBody($decodedJson)
            );
        } catch (JsonException $e) {
            return $this->responseFactory->createResponse(
                self::HTTP_BAD_REQUEST,
                'Invalid JSON payload'
            );
        }

    }

}
