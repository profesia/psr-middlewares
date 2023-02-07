<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Exception;
use Google\Client;
use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class GoogleBearerTokenVerificationMessagingMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Client $client,
        private LoggerInterface $logger,
        private string $headerName,
        ?RequestContextGeneratingInterface $contextGenerator = null
    ) {
        parent::__construct($this->responseFactory, $contextGenerator);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $bearerToken  = implode(' ', $request->getHeader($this->headerName));
        $idTokenParts = explode(' ', $bearerToken);
        $context      = $this->generateContext($request);

        if (count($idTokenParts) !== 2) {
            $this->logger->error(
                'Bearer token has invalid format - it should contains two strings separated by a blank space',
                $context
            );

            return $this->createInvalidResponseWithHeaders(
                ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
            );
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
            return $this->createInvalidResponseWithHeaders(
                ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
            );
        }

        return $handler->handle($request);
    }
}
