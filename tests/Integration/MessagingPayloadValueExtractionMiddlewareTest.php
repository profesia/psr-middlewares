<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\Exception\BadConfigurationException;
use Profesia\Psr\Middleware\MessagingPayloadValueExtractionMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

class MessagingPayloadValueExtractionMiddlewareTest extends TestCase
{
    public function testCanDetectedInvalidPathConfig(): void
    {
        $this->expectExceptionObject(new BadConfigurationException('Path to payload value could not be empty'));
        new MessagingPayloadValueExtractionMiddleware(
            new Psr17Factory(),
            new NullLogger(),
            new NullServerVariablesStorage(),
            [],
            'keyToStore'
        );
    }

    public function provideDataForErrorStates(): array
    {
        return [
            [
                (new ServerRequest(
                    'GET',
                    'https://test1.sk',
                    [],
                    null,
                ))->withParsedBody(null),
                [
                    'testKey'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'No payload supplied',
                ],
            ],
            [
                (new ServerRequest(
                    'GET',
                    'https://test2.sk',
                    [],
                    null,
                ))->withParsedBody(new \stdClass()),
                [
                    'testKey'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'No payload supplied',
                ],
            ],
            [
                (new ServerRequest(
                    'GET',
                    'https://test3.sk',
                    [],
                    null,
                ))->withParsedBody(
                    [
                        'test1' => [
                            'test2' => [
                                'test3' => 'value'
                            ]
                        ]
                    ]
                ),
                [
                    'test1'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'Extracted value should be of a primitive type',
                ],
            ],
            [
                (new ServerRequest(
                    'GET',
                    'https://test4.sk',
                    [],
                    null,
                ))->withParsedBody(
                    [
                        'test1' => [
                            'test2' => [
                                'test3' => 'value'
                            ]
                        ]
                    ]
                ),
                [
                    'nonExistingKey'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [nonExistingKey] in the payload path: []',
                ],
            ],
            [
                (new ServerRequest(
                    'GET',
                    'https://test5.sk',
                    [],
                    null,
                ))->withParsedBody(
                    [
                        'test1' => [
                            'test2' => [
                                'test3' => 'value'
                            ]
                        ]
                    ]
                ),
                [
                    'test1',
                    'nonExistingKey'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [nonExistingKey] in the payload path: [test1]',
                ],
            ],
            [
                (new ServerRequest(
                    'GET',
                    'https://test6.sk',
                    [],
                    null,
                ))->withParsedBody(
                    [
                        'test1' => [
                            'test2' => [
                                'test3' => 'value'
                            ]
                        ]
                    ]
                ),
                [
                    'test1',
                    'test2',
                    'nonExistingKey'
                ],
                200,
                [
                    'status'  => 'Bad request',
                    'message' => 'Missing key: [nonExistingKey] in the payload path: [test1, test2]',
                ],
            ],
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $pathToPayloadValue
     * @param int                    $expectedStatusCode
     * @param array                  $expectedMessage
     *
     * @return void
     * @dataProvider provideDataForErrorStates
     */
    public function testErrorStates(
        ServerRequestInterface $request,
        array $pathToPayloadValue,
        int $expectedStatusCode,
        array $expectedMessage
    ): void {
        $factory    = new Psr17Factory();
        $middleware = new MessagingPayloadValueExtractionMiddleware(
            $factory,
            new NullLogger(),
            new NullServerVariablesStorage(),
            $pathToPayloadValue,
            'keyToStore'
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals($expectedMessage, $body);
    }

    public function testCanProcess(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new MessagingPayloadValueExtractionMiddleware(
            $factory,
            new NullLogger(),
            new NullServerVariablesStorage(),
            [
                'message',
                'attributes',
                'testKey',
            ],
            'keyToStore'
        );

        $request    = new ServerRequest(
            'GET',
            'https://test10.sk',
            [],
            null
        );
        $parsedBody = [
            'message' => [
                'attributes' => [
                    'testKey' => 'testValue',
                ],
            ],
        ];
        $request    = $request->withParsedBody(
            $parsedBody
        );
        $response   = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(
            [
                'headers'    => [
                    'Host' => [
                        'test10.sk',
                    ],
                ],
                'body'       => '',
                'parsedBody' => $parsedBody,
            ],
            $body
        );
    }
}
