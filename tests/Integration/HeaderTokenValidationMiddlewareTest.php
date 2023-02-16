<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\HeaderTokenValidationMiddleware;
use Profesia\Psr\Middleware\Test\Assets\TestRequestHandler;

class HeaderTokenValidationMiddlewareTest extends TestCase
{
    public function testWillReturnUnauthorizedOnMissingToken(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerLine1',
            'token'
        );

        $request = new ServerRequest(
            'GET',
            'https:://test1.com'
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }

    public function testWillPassRequestToNextHandlerOnMissingHeaderLine(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerLine2',
            'token'
        );

        $request = new ServerRequest(
            'GET',
            'https:://test2.com',
            []
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }

    public function testWillPassRequestToNextHandlerOnTokenMismatch(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            'headerLine3',
            'token'
        );

        $request = new ServerRequest(
            'GET',
            'https:://test3.com',
            [
                'headerLine3' => 'other-token',
            ]
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getReasonPhrase());
    }

    public function provideDataForValidation(): array
    {
        return [
            [
                [
                    'uri'     => 'https:://test4.com',
                    'headerName' => 'X-Api-Token',
                    'headers' => [
                        'X-Api-Token' => 'token',
                    ],
                    'asserts' => [
                        'getStatusCode'   => 200,
                        'getReasonPhrase' => 'OK',
                    ],
                    'body'    => [
                        'headers'    => [
                            'X-Api-Token' => ['token'],
                        ],
                        'body'       => '',
                        'parsedBody' => null,
                    ],
                ],
            ],
            [
                [
                    'uri'     => 'https:://test5.com',
                    'headerName' => 'X-Api-Token',
                    'headers' => [
                        'X-Api-Token' => ['token'],
                    ],
                    'asserts' => [
                        'getStatusCode'   => 200,
                        'getReasonPhrase' => 'OK',
                    ],
                    'body'    => [
                        'headers'    => [
                            'X-Api-Token' => ['token'],
                        ],
                        'body'       => '',
                        'parsedBody' => null,
                    ],
                ],
            ]
        ];
    }

    /**
     * @param array $config
     *
     * @dataProvider provideDataForValidation
     *
     * @return void
     */
    public function testCanValidateToken(array $config): void
    {
        $factory    = new Psr17Factory();
        $middleware = new HeaderTokenValidationMiddleware(
            $factory,
            $config['headerName'],
            'token'
        );

        $request = new ServerRequest(
            'GET',
            $config['uri'],
            $config['headers']
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        foreach ($config['asserts'] as $method => $value) {
            $this->assertEquals($value, $response->{$method}());
        }

        $body = $response->getBody();
        $body->rewind();
        $parsedBody = json_decode($body->getContents(), true);

        $this->assertEquals($config['body'], $parsedBody);
    }
}
