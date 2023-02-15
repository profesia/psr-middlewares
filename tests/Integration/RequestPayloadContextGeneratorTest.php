<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\Exception\ContextGenerationException;
use Profesia\Psr\Middleware\Extra\RequestPayloadContextGenerator;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RequestPayloadContextGeneratorTest extends TestCase
{
    public function provideIncompleteRequestData(): array
    {
        $requiredKeysStructure = [
            'eventType'     => 'message.attributes.eventType',
            'occurredOn'    => 'message.attributes.eventOccurredOn',
            'correlationId' => 'message.attributes.correlationId',
            'target'        => 'message.attributes.target',
        ];

        $requiredKeysString = implode(', ', $requiredKeysStructure);

        return [
            [
                (new ServerRequest(
                    'get',
                    'https://test2.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody([]),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
            [
                (new ServerRequest(
                    'get',
                    'https://test3.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'message' => [],
                    ]
                ),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
            [
                (new ServerRequest(
                    'get',
                    'https://test4.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'message' => [
                            'attributes' => [],
                        ],
                    ]
                ),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
            [
                (new ServerRequest(
                    'get',
                    'https://test5.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'message' => [
                            'attributes' => [
                                'eventType' => 'TestEvent',
                            ],
                        ],
                    ]
                ),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
            [
                (new ServerRequest(
                    'get',
                    'https://test6.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'message' => [
                            'attributes' => [
                                'eventType'       => 'TestEvent',
                                'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                            ],
                        ],
                    ]
                ),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
            [
                (new ServerRequest(
                    'get',
                    'https://test7.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'message' => [
                            'attributes' => [
                                'eventType'       => 'TestEvent',
                                'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                                'correlationId'   => 'test-id',
                            ],
                        ],
                    ]
                ),
                new ContextGenerationException("Required structure of payload: [{$requiredKeysString}] is was not supplied in the message payload"),
            ],
        ];
    }

    public function testCanGenerate(): void
    {
        $data = [
            'eventType'       => 'TestEvent',
            'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'correlationId'   => 'test',
            'target'          => 'target',
        ];

        $generator = new RequestPayloadContextGenerator(
            [
                'eventType'     => 'message.attributes.eventType',
                'occurredOn'    => 'message.attributes.eventOccurredOn',
                'correlationId' => 'message.attributes.correlationId',
                'target'        => 'message.attributes.target',
            ]
        );
        $request   = new ServerRequest(
            'get',
            'https://test1.sk',
            [
                'Content-Type' => [
                    'application/json',
                ],
            ]
        );
        $request   = $request->withParsedBody(
            [
                'message' => [
                    'attributes' => $data,
                ],
            ]
        );

        $this->assertEquals(
            [
                'eventType'     => $data['eventType'],
                'occurredOn'    => $data['eventOccurredOn'],
                'correlationId' => $data['correlationId'],
                'target'        => $data['target'],
            ],
            $generator->generate(
                $request
            )
        );
    }

    /**
     * @param ServerRequestInterface     $request
     * @param ContextGenerationException $exception
     *
     * @return void
     * @dataProvider provideIncompleteRequestData
     */
    public function testWillThrowAnExceptionOnRequestStructureMismatch(ServerRequestInterface $request, ContextGenerationException $exception): void
    {
        $generator = new RequestPayloadContextGenerator(
            [
                'eventType'     => 'message.attributes.eventType',
                'occurredOn'    => 'message.attributes.eventOccurredOn',
                'correlationId' => 'message.attributes.correlationId',
                'target'        => 'message.attributes.target',
            ]
        );

        $this->expectExceptionObject($exception);
        $generator->generate($request);
    }
}
