<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\MessagingGoogleBearerTokenVerificationMiddleware;
use Profesia\Psr\Middleware\Test\Integration\Assets\NullClient;
use Profesia\Psr\Middleware\Test\Integration\Assets\TestRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

class MessagingGoogleBearerTokenVerificationMiddlewareTest extends TestCase
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
                            'Host' => [
                                'test2.uri',
                            ],
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

        $decodedExpectedBody = json_decode($expectedResponseBody, true);
        $hasBody             = array_key_exists('body', $decodedExpectedBody);

        $middleware = new MessagingGoogleBearerTokenVerificationMiddleware(
            $factory,
            new NullClient(),
            new NullLogger(),
            'authorization',
            'headerContextKey'
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

    public function provideDataForErrorStatesTest(): array
    {
        $factory = new Psr17Factory();

        return [
            (new ServerRequest(
                'POST',
                'https://test3.uri',
                [
                    'authorization' => ['test1 test2'],
                ]
            ))->withBody(
                Stream::create(
                    json_encode(
                        [
                            'message' => [
                                'attributes' => [

                                ]
                            ]
                        ]
                    )
                )
            ),
            $factory->createResponse(200, 'OK'),
            200,
            'OK',
            json_encode(
                [
                    'headers'    => [
                        'Host'          => [
                            'test3.uri',
                        ],
                        'authorization' => [
                            'test1 test2',
                        ],
                    ],
                    'body'       => '',
                    'parsedBody' => null,
                ]
            ),
        ];
    }
}
