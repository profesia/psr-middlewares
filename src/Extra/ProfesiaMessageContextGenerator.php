<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ProfesiaMessageContextGenerator implements RequestContextGeneratingInterface
{

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     * @throws RuntimeException
     */
    public function generate(ServerRequestInterface $request): array
    {
        $body = $request->getBody();
        $body->rewind();
        $rawContents    = $body->getContents();
        $decodedContent = json_decode($rawContents, true, JSON_THROW_ON_ERROR);
        $body->rewind();

        if (array_key_exists('message', $decodedContent) === false) {
            throw new RuntimeException('Missing [message] key in the message payload');
        }

        if (array_key_exists('attributes', $decodedContent['message']) === false) {
            throw new RuntimeException('Missing [attributes] key in the message["message"] payload');
        }

        $requiredKeys = [
            'eventType',
            'eventOccurredOn',
            'correlationId',
            'target'
        ];

        $missingKeys = [];
        foreach ($requiredKeys as $requiredKey) {
            if (array_key_exists($requiredKey, $decodedContent['message']['attributes']) === false) {
                $missingKeys[] = $requiredKey;
            }
        }

        if ($missingKeys !== []) {
            $missingKeysString = implode(', ', $missingKeys);
            throw new RuntimeException("Missing [{$missingKeysString}] key in the message[\"message\"][\"attributes\"] payload");
        }

        return [
            'eventType'     => $decodedContent['message']['attributes']['eventType'],
            'occurredOn'    => $decodedContent['message']['attributes']['eventOccurredOn'],
            'correlationId' => $decodedContent['message']['attributes']['correlationId'],
            'target'        => $decodedContent['message']['attributes']['target'],
        ];
    }

}
