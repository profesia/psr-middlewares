<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Profesia\Psr\Middleware\Extra\ServerVariablesStorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingPayloadValueExtractionMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private ServerVariablesStorageInterface $variablesStore,
        private array $pathToPayloadValue,
        private string $payloadValueStoreKey,
        ?RequestContextGeneratingInterface $contextGenerator = null
    ) {
        parent::__construct($this->responseFactory, $contextGenerator);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context      = $this->generateContext($request);
        $finalValue   = $request->getParsedBody();
        $existingKeys = [];
        foreach ($this->pathToPayloadValue as $key) {
            if (array_key_exists($key, $finalValue) === false) {
                $path    = implode(', ', $existingKeys);
                $message = "Missing key: [{$key}] in the payload path: [{$path}]";

                $this->logger->error($message, $context);

                return $this->createInvalidResponseWithHeaders(
                    [
                        'status'  => 'Bad request',
                        'message' => $message,
                    ]

                );
            }

            $existingKeys[] = $key;
            $finalValue     = $finalValue[$key];
        }

        $this->variablesStore->store($this->payloadValueStoreKey, $finalValue);

        return $handler->handle($request);
    }
}
