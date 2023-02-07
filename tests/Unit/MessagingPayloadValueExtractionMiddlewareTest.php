<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\Psr\Middleware\Extra\RequestContextGeneratingInterface;
use Profesia\Psr\Middleware\Extra\ServerVariablesStorageInterface;
use Profesia\Psr\Middleware\MessagingPayloadValueExtractionMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingPayloadValueExtractionMiddlewareTest extends MockeryTestCase
{
    public function provideDataForDetectingMissingPayloadPath(): array
    {
        return [
            [
                [
                    'message',
                    'attributes',
                    'correlationId',
                ],
                [
                ],
                "Missing key: [message] in the payload path: []",
            ],
            [
                [
                'test'
                ],
                null,
                'No payload supplied'
            ],
        ];
    }

    /**
     * @param array  $path
     * @param mixed  $parsedBody
     * @param string $exceptionMessage
     *
     * @return void
     * @dataProvider provideDataForDetectingMissingPayloadPath
     */
    public function testCanDetectErrorStates(array $path, mixed $parsedBody, string $exceptionMessage): void
    {
        $storeKey = 'storeKey';
        $context  = [
            'context' => [],
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                $parsedBody
            );

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $stream) use ($exceptionMessage): bool {
                    $jsonString = (string)$stream;

                    return (
                        ['status' => 'Bad request', 'message' => $exceptionMessage] === json_decode($jsonString, true)
                    );
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
            )->andReturn(
                $response
            );


        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
        $responseFactory
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
                    $exceptionMessage,
                    $context,
                ]
            );

        /** @var MockInterface|ServerVariablesStorageInterface $variableStorage */
        $variableStorage = Mockery::mock(ServerVariablesStorageInterface::class);

        /** @var MockInterface|RequestContextGeneratingInterface $contextGenerator */
        $contextGenerator = Mockery::mock(RequestContextGeneratingInterface::class);
        $contextGenerator
            ->shouldReceive('generate')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            )->andReturn(
                $context
            );

        $middleware = new MessagingPayloadValueExtractionMiddleware(
            $responseFactory,
            $logger,
            $variableStorage,
            $path,
            $storeKey,
            $contextGenerator
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanProcess(): void
    {
        $value = 'testValue';
        $path = [
            'message',
            'attributes',
            'test'
        ];
        $parsedBody = [
            'message' => [
                'attributes' => [
                    'test' => $value
                ]
            ]
        ];
        $storeKey = 'storeKey';
        $context  = [
            'context' => [],
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(
                $parsedBody
            );

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);

        /** @var MockInterface|ServerVariablesStorageInterface $variableStorage */
        $variableStorage = Mockery::mock(ServerVariablesStorageInterface::class);
        $variableStorage
            ->shouldReceive('store')
            ->once()
            ->withArgs(
                [
                    $storeKey,
                    $value
                ]
            );

        /** @var MockInterface|RequestContextGeneratingInterface $contextGenerator */
        $contextGenerator = Mockery::mock(RequestContextGeneratingInterface::class);
        $contextGenerator
            ->shouldReceive('generate')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            )->andReturn(
                $context
            );

        $middleware = new MessagingPayloadValueExtractionMiddleware(
            $responseFactory,
            $logger,
            $variableStorage,
            $path,
            $storeKey,
            $contextGenerator
        );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn(
                $response
            );

        $middleware->process(
            $request,
            $handler
        );
    }
}
