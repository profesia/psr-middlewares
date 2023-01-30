<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration;

use DateTimeImmutable;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Psr\Middleware\Extra\EmptyContextGenerator;

class EmptyContextGeneratorTest extends TestCase
{
    public function testCanGenerate(): void
    {
        $generator = new EmptyContextGenerator();
        $request   = new ServerRequest(
            'get',
            'https://test.sk',
            [
                'Content-Type' => [
                    'application/json',
                ],
            ],
            json_encode(
                [
                    'message' => [
                        'attributes' => [
                            'eventType'       => 'TestEvent',
                            'eventOccurredOn' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                            'correlationId'   => 'test',
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals(
            [],
            $generator->generate(
                $request
            )
        );
    }
}
