<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Extra;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints;

final class RequestPayloadContextGenerator implements RequestContextGeneratingInterface
{

    public function __construct(
        //private ValidatorInterface $validator,
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

        $validator = Validation::createValidator();

        $constraint = new Constraints\Collection(
            [
                'message' => new Constraints\Collection(
                    [
                        'attributes' => new Constraints\Collection(
                            [
                                'eventType'       => new Constraints\NotBlank(),
                                'eventOccurredOn' => new Constraints\DateTime(),
                                'correlationId'   => new Constraints\Uuid(),
                                'target'          => new Constraints\NotBlank(),
                            ]
                        ),
                    ]
                ),
            ]
        );

        echo '<pre>';
        var_dump($validator->validate($decodedContent, $constraint));
        exit;

        return [
            'eventType'     => $decodedContent['message']['attributes']['eventType'],
            'occurredOn'    => $decodedContent['message']['attributes']['eventOccurredOn'],
            'correlationId' => $decodedContent['message']['attributes']['correlationId'],
            'target'        => $decodedContent['message']['attributes']['target'],
        ];
    }
}
