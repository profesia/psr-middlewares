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
use RuntimeException;

class MessagingGoogleBearerTokenVerificationMiddleware extends AbstractMessagingMiddleware
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
        $context = [];

        try {
            $context = $this->generateContext($request);
        } catch (RuntimeException $e) {
            $this->logger->error(
                "Error during context generation. Cause: [{$e->getMessage()}]"
            );
        }

        if (count($idTokenParts) !== 2) {
            $this->logger->error(
                'Bearer token has invalid format - it should contains two strings separated by a blank space',
                $context
            );

            return $this->createInvalidResponseWithHeaders(
                ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
            );
        }

        $idToken = $idTokenParts[1];
        try {
            $validToken = $this->client->verifyIdToken($idToken);

            $output = ($validToken === false) ? 'false' : 'true';
            $this->logger->info(
                "Verification of token done with output: [{$output}]",
                $context
            );

            if ($validToken === false) {
                return $this->createInvalidResponseWithHeaders(
                    ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                "An error during verification of the token occurred. Cause: [{$e->getMessage()}]",
                $context
            );
        }

        return $handler->handle($request);
    }
}
