<?php

declare(strict_types=1);


namespace Profesia\Psr\Middleware\Test\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\JsonDecodingMiddleware;
use Profesia\Psr\Middleware\Test\Integration\Assets\TestRequestHandler;

class JsonDecodingMiddlewareTest extends TestCase
{
    public function testWillPassRequestToNextHandlerOnEmptyBody(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new JsonDecodingMiddleware(
            $factory
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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals(
            [
                'headers'    => [],
                'body'       => '',
                'parsedBody' => null,
            ],
            json_decode($body->getContents(), true)
        );
    }

    public function testWillPassRequestToNextHandlerOnMissingHeaderLine(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $request = new ServerRequest(
            'GET',
            'https:://test2.com',
            [],
            Stream::create('testing')
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals(
            [
                'headers'    => [],
                'body'       => 'testing',
                'parsedBody' => null,
            ],
            json_decode($body->getContents(), true)
        );
    }

    public function testWillDetectInvalidJson(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $stream = Stream::create('{:');
        $stream->rewind();
        $request = new ServerRequest(
            'GET',
            'https:://test3.com',
            [
                'Content-Type' => ['application/json'],
            ],
            $stream
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid JSON payload', $response->getReasonPhrase());
    }

    public function testWillDecodeJsonContent(): void
    {
        $factory    = new Psr17Factory();
        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $requestContent = ["a" => 1];
        $stream         = Stream::create(
            json_encode($requestContent)
        );
        $stream->rewind();
        $request = new ServerRequest(
            'GET',
            'https:://test4.com',
            [
                'Content-Type' => ['application/json'],
            ],
            $stream
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $responseBody->rewind();
        $responseContents = json_decode($responseBody->getContents(), true);
        $this->assertEquals($requestContent, $responseContents['parsedBody']);
        $this->assertEquals(json_encode($requestContent), $responseContents['body']);
        $this->assertEquals(
            ['Content-Type' => ['application/json']],
            $responseContents['headers']
        );
    }

    public function testWillPassOriginalBody()
    {
        $factory    = new Psr17Factory();
        $middleware = new JsonDecodingMiddleware(
            $factory
        );

        $requestContent = ["a" => 1];
        $stream         = Stream::create(
            json_encode($requestContent)
        );
        $stream->rewind();
        $request = new ServerRequest(
            'GET',
            'https:://test5.com',
            [
                'Content-Type' => ['application/json'],
            ],
            $stream
        );

        $response = $middleware->process(
            $request,
            new TestRequestHandler(
                $factory
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $responseBody->rewind();
        $responseContents = json_decode($responseBody->getContents(), true);
        $this->assertEquals(json_encode($requestContent), $responseContents['body']);
        $this->assertEquals(
            ['Content-Type' => ['application/json']],
            $responseContents['headers']
        );
    }
}
