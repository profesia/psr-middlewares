<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\MessagingCorrelationIdOverrideMiddleware;
use Profesia\Psr\Middleware\Test\Assets\TestCorrelationIdResolver;
use Profesia\Psr\Middleware\Test\Assets\TestRequestHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use stdClass;

class MessagingCorrelationIdOverrideMiddlewareTest extends TestCase
{
    public function provideDataForErrorResponses(): array
    {
        return [
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors1.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    null,
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Bad payload supplied',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors2.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    new stdClass(),
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Bad payload supplied',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors3.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [

                    ],
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [key1] in the correlationId path: []',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors4.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'key1' => [],
                    ],
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [key2] in the correlationId path: [key1]',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors5.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'key1' => [
                            'key2' => [
                            ],
                        ],
                    ],
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [key3] in the correlationId path: [key1.key2]',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors6.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'key1' => [
                            'key2' => [
                                'key3' => [],
                            ],
                        ],
                    ],
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Extracted value should be of the string type',
                ],
            ],
            [
                (new ServerRequest(
                    'POST',
                    'https://test.errors7.sk',
                    [
                        'Content-Type' => [
                            'application/json',
                        ],
                    ]
                ))->withParsedBody(
                    [
                        'key1' => [
                            'key2' => [
                                'key3' => 10,
                            ],
                        ],
                    ],
                ),
                [
                    'status'  => 'Bad request',
                    'message' => 'Extracted value should be of the string type',
                ],
            ],
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $expectedResponse
     *
     * @return void
     * @dataProvider provideDataForErrorResponses
     */
    public function testErrorResponses(ServerRequestInterface $request, array $expectedResponse): void
    {
        $factory    = new Psr17Factory();
        $resolver   = new TestCorrelationIdResolver();
        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            new NullLogger(),
            $resolver,
            [
                'key1',
                'key2',
                'key3',
            ],
            'headerContextKey'
        );

        $actualResponse = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );
        $this->assertEquals(200, $actualResponse->getStatusCode());
        $this->assertEquals('OK', $actualResponse->getReasonPhrase());
        $body        = $actualResponse->getBody();
        $decodedBody = json_decode((string)$body, true);

        $this->assertEquals($expectedResponse, $decodedBody);
        $this->assertEquals(TestCorrelationIdResolver::CORRELATION_ID, $resolver->resolve());
    }

    public function testCanProcess(): void
    {
        $factory    = new Psr17Factory();
        $resolver   = new TestCorrelationIdResolver();
        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            new NullLogger(),
            $resolver,
            [
                'key1',
                'key2',
                'key3',
            ],
            'headerContextKey'
        );

        $correlationId = 'testValue';
        $request       = new ServerRequest(
            'POST',
            'https://test.process.sk'
        );
        $request       = $request->withHeader(
            'Content-Type',
            ['application/json']
        )->withParsedBody(
            [
                'key1' => [
                    'key2' => [
                        'key3' => $correlationId,
                    ],
                ],
            ]
        );

        $actualResponse = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );
        $this->assertEquals(200, $actualResponse->getStatusCode());
        $this->assertEquals('OK', $actualResponse->getReasonPhrase());
        $body        = $actualResponse->getBody();
        $decodedBody = json_decode((string)$body, true);

        $this->assertEquals(
            [
                'headers'    => [
                    'Host'         => [
                        'test.process.sk',
                    ],
                    'Content-Type' => [
                        'application/json',
                    ],
                ],
                'body'       => '',
                'parsedBody' => [
                    'key1' => [
                        'key2' => [
                            'key3' => $correlationId,
                        ],
                    ],
                ],
            ],
            $decodedBody
        );

        $this->assertEquals($correlationId, $resolver->resolve());
    }
}
