<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Profesia\Psr\Middleware\JsonDecodingMiddleware;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonDecodingMiddlewareTest extends MockeryTestCase
{
    public function testWillPassRequestToNextHandlerOnEmptyBody(): void
    {
        /** @var ResponseFactoryInterface|MockInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        /** @var MockInterface|StreamInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream
            ->shouldReceive('getContents')
            ->once()
            ->andReturn(
                ''
            );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getBody')
            ->once()
            ->andReturn(
                $stream
            );

        /** @var ResponseInterface|MockInterface $expectedResponse */
        $expectedResponse = Mockery::mock(ResponseInterface::class);

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            )->andReturn(
                $expectedResponse
            );

        $actualResponse = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $expectedResponse,
            $actualResponse
        );
    }

    public function testWillPassRequestToNextHandlerOnMissingHeaderLine(): void
    {
        /** @var ResponseFactoryInterface|MockInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        /** @var MockInterface|StreamInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream
            ->shouldReceive('getContents')
            ->once()
            ->andReturn(
                '0123456789'
            );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getBody')
            ->once()
            ->andReturn(
                $stream
            );
        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )
            ->andReturn(
                'test'
            );

        /** @var ResponseInterface|MockInterface $expectedResponse */
        $expectedResponse = Mockery::mock(ResponseInterface::class);

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            )->andReturn(
                $expectedResponse
            );

        $actualResponse = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $expectedResponse,
            $actualResponse
        );
    }

    public function testWillDetectInvalidJson(): void
    {
        /** @var ResponseInterface|MockInterface $expectedResponse */
        $expectedResponse = Mockery::mock(ResponseInterface::class);

        /** @var ResponseFactoryInterface|MockInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);
        $factory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    400,
                    'Invalid JSON payload',
                ]
            )->andReturn(
                $expectedResponse
            );

        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $content = '{:';

        /** @var MockInterface|StreamInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream
            ->shouldReceive('getContents')
            ->once()
            ->andReturn(
                $content
            );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getBody')
            ->once()
            ->andReturn(
                $stream
            );

        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )
            ->andReturn(
                'application/json'
            );

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $actualResponse = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $expectedResponse,
            $actualResponse
        );
    }

    public function testWillDecodeJsonContent(): void
    {
        /** @var ResponseInterface|MockInterface $expectedResponse */
        $expectedResponse = Mockery::mock(ResponseInterface::class);

        /** @var ResponseFactoryInterface|MockInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $content = '{"a": 1}';

        /** @var MockInterface|StreamInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream
            ->shouldReceive('getContents')
            ->once()
            ->andReturn($content);

        $stream
            ->shouldReceive('isSeekable')
            ->once()
            ->andReturn(true);
        $stream
            ->shouldReceive('rewind')
            ->once();

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getBody')
            ->twice()
            ->andReturn(
                $stream
            );

        $request
            ->shouldReceive('getHeaderLine')
            ->once()
            ->withArgs(
                [
                    'Content-Type',
                ]
            )
            ->andReturn(
                'application/json'
            );
        $request
            ->shouldReceive('withParsedBody')
            ->once()
            ->withArgs(
                [
                    json_decode($content, true),
                ]
            )->andReturn(
                $request
            );
        $request
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                [
                    $stream,
                ]
            )->andReturn($request);

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request,
                ]
            )->andReturn(
                $expectedResponse
            );

        $actualResponse = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $expectedResponse,
            $actualResponse
        );
    }
}
