<?php

namespace App\Tests\Extractor;

use App\Extractor\Soundcloud;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class SoundcloudTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower', true],
            ['http://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower#t=0:02', true],
            ['https://soundcloud.com/birdfeeder', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $soundCloud = new Soundcloud();
        $this->assertSame($expected, $soundCloud->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode(['title' => 'my title', 'description' => 'my description', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $soundCloud = new Soundcloud();
        $soundCloud->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $soundCloud->setLogger($logger);

        // first test fail because we didn't match an url, so SoundcloudUrl isn't defined
        $this->assertEmpty($soundCloud->getContent());

        $soundCloud->match('https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $soundCloud->getContent());
        // this one will got an empty array
        $this->assertEmpty($soundCloud->getContent());
        // this one will catch an exception
        $this->assertEmpty($soundCloud->getContent());

        $this->assertTrue($logHandler->hasWarning('Soundcloud extract failed for: https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower'), 'Warning message matched');
    }
}
