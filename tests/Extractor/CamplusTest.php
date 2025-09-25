<?php

namespace App\Tests\Extractor;

use App\Extractor\Camplus;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;

class CamplusTest extends AppTestCase
{
    public static function dataMatch(): array
    {
        return [
            ['http://campl.us/rL9Q', true],
            ['http://campl.us/jQKwkTKxLHG', true],
            ['https://campl.us/rL9Q', true],
            ['https://campl.us/hvGw', true],
            ['http://campl.us/ozu1', true],
            ['http://pics.campl.us/f/6/6283.e61ef28b1535e624f30e4ef96fcd3f52.jpg', false],
            ['http://github.com/symfony/symfony', false],
            ['http://user@:80', false],
        ];
    }

    #[DataProvider('dataMatch')]
    public function testMatch(string $url, bool $expected): void
    {
        $camplus = new Camplus();
        $this->assertSame($expected, $camplus->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([new Response(200, ['content-type' => 'application/json'], (string) json_encode([
            'page' => ['tweet' => [
                'id' => '123',
                'username' => 'j0k',
                'realname' => 'j0k',
                'text' => 'yay',
            ]], 'pictures' => [[
                '480px' => 'http://0.0.0.0/youpi.jpg',
            ]],
        ]))]);

        $camplus = new Camplus();
        $camplus->setClient($client);

        // first test fail because we didn't match an url, so camplusId isn't defined
        $this->assertEmpty($camplus->getContent());

        $camplus->match('http://campl.us/rL9Q');

        $content = (string) $camplus->getContent();

        $this->assertStringContainsString('<h2>Photo from j0k</h2>', $content);
        $this->assertStringContainsString('<p><img src="http://0.0.0.0/youpi.jpg" /></p>', $content);
    }

    public function testContentWithException(): void
    {
        $client = self::getMockClient([new Response(400, ['content-type' => 'application/json'], (string) json_encode('oops'))]);

        $camplus = new Camplus();
        $camplus->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $camplus->setLogger($logger);

        $camplus->match('http://campl.us/rL9Q');

        // this one will catch an exception
        $this->assertEmpty($camplus->getContent());

        $this->assertTrue($logHandler->hasWarning('Camplus extract failed for: rL9Q'), 'Warning message matched');
    }
}
