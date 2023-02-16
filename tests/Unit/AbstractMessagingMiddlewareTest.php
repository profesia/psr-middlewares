<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\Psr\Middleware\Test\Assets\NullContextGeneratingMessagingMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AbstractMessagingMiddlewareTest extends MockeryTestCase
{
    public function testCanHandleContextGeneration(): void
    {
        $headerContextKey = 'key';

        /** @var MockInterface|ResponseFactoryInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    'Testing',
                    [
                        'test1' => 'value1',
                        'test2' => 'value2',
                        'value3',
                    ],
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
                [
                    'test1=value1',
                    'test2=value2',
                    'value3',
                ]
            );

        $middleware = new NullContextGeneratingMessagingMiddleware(
            $factory,
            $logger,
            $headerContextKey
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
