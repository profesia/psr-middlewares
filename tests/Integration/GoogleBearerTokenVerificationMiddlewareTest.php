<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Google\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Profesia\Psr\Middleware\GoogleBearerTokenVerificationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

class GoogleBearerTokenVerificationMiddlewareTest extends MockeryTestCase
{
    public function provideDataForVerificationTest(): array
    {
        $factory = new Psr17Factory();
        return [
            [
                new ServerRequest(
                    'POST',
                    'https://test.uri',
                    [
                        'authorization' => ['test1 test2']
                    ]
                ),
                $factory->createResponse(200, 'OK'),
                200,
                'OK',
            ],
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param int                    $expectedStatusCode
     * @param string                 $expectedReasonPhrase
     *
     * @return void
     * @dataProvider provideDataForVerificationTest
     */
    public function testVerification(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $expectedStatusCode,
        string $expectedReasonPhrase
    ): void {
        $factory = new Psr17Factory();

        /** @var MockInterface|Client $client */
        $client = Mockery::mock(Client::class);
        if ($expectedReasonPhrase === 'OK') {
            $header      = implode(' ', $request->getHeader('authorization'));
            $headerParts = explode(' ', $header);

            $client
                ->shouldReceive('verifyIdToken')
                ->once()
                ->withArgs(
                    [
                        $headerParts[1],
                    ]
                )->andReturn([]);
        }

        $middleware = new GoogleBearerTokenVerificationMiddleware(
            $factory,
            $client,
            new NullLogger(),
            'authorization'
        );

        $actualResponse = $middleware->process(
            $request,
            new TestRequestHandler(
                new Psr17Factory()
            )
        );

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertEquals($expectedReasonPhrase, $response->getReasonPhrase());
    }
}
