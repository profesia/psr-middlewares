<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\Psr\Middleware\Exception\BadPayloadStructureException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingPayloadValueToHeaderMiddleware extends AbstractMessagingMiddleware
{
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        string $contextHeaderKey,
        private array $requiredKeysStructure
    ) {
        parent::__construct($responseFactory, $contextHeaderKey);
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $payload = $request->getParsedBody();

        if (is_array($payload) === false) {
            $message = 'Payload is not an array';
            $this->logger->error($message);

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Unprocessable entity',
                    'message' => $message,
                ]
            );
        }

        try {
            $headerValues = [];
            foreach ($this->requiredKeysStructure as $outputKey => $path) {
                $headerValues[$outputKey] = self::extractPathValue($payload, explode('.', $path));
            }

            return $handler->handle(
                $request->withHeader(
                    $this->getContextHeaderKey(),
                    $headerValues
                )
            );
        } catch (BadPayloadStructureException $e) {
            $message = "Unprocessable entity. Cause: [{$e->getMessage()}]";
            $this->logger->error($message);

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Unprocessable entity',
                    'message' => $message,
                ]
            );
        }
    }

    /**
     * @param array $payload
     * @param array $requiredKeyStructure
     *
     * @return mixed
     * @throws BadPayloadStructureException
     */
    private static function extractPathValue(array $payload, array $requiredKeyStructure): mixed
    {
        $payloadPart = $payload;
        $foundKeys   = [];
        foreach ($requiredKeyStructure as $key) {
            if (array_key_exists($key, $payloadPart) === false) {
                $path = implode('.', $foundKeys);

                throw new BadPayloadStructureException("Missing key: [$key] in path: [$path]");
            }

            $foundKeys[] = $key;
            $payloadPart = $payloadPart[$key];
        }

        return $payloadPart;
    }
}
