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
        ];
    }

    /**
     * @param array  $path
     * @param array  $parsedBody
     * @param string $exceptionMessage
     *
     * @return void
     * @dataProvider provideDataForDetectingMissingPayloadPath
     */
    public function testCanDetectMissingPayloadPath(array $path, array $parsedBody, string $exceptionMessage): void
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
}
