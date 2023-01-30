<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\Extra\ProfesiaMessageContextGenerator;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class ProfesiaMessageContextGeneratorTest extends TestCase
{
    public function provideIncompleteRequestData(): array
    {
        return [
            [
                new ServerRequest(
                    'get',
                    'https://test2.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                        ]
                    )
                ),
                new RuntimeException('Missing [message] key in the message payload'),
            ],
            [
                new ServerRequest(
                    'get',
                    'https://test3.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                            'message' => [],
                        ]
                    )
                ),
                new RuntimeException('Missing [attributes] key in the message["message"] payload'),
            ],
            [
                new ServerRequest(
                    'get',
                    'https://test4.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                            'message' => [
                                'attributes' => [],
                            ],
                        ]
                    )
                ),
                new RuntimeException(
                    'Missing [eventType, eventOccurredOn, correlationId, target] key in the message["message"]["attributes"] payload'
                ),
            ],
            [
                new ServerRequest(
                    'get',
                    'https://test5.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                            'message' => [
                                'attributes' => [
                                    'eventType' => 'TestEvent',
                                ],
                            ],
                        ]
                    )
                ),
                new RuntimeException('Missing [eventOccurredOn, correlationId, target] key in the message["message"]["attributes"] payload'),
            ],
            [
                new ServerRequest(
                    'get',
                    'https://test6.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                            'message' => [
                                'attributes' => [
                                    'eventType'       => 'TestEvent',
                                    'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                                ],
                            ],
                        ]
                    )
                ),
                new RuntimeException('Missing [correlationId, target] key in the message["message"]["attributes"] payload'),
            ],
            [
                new ServerRequest(
                    'get',
                    'https://test7.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ],
                    json_encode(
                        [
                            'message' => [
                                'attributes' => [
                                    'eventType'       => 'TestEvent',
                                    'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                                    'correlationId'   => 'test-id',
                                ],
                            ],
                        ]
                    )
                ),
                new RuntimeException('Missing [target] key in the message["message"]["attributes"] payload'),
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

        $generator = new ProfesiaMessageContextGenerator();
        $request   = new ServerRequest(
            'get',
            'https://test1.sk',
            [
                'Content-Type' => [
                    'application/json',
                ],
            ],
            json_encode(
                [
                    'message' => [
                        'attributes' => $data,
                    ],
                ]
            )
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
     * @param ServerRequestInterface $request
     * @param RuntimeException       $exception
     *
     * @return void
     * @dataProvider provideIncompleteRequestData
     */
    public function testWillThrowAnExceptionOnRequestStructureMismatch(ServerRequestInterface $request, RuntimeException $exception): void
    {
        $generator = new ProfesiaMessageContextGenerator();

        $this->expectExceptionObject($exception);
        $generator->generate($request);
    }
}
