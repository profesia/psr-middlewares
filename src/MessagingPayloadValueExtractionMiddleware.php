<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\Psr\Middleware\Exception\BadConfigurationException;
use Profesia\Psr\Middleware\Exception\ContextGenerationException;
use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Profesia\Psr\Middleware\Extra\ServerVariablesStorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingPayloadValueExtractionMiddleware extends AbstractMessagingMiddleware
{
    /**
     * @param ResponseFactoryInterface        $responseFactory
     * @param LoggerInterface                 $logger
     * @param ServerVariablesStorageInterface $variablesStore
     * @param array                           $pathToPayloadValue
     * @param string                          $payloadValueStoreKey
     * @param string                          $contextHeaderKey
     *
     * @throws BadConfigurationException
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private ServerVariablesStorageInterface $variablesStore,
        private array $pathToPayloadValue,
        private string $payloadValueStoreKey,
        string $contextHeaderKey
    ) {
        if ($this->pathToPayloadValue === []) {
            throw new BadConfigurationException('Path to payload value could not be empty');
        }

        parent::__construct($this->responseFactory, $contextHeaderKey);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $this->generateContext($request);
        $payload = $request->getParsedBody();

        if (is_array($payload) === false) {
            $message = 'No payload supplied';
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
        foreach ($this->pathToPayloadValue as $key) {
            if (array_key_exists($key, $extractedValue) === false) {
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
            $extractedValue = $extractedValue[$key];
        }

        if (is_array($extractedValue) === true) {
            $message = 'Extracted value should be of a primitive type';

            $this->logger->error($message, $context);

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Bad request',
                    'message' => $message,
                ]

            );
        }

        $this->logger->info("Storing value: [{$extractedValue}] under key: [{$this->payloadValueStoreKey}]");
        $this->variablesStore->store($this->payloadValueStoreKey, $extractedValue);

        return $handler->handle($request);
    }
}
