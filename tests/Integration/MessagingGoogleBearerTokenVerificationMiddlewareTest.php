<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Google\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Profesia\Psr\Middleware\MessagingGoogleBearerTokenVerificationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

class MessagingGoogleBearerTokenVerificationMiddlewareTest extends MockeryTestCase
{
    public function provideDataForVerificationTest(): array
    {
        $factory = new Psr17Factory();

        return [
            [
                new ServerRequest(
                    'POST',
                    'https://test1.uri',
                    [
                        'authorization' => ['test1 test2'],
                    ]
                ),
                $factory->createResponse(200, 'OK'),
                200,
                'OK',
                json_encode(
                    [
                        'headers'    => [
                            'Host'          => [
                                'test1.uri',
                            ],
                            'authorization' => [
                                'test1 test2',
                            ],
                        ],
                        'body'       => '',
                        'parsedBody' => null,
                    ]
                ),
            ],
            [
                new ServerRequest(
                    'POST',
                    'https://test2.uri',
                    [
                        'authorization' => ['test1'],
                    ]
                ),
                $factory->createResponse(200, 'OK'),
                200,
                'OK',
                json_encode(
                    [
                        'headers' => [
                            'Host'          => [
                                'test2.uri',
                            ]
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param int                    $expectedStatusCode
     * @param string                 $expectedReasonPhrase
     * @param string                 $expectedResponseBody
     *
     * @return void
     * @dataProvider provideDataForVerificationTest
     */
    public function testVerification(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $expectedStatusCode,
        string $expectedReasonPhrase,
        string $expectedResponseBody
    ): void {
        $factory = new Psr17Factory();

        /** @var MockInterface|Client $client */
        $client = Mockery::mock(Client::class);
        $decodedExpectedBody = json_decode($expectedResponseBody, true);
        $hasBody = array_key_exists('body', $decodedExpectedBody);
        if ($hasBody === true) {
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

        $middleware = new MessagingGoogleBearerTokenVerificationMiddleware(
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
        if ($hasBody === true) {
            $actualResponseBodyStream = $actualResponse->getBody();
            $actualResponseBodyStream->rewind();
            $this->assertEquals($expectedResponseBody, $actualResponseBodyStream->getContents());
        }
    }
}
