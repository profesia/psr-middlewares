<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Profesia\Psr\Middleware\Exception\ContextGenerationException;
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
     * @throws ContextGenerationException
     */
    public function generate(ServerRequestInterface $request): array
    {
        try {
            $decodedContent = $request->getParsedBody();

            $returnArray = [];
            foreach ($this->requiredKeysStructure as $outputKey => $path) {
                $returnArray[$outputKey] = self::extractPathValue($decodedContent, explode('.', $path));
            }


            return $returnArray;
        } catch (RuntimeException $e) {
            $requiredStructureString = implode(', ', $this->requiredKeysStructure);

            throw new ContextGenerationException("Required structure of payload: [{$requiredStructureString}] is was not supplied in the message payload");
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
