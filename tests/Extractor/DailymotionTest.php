<?php

namespace App\Tests\Extractor;

use App\Extractor\Dailymotion;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class DailymotionTest extends AppTestCase
{
    public function dataMatch(): array
    {
        return [
            ['http://dai.ly/xockol', true],
            ['http://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['http://dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch(string $url, bool $expected): void
    {
        $dailymotion = new Dailymotion();
        $this->assertSame($expected, $dailymotion->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([
            new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>'])),
            new Response(200, [], (string) json_encode('')),
            new Response(400, [], (string) json_encode('oops')),
        ]);

        $dailymotion = new Dailymotion();
        $dailymotion->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $dailymotion->setLogger($logger);

        // first test fail because we didn't match an url, so DailymotionUrl isn't defined
        $this->assertEmpty($dailymotion->getContent());

        $dailymotion->match('https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $dailymotion->getContent());
        // this one will got an empty array
        $this->assertEmpty($dailymotion->getContent());
        // this one will catch an exception
        $this->assertEmpty($dailymotion->getContent());

        $this->assertTrue($logHandler->hasWarning('Dailymotion extract failed for: https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel'), 'Warning message matched');
    }
}
