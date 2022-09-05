<?php

namespace App\Tests\Extractor;

use App\Extractor\Vine;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class VineTest extends AppTestCase
{
    public function dataMatch(): array
    {
        return [
            ['https://vine.co/v/e7V1hLdF1bP', true],
            ['http://vine.co/v/e7V1hLdF1bP', true],
            ['https://vine.co', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch(string $url, bool $expected): void
    {
        $vine = new Vine();
        $this->assertSame($expected, $vine->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([
            new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>'])),
            new Response(200, [], (string) json_encode('')),
            new Response(400, [], (string) json_encode('oops')),
        ]);

        $vine = new Vine();
        $vine->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $vine->setLogger($logger);

        // first test fail because we didn't match an url, so VineId isn't defined
        $this->assertEmpty($vine->getContent());

        $vine->match('https://vine.co/v/e7V1hLdF1bP');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vine->getContent());
        // this one will got an empty array
        $this->assertEmpty($vine->getContent());
        // this one will catch an exception
        $this->assertEmpty($vine->getContent());

        $this->assertTrue($logHandler->hasWarning('Vine extract failed for: e7V1hLdF1bP'), 'Warning message matched');
    }
}
