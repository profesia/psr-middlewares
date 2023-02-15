<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Google\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use LogicException;

class MessagingGoogleBearerTokenVerificationMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Client $client,
        private LoggerInterface $logger,
        private string $headerName,
        string $contextHeaderKey
    ) {
        parent::__construct($this->responseFactory, $contextHeaderKey);
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context      = $this->generateContext($request);
        $bearerToken  = implode(' ', $request->getHeader($this->headerName));
        $idTokenParts = explode(' ', $bearerToken);

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

            return $handler->handle($request);
        } catch (UnexpectedValueException|LogicException $e) {
            $message = "An error during verification of the token occurred. Cause: [{$e->getMessage()}]";
            $this->logger->error(
                $message,
                $context
            );

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Unauthorized',
                    'message' => $message,
                ]
            );
        }
    }
}
