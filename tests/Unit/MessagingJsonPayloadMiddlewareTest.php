<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Nyholm\Psr7\Stream;
use Profesia\Psr\Middleware\MessagingJsonPayloadMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class MessagingJsonPayloadMiddlewareTest extends MockeryTestCase
{
    public function testCanDetectUnsupportedMethod(): void
    {
        $headerContextKey = 'headerContextKey';
        $context          = [
            'test' => 1,
        ];

        $method = 'non-existing';

        /** @var MockINterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    "Unsupported method: [$method] supplied. Skipping decoding.",
                    $context,
                ]
            );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey,
                ]
            )->andReturn(
                $context
            );
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);

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

        $middleware = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                'method',
            ],
            $headerContextKey
        );

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanDetectApplicabilityDetection(): void
    {
        $headerContextKey = 'headerContextKey';
        $context          = [
            'test' => 1,
        ];

        $method      = 'method';
        $contentType = 'contentType';

        /** @var MockINterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $class   = MessagingJsonPayloadMiddleware::class;

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    "Content-Type: [$contentType] is not supported by: [$class]. Skipping decoding.",
                    $context,
                ]
            );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey,
                ]
            )->andReturn(
                $context
            );
        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )->andReturn(
                $contentType
            );
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);

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

        $middleware = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                $method,
            ],
            $headerContextKey
        );

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanDetectInvalidJson(): void
    {
        $headerContextKey = 'headerContextKey';
        $context          = [
            'test' => 1,
        ];

        $method      = 'method';
        $contentType = 'application/json';

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $body) {
                    $decodedContent = json_decode((string)$body, true);

                    return ($decodedContent ===
                        [
                            'status'  => 'Unprocessable entity',
                            'message' => 'Error while decoding payload. Cause: [Syntax error]',
                        ]
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
                    [
                        'application/json',
                    ],
                ]
            )->andReturn(
                $response
            );

        /** @var MockINterface|ResponseFactoryInterface $factory */
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
                    'Error while decoding payload. Cause: [Syntax error]',
                    $context,
                ]
            );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey,
                ]
            )->andReturn(
                $context
            );
        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )->andReturn(
                $contentType
            );
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);
        $request
            ->shouldReceive('getBody')
            ->once()
            ->andReturn(
                Stream::create('{"a":')
            );

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $middleware = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                $method,
            ],
            $headerContextKey
        );

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanProcess(): void
    {
        $headerContextKey = 'headerContextKey';
        $context          = [
            'test' => 1,
        ];

        $method          = 'method';
        $contentType     = 'application/json';
        $rawResponseBody = '{"a":1}';

        /** @var MockINterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        $class = MessagingJsonPayloadMiddleware::class;
        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    "Successfully parsed payload via: [$class]",
                    $context,
                ]
            );

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey,
                ]
            )->andReturn(
                $context
            );
        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )->andReturn(
                $contentType
            );
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);
        $request
            ->shouldReceive('getBody')
            ->once()
            ->andReturn(
                Stream::create($rawResponseBody)
            );
        $request
            ->shouldReceive('withParsedBody')
            ->once()
            ->withArgs(
                [
                    json_decode($rawResponseBody, true),
                ]
            )
            ->andReturn($request);

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

        $middleware = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                $method,
            ],
            $headerContextKey
        );

        $middleware->process(
            $request,
            $handler
        );
    }
}
