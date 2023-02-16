<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\Psr\Middleware\MessagingPayloadValueToHeaderMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use stdClass;

class MessagingPayloadValueToHeaderMiddlewareTest extends MockeryTestCase
{
    public function provideDataForInvalidPayload(): array
    {
        return [
            [
                new stdClass(),
            ],
            [
                null,
            ],
            [
                'test',
            ],
        ];
    }

    /**
     * @param mixed $payload
     *
     * @return void
     * @dataProvider provideDataForInvalidPayload
     */
    public function testCanDetectInvalidPayload(mixed $payload): void
    {
        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $stream) {
                    $decodedContent = json_decode((string)$stream, true);

                    return (
                        [
                            'status'  => 'Unprocessable entity',
                            'message' => 'Payload is not an array',
                        ] === $decodedContent
                    );
                }
            )->andReturn($response);
        $response
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                    [
                        'application/json',
                    ],
                ]
            )->andReturn(
                $response
            );

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
                    'Payload is not an array',
                ]
            );

        $contextHeaderKey = 'contextHeaderKey';

        $middleware = new MessagingPayloadValueToHeaderMiddleware(
            $factory,
            $logger,
            $contextHeaderKey,
            []
        );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn($payload);

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function testCanDetectBadPayloadStructure(): void
    {
        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $stream) {
                    $decodedContent = json_decode((string)$stream, true);

                    return (
                        [
                            'status'  => 'Unprocessable entity',
                            'message' => 'Unprocessable entity. Cause: [Missing key: [key4] in path: [message.attributes]]',
                        ] === $decodedContent
                    );
                }
            )->andReturn($response);
        $response
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                    [
                        'application/json',
                    ],
                ]
            )->andReturn(
                $response
            );

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
                    'Unprocessable entity. Cause: [Missing key: [key4] in path: [message.attributes]]',
                ]
            );

        $contextHeaderKey = 'contextHeaderKey';

        $middleware = new MessagingPayloadValueToHeaderMiddleware(
            $factory,
            $logger,
            $contextHeaderKey,
            [
                'test1' => 'message.attributes.key1',
                'test2' => 'message.attributes.key2',
                'test3' => 'message.attributes.key3',
                'test4' => 'message.attributes.key4',
            ]
        );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                [
                    'message' => [
                        'attributes' => [
                            'key1' => 'value1',
                            'key2' => 'value2',
                            'key3' => 'value3',
                        ],
                    ],
                ]
            );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function testCanProcess(): void
    {
        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        /** @var MockInterface|LoggerInterface $logger */
        $logger           = Mockery::mock(LoggerInterface::class);
        $contextHeaderKey = 'contextHeaderKey';

        $middleware = new MessagingPayloadValueToHeaderMiddleware(
            $factory,
            $logger,
            $contextHeaderKey,
            [
                'test1' => 'message.attributes.key1',
                'test2' => 'message.attributes.key2',
                'test3' => 'message.attributes.key3',
                'test4' => 'message.attributes.key4',
            ]
        );

        $payload = [
            'message' => [
                'attributes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                    'key4' => 'value4',
                ],
            ],
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
            ->shouldReceive('withHeader')
            ->once()
            ->withArgs(
                [
                    $contextHeaderKey,
                    [
                        'test1=value1',
                        'test2=value2',
                        'test3=value3',
                        'test4=value4',
                    ],
                ]
            )->andReturn($request);

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->withArgs(
                [
                    $request,
                ]
            );

        $middleware->process($request, $handler);
    }
}
