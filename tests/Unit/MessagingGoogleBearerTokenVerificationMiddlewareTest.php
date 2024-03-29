<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Unit;

use Google\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Profesia\Psr\Middleware\MessagingGoogleBearerTokenVerificationMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class MessageGoogleBearerTokenVerificationMiddlewareTest extends MockeryTestCase
{
    public function provideDataForTokenVerification(): array
    {
        return [
            [
                false,
            ],
            [
                [],
            ],
        ];
    }

    /**
     * @param mixed $verifyTokenOutput
     *
     * @return void
     * @dataProvider provideDataForTokenVerification
     */
    public function testCanVerifyToken(mixed $verifyTokenOutput): void
    {
        $headerName = 'test';
        $rawBearer  = ['par1 part2'];
        $headerContextKey = 'headerContextKey';
        $context = [
            'value=1'
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerName,
                ]
            )->andReturn(
                $rawBearer
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey
                ]
            )
            ->andReturn(
                $context
            );

        /** @var MockInterface|Client $client */
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('verifyIdToken')
            ->once()
            ->withArgs(
                [
                    'part2',
                ]
            )
            ->andReturn($verifyTokenOutput);

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        if ($verifyTokenOutput !== false) {
            $handler
                ->shouldReceive('handle')
                ->once()
                ->withArgs(
                    [
                        $request,
                    ]
                )->andReturn();
        }

        /** @var ResponseFactoryInterface|MockInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
        if ($verifyTokenOutput === false) {
            /** @var MockInterface|ResponseInterface $response */
            $response = Mockery::mock(ResponseInterface::class);

            $responseFactory
                ->shouldReceive('createResponse')
                ->once()
                ->withArgs(
                    [
                        200,
                        'OK',
                    ]
                )
                ->andReturn($response);

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
                )
                ->andReturn($response);

            $response
                ->shouldReceive('withBody')
                ->once()
                ->withArgs(
                    function (StreamInterface $stream) {
                        $stream->rewind();
                        $contents = $stream->getContents();

                        return ($contents === json_encode(
                                ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
                            )
                        );
                    }
                )->andReturn($response);
        }

        $verifyTokenString = ($verifyTokenOutput === false) ? 'false' : 'true';
        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info')
            ->once()
            ->withArgs(
                [
                    "Verification of token done with output: [{$verifyTokenString}]",
                    [
                        'value' => '1'
                    ],
                ]
            );

        $middleware = new MessagingGoogleBearerTokenVerificationMiddleware(
            $responseFactory,
            $client,
            $logger,
            $headerName,
            'headerContextKey'
        );

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanDetectInvalidToken(): void
    {
        $headerName = 'test';
        $rawBearer  = ['part1'];
        $headerContextKey = 'headerContextKey';
        $context = [
            'value=1'
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerName,
                ]
            )->andReturn(
                $rawBearer
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey
                ]
            )
            ->andReturn(
                $context
            );

        /** @var MockInterface|Client $client */
        $client = Mockery::mock(Client::class);
        $client
            ->shouldNotReceive('verifyIdToken');

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldNotReceive('handle');

        /** @var ResponseFactoryInterface|MockInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $responseFactory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    200,
                    'OK',
                ]
            )
            ->andReturn($response);

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
            )
            ->andReturn($response);

        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $stream) {
                    $stream->rewind();
                    $contents = $stream->getContents();

                    return ($contents === json_encode(
                            ['status' => 'Unauthorized', 'message' => 'Incorrect ID token']
                        )
                    );
                }
            )->andReturn($response);

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(
                [
                    'Bearer token has invalid format - it should contains two strings separated by a blank space',
                    [
                        'value' => '1'
                    ],
                ]
            );

        $middleware = new MessagingGoogleBearerTokenVerificationMiddleware(
            $responseFactory,
            $client,
            $logger,
            $headerName,
            'headerContextKey'
        );

        $middleware->process(
            $request,
            $handler
        );
    }

    public function testCanHandleExceptionDuringVerification(): void
    {
        $headerName = 'test';
        $rawBearer  = ['part1 part2'];
        $headerContextKey = 'headerContextKey';
        $context = [
            'value=1'
        ];

        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerName,
                ]
            )->andReturn(
                $rawBearer
            );
        $request
            ->shouldReceive('getHeader')
            ->once()
            ->withArgs(
                [
                    $headerContextKey
                ]
            )
            ->andReturn(
                $context
            );

        $message   = 'Error message';
        $exception = new UnexpectedValueException($message);
        /** @var MockInterface|Client $client */
        $client = Mockery::mock(Client::class);
        $client
            ->shouldReceive('verifyIdToken')
            ->once()
            ->withArgs(
                [
                    'part2',
                ]
            )
            ->andThrow(
                $exception
            );

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldNotReceive('handle');

        /** @var ResponseFactoryInterface|MockInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $responseFactory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    200,
                    'OK',
                ]
            )
            ->andReturn($response);

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
            )
            ->andReturn($response);

        $response
            ->shouldReceive('withBody')
            ->once()
            ->withArgs(
                function (StreamInterface $stream) use ($message) {
                    $contents = (string)$stream;

                    return ($contents === json_encode(
                            ['status' => 'Unauthorized', 'message' => "An error during verification of the token occurred. Cause: [{$message}]"]
                        )
                    );
                }
            )->andReturn($response);

        $context = [
            'value=1',
        ];

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(
                [
                    "An error during verification of the token occurred. Cause: [{$message}]",
                    [
                        'value' => '1'
                    ],
                ]
            );

        $middleware = new MessagingGoogleBearerTokenVerificationMiddleware(
            $responseFactory,
            $client,
            $logger,
            $headerName,
            'headerContextKey'
        );

        $middleware->process(
            $request,
            $handler
        );
    }
}
