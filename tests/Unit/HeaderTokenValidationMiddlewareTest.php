<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Test\Unit;

use Mockery\MockInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Profesia\Psr\Middleware\HeaderTokenValidationMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HeaderTokenValidationMiddlewareTest extends MockeryTestCase
{
    public function testWillReturnUnauthorizedOnMissingToken(): void
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
                    401,
                    'Unauthorized'
                ]
            )->andReturn(
                $expectedResponse
            );


        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerName1',
            'token'
        );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    'headerName1',
                ]
            )
            ->andReturn(
                []
            );

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $response,
            $expectedResponse
        );
    }

    public function testWillReturnUnauthorizedOnTokenMismatch(): void
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
                    401,
                    'Unauthorized'
                ]
            )->andReturn(
                $expectedResponse
            );


        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerName2',
            'token'
        );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    'headerName2',
                ]
            )
            ->andReturn(
                [
                    'other-token'
                ]
            );

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $response,
            $expectedResponse
        );
    }

    public function testCanValidateToken(): void
    {
        /** @var ResponseInterface|MockInterface $expectedResponse */
        $expectedResponse = Mockery::mock(ResponseInterface::class);

        /** @var ResponseFactoryInterface|MockInterface $factory */
        $factory = Mockery::mock(ResponseFactoryInterface::class);


        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerName3',
            'token'
        );

        /** @var ServerRequestInterface|MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    'headerName3',
                ]
            )
            ->andReturn(
                [
                    0 => 'token'
                ]
            );

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn(
                $expectedResponse
            );

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(
            $response,
            $expectedResponse
        );
    }
}
