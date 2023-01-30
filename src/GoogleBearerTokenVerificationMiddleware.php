<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Exception;
use Google\Client;
use Nyholm\Psr7\Stream;
use Profesia\Psr\Middleware\Extra\EmptyContextGenerator;
use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class GoogleBearerTokenVerificationMiddleware implements MiddlewareInterface
{
    private const HTTP_OK = 200;
    private RequestContextGeneratingInterface $contextGenerator;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Client $client,
        private LoggerInterface $logger,
        private string $headerName,
        ?RequestContextGeneratingInterface $contextGenerator = null
    ) {
        if ($contextGenerator === null) {
            $this->contextGenerator = new EmptyContextGenerator();
        } else {
            $this->contextGenerator = $contextGenerator;
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $bearerToken  = implode(' ', $request->getHeader($this->headerName));
        $idTokenParts = explode(' ', $bearerToken);
        $context      = $this->contextGenerator->generate($request);

        if (count($idTokenParts) !== 2) {
            $this->logger->error(
                'Bearer token has invalid format - it should contains two strings separated by a blank space',
                $context
            );

            return $this->createInvalidResponseWithHeaders();
        }

        $idToken    = $idTokenParts[1];
        $validToken = false;
        try {
            $validToken = $this->client->verifyIdToken($idToken);

            $output = ($validToken === false) ? 'false' : 'true';
            $this->logger->info(
                "Verification of token done with output: [{$output}]",
                $context
            );
        } catch (Exception $e) {
            $this->logger->error(
                "An error during verification of the token occurred. Cause: [{$e->getMessage()}]",
                $context
            );
        }

        if ($validToken === false) {
            return $this->createInvalidResponseWithHeaders();
        }

        return $handler->handle($request);
    }

    private function createInvalidResponseWithHeaders(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(
            self::HTTP_OK,
            'OK',
        );
        $response = $response->withBody(
            Stream::create(
                json_encode(
                    ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
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
