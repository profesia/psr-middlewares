<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Profesia\CorrelationId\Resolver\CorrelationIdResolver;
use Profesia\Psr\Middleware\MessagingCorrelationIdOverrideMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingCorrelationIdOverrideMiddlewareTest extends MockeryTestCase
{
    public function provideDataForBadPayloadTest(): array
    {
        return [
            [
                null,
            ],
            [
                new \stdClass(),
            ],
        ];
    }

    /**
     * @param mixed $payload
     *
     * @return void
     * @dataProvider provideDataForBadPayloadTest
     */
    public function testCanDetectBadPayload(mixed $payload): void
    {
        $pathToCorrelationId = [
            'test',
            'key1',
            'key2',
        ];

        $contextHeaderKey = 'header';
        $context          = [
            'key1=value1',
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                $payload
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $contextHeaderKey,
                ]
            )->andReturn(
                $context
            );

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $body) {
                    $decodedContent = json_decode((string)$body, true);

                    return ($decodedContent === [
                            'status'  => 'Bad request',
                            'message' => 'Bad payload supplied',
                        ]);
                }
            )->andReturn(
                $response
            );
        $response
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                    ['application/json'],
                ]
            )->andReturn($response);

        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $factory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    200,
                    'OK',
                ]
            )->andReturn(
                $response
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(
                [
                    'Bad payload supplied',
                    [
                        'key1' => 'value1',
                    ],
                ]
            );

        /** @var MockInterface|CorrelationIdResolver $resolver */
        $resolver = Mockery::mock(CorrelationIdResolver::class);
        $resolver
            ->shouldNotReceive();

        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            $logger,
            $resolver,
            $pathToCorrelationId,
            $contextHeaderKey
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function provideDataForExtractionException(): array
    {
        return [
            [
                [
                    'key1',
                    'key2',
                    'key3',
                ],
                [],
                'Missing key: [key1] in the correlationId path: []',
            ],
            [
                [
                    'key1',
                    'key2',
                    'key3',
                ],
                [
                    'key1' => [],
                ],
                'Missing key: [key2] in the correlationId path: [key1]',
            ],
            [
                [
                    'key1',
                    'key2',
                    'key3',
                ],
                [
                    'key1' => [
                        'key2' => [],
                    ],
                ],
                'Missing key: [key3] in the correlationId path: [key1.key2]',
            ],
        ];
    }

    /**
     * @param array  $pathToCorrelationId
     * @param array  $payload
     * @param string $errorMessage
     *
     * @return void
     * @dataProvider provideDataForExtractionException
     */
    public function testCanDetectExtractionException(array $pathToCorrelationId, array $payload, string $errorMessage): void
    {
        $contextHeaderKey = 'header';
        $context          = [
            'key1=value1',
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                $payload
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $contextHeaderKey,
                ]
            )->andReturn(
                $context
            );

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $body) use ($errorMessage) {
                    $decodedContent = json_decode((string)$body, true);

                    return ($decodedContent === [
                            'status'  => 'Bad request',
                            'message' => $errorMessage,
                        ]);
                }
            )->andReturn(
                $response
            );
        $response
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                    ['application/json'],
                ]
            )->andReturn($response);

        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $factory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    200,
                    'OK',
                ]
            )->andReturn(
                $response
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(
                [
                    $errorMessage,
                    [
                        'key1' => 'value1',
                    ],
                ]
            );

        /** @var MockInterface|CorrelationIdResolver $resolver */
        $resolver = Mockery::mock(CorrelationIdResolver::class);
        $resolver
            ->shouldNotReceive();

        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            $logger,
            $resolver,
            $pathToCorrelationId,
            $contextHeaderKey
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function testCanDetectNotStringCorrelationId(): void
    {
        $contextHeaderKey = 'header';
        $context          = [
            'key1=value1',
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                [
                    'key1' => [
                        'key2' => [
                            'key3' => [],
                        ],
                    ],
                ]
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $contextHeaderKey,
                ]
            )->andReturn(
                $context
            );

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $body) {
                    $decodedContent = json_decode((string)$body, true);

                    return ($decodedContent === [
                            'status'  => 'Bad request',
                            'message' => 'Extracted value should be of the string type',
                        ]);
                }
            )->andReturn(
                $response
            );
        $response
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                    ['application/json'],
                ]
            )->andReturn($response);

        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $factory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    200,
                    'OK',
                ]
            )->andReturn(
                $response
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(
                [
                    'Extracted value should be of the string type',
                    [
                        'key1' => 'value1',
                    ],
                ]
            );

        /** @var MockInterface|CorrelationIdResolver $resolver */
        $resolver = Mockery::mock(CorrelationIdResolver::class);
        $resolver
            ->shouldNotReceive();

        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            $logger,
            $resolver,
            [
                'key1',
                'key2',
                'key3',
            ],
            $contextHeaderKey
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function testCanProcess(): void
    {
        $correlationId    = 'value';
        $contextHeaderKey = 'header';
        $context          = [
            'key1=value1',
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                [
                    'key1' => [
                        'key2' => [
                            'key3' => $correlationId,
                        ],
                    ],
                ]
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $contextHeaderKey,
                ]
            )->andReturn(
                $context
            );

        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $factory
            ->shouldNotReceive('createResponse');

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    "Storing correlation ID: [{$correlationId}]",
                    [
                        'key1' => 'value1',
                    ],
                ]
            );

        /** @var MockInterface|CorrelationIdResolver $resolver */
        $resolver = Mockery::mock(CorrelationIdResolver::class);
        $resolver
            ->shouldReceive('store')
            ->once()
            ->withArgs(
                [
                    $correlationId
                ]
            );

        $middleware = new MessagingCorrelationIdOverrideMiddleware(
            $factory,
            $logger,
            $resolver,
            [
                'key1',
                'key2',
                'key3',
            ],
            $contextHeaderKey
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            );

        $middleware->process($request, $handler);
    }
}
