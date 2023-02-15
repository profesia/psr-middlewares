<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\Psr\Middleware\Exception\DecodingException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractMessagingDecodingMiddleware extends AbstractMessagingMiddleware
{
    private array $supportedMethods = [];

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        array $supportedMethods,
        string $contextHeaderKey
    ) {
        foreach ($supportedMethods as $method) {
            $this->supportedMethods[strtolower($method)] = true;
        }

        parent::__construct(
            $responseFactory,
            $contextHeaderKey
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $this->generateContext($request);
        $method  = $request->getMethod();

        if (array_key_exists(strtolower($method), $this->supportedMethods) === false) {
            $this->logger->info(
                "Unsupported method: [$method] supplied. Skipping decoding.",
                $context
            );

            return $handler->handle($request);
        }

        if ($this->shouldBeApplied($request) === false) {
            return $handler->handle($request);
        }

        try {
            $decodedContent = $this->decode($request->getBody());
            $class          = get_class($this);
            $this->logger->info(
                "Successfully parsed payload via: [$class]",
                $context
            );

            return $handler->handle(
                $request->withParsedBody(
                    $decodedContent
                )
            );
        } catch (DecodingException $e) {
            $message = "Error while decoding payload. Cause: [{$e->getMessage()}]";
            $this->logger->error(
                $message,
                $context
            );

            return $this->createInvalidResponseWithHeaders(
                [
                    'status'  => 'Unprocessable entity',
                    'message' => $message,
                ]
            );
        }
    }

    /**
     * @param StreamInterface $body
     *
     * @return mixed
     * @throws DecodingException
     */
    protected abstract function decode(StreamInterface $body): mixed;

    protected abstract function getSupportedContentType(): array;

    protected function shouldBeApplied(ServerRequestInterface $request): bool
    {
        $supportedContentTypes = $this->getSupportedContentType();
        $actualContentType     = $request->getHeaderLine('Content-Type');

        foreach ($supportedContentTypes as $supportedType) {
            if (str_contains($supportedType, $actualContentType) === true) {
                return true;
            }
        }

        $class = get_class($this);
        $this->logger->info(
            "Content-Type: [$actualContentType] is not supported by: [$class]. Skipping decoding.",
            $this->generateContext($request)
        );

        return false;
    }
}
