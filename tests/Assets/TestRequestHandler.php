<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Assets;

use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestRequestHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $factory;

    public function __construct(ResponseFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->factory->createResponse(
            200,
            'OK'
        );

        $body = $request->getBody();
        $body->rewind();

        return $response
            ->withBody(
                Stream::create(
                    json_encode(
                        [
                            'headers'    => $request->getHeaders(),
                            'body'       => $body->getContents(),
                            'parsedBody' => $request->getParsedBody(),
                        ]
                    )
                )
            )
            ->withHeader('Content-Type', 'application/json');
    }
}
