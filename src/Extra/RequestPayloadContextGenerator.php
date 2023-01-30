<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RequestPayloadContextGenerator implements RequestContextGeneratingInterface
{

    public function __construct(
        private array $requiredKeysStructure
    ) {
    }

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

        try {
            $returnArray = [];
            foreach ($this->requiredKeysStructure as $outputKey => $path) {
                $returnArray[$outputKey] = self::extractPathValue($decodedContent, explode('.', $path));
            }


            return $returnArray;
        } catch (RuntimeException $e) {
            $requiredStructureString = implode(', ', $this->requiredKeysStructure);

            throw new RuntimeException("Required structure of payload: [{$requiredStructureString}] is was not supplied in the message payload");
        }
    }

    private static function extractPathValue(array $payload, array $requiredKeysStructure): mixed
    {
        $payloadPart = $payload;
        foreach ($requiredKeysStructure as $key) {
            if (array_key_exists($key, $payloadPart) === false) {
                throw new RuntimeException();
            }

            $payloadPart = $payloadPart[$key];
        }

        return $payloadPart;
    }
}
