<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware;

use Profesia\Psr\Middleware\Exception\DecodingException;
use Psr\Http\Message\StreamInterface;
use JsonException;

final class MessagingJsonPayloadMiddleware extends AbstractMessagingDecodingMiddleware
{
    protected function decode(StreamInterface $body): mixed
    {
        $content = (string)$body;

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new DecodingException(
                $e->getMessage(),
            );
        }
    }

    protected function getSupportedContentType(): array
    {
        return ['application/json'];
    }
}
