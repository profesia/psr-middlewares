<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\MessagingJsonPayloadMiddleware;
use Profesia\Psr\Middleware\Test\Assets\TestRequestHandler;
use Psr\Log\NullLogger;

class MessagingJsonPayloadMiddlewareTest extends TestCase
{
    public function testCanDetectUnsupportedMethod(): void
    {
        $request          = new ServerRequest(
            'GET',
            'https://test1.com',
        );
        $factory          = new Psr17Factory();
        $logger           = new NullLogger();
        $contextHeaderKey = 'contextHeaderKey';
        $middleware       = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                'POST',
                'PUT',
            ],
            $contextHeaderKey
        );

        $handler = new TestRequestHandler(
            $factory
        );

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $content     = (string)$response->getBody();
        $decodedBody = json_decode($content, true);
        $this->assertEquals(
            [
                'headers'    => [
                    'Host' => [
                        'test1.com',
                    ],
                ],
                'body'       => '',
                'parsedBody' => null,
            ],
            $decodedBody
        );
    }

    public function testCanDetectApplicabilityDetection(): void
    {
        $testData         = [
            'testingCase' => 2,
        ];
        $request          = new ServerRequest(
            'POST',
            'https://test2.com',
            [
                'Content-Type' => 'application/xml',
            ],
            Stream::create(json_encode($testData))
        );
        $factory          = new Psr17Factory();
        $logger           = new NullLogger();
        $contextHeaderKey = 'contextHeaderKey';
        $middleware       = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                'POST',
                'PUT',
            ],
            $contextHeaderKey
        );

        $handler = new TestRequestHandler(
            $factory
        );

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $content     = (string)$response->getBody();
        $decodedBody = json_decode($content, true);
        $this->assertEquals(
            [
                'headers'    => [
                    'Host'         => [
                        'test2.com',
                    ],
                    'Content-Type' => [
                        'application/xml',
                    ],
                ],
                'body'       => json_encode($testData),
                'parsedBody' => null,
            ],
            $decodedBody
        );
    }

    public function testCanDetectInvalidJson(): void
    {
        $payload          = '{"testingCase":}';
        $request          = new ServerRequest(
            'POST',
            'https://test3.com',
            [
                'Content-Type' => 'application/json',
            ],
            Stream::create($payload)
        );
        $factory          = new Psr17Factory();
        $logger           = new NullLogger();
        $contextHeaderKey = 'contextHeaderKey';
        $middleware       = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                'POST',
                'PUT',
            ],
            $contextHeaderKey
        );

        $handler = new TestRequestHandler(
            $factory
        );

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $content     = (string)$response->getBody();
        $decodedBody = json_decode($content, true);

        $this->assertEquals(
            [
                'status'  => 'Unprocessable entity',
                'message' => 'Error while decoding payload. Cause: [Syntax error]',
            ],
            $decodedBody
        );
    }

    public function testCanProcess(): void
    {
        $testData         = [
            'testingCase' => 4,
            'data' => [
                'a',
                'b',
                'c'
            ]
        ];
        $request          = new ServerRequest(
            'POST',
            'https://test4.com',
            [
                'Content-Type' => 'application/json',
            ],
            Stream::create(json_encode($testData))
        );
        $factory          = new Psr17Factory();
        $logger           = new NullLogger();
        $contextHeaderKey = 'contextHeaderKey';
        $middleware       = new MessagingJsonPayloadMiddleware(
            $factory,
            $logger,
            [
                'POST',
                'PUT',
            ],
            $contextHeaderKey
        );

        $handler = new TestRequestHandler(
            $factory
        );

        $response = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $content     = (string)$response->getBody();
        $decodedBody = json_decode($content, true);
        $this->assertEquals(
            [
                'headers'    => [
                    'Host'         => [
                        'test4.com',
                    ],
                    'Content-Type' => [
                        'application/json',
                    ],
                ],
                'body'       => json_encode($testData),
                'parsedBody' => $testData,
            ],
            $decodedBody
        );
    }
}
