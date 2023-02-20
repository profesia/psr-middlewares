<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\CorrelationId\Resolver\CorrelationIdResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingCorrelationIdOverrideMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private CorrelationIdResolverInterface $resolver,
        private array $pathToCorrelationId,
        string $contextHeaderKey
    )
    {
        parent::__construct(
            $responseFactory,
            $contextHeaderKey
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $this->generateContext($request);
        $payload = $request->getParsedBody();

        if (is_array($payload) === false) {
            $message = 'Bad payload supplied';
            $this->logger->error($message, $context);

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Bad request',
                    'message' => $message,
                ]

            );
        }

        $existingKeys   = [];
        $extractedValue = $payload;
        foreach ($this->pathToCorrelationId as $key) {
            if (array_key_exists($key, $extractedValue) === false) {
                $path    = implode('.', $existingKeys);
                $message = "Missing key: [{$key}] in the correlationId path: [{$path}]";

                $this->logger->error($message, $context);

                return $this->createInvalidResponseWithHeaders(
                    [
                        'status'  => 'Bad request',
                        'message' => $message,
                    ]

                );
            }

            $existingKeys[] = $key;
            $extractedValue = $extractedValue[$key];
        }

        if (is_string($extractedValue) === false) {
            $message = 'Extracted value should be of the string type';

            $this->logger->error($message, $context);

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Bad request',
                    'message' => $message,
                ]

            );
        }

        $this->resolver->store($extractedValue);
        $this->logger->info("Storing correlation ID: [{$extractedValue}]", $context);

        return $handler->handle($request);
    }
}
