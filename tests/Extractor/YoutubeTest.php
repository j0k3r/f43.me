<?php

namespace App\Tests\Extractor;

use App\Extractor\Youtube;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class YoutubeTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['https://www.youtube.com/watch?v=UacN1xwVK2Y', true],
            ['http://youtube.com/watch?v=UacN1xwVK2Y', true],
            ['https://youtu.be/UacN1xwVK2Y', true],
            ['http://youtu.be/UacN1xwVK2Y?t=1s', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $youtube = new Youtube();
        $this->assertSame($expected, $youtube->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $youtube = new Youtube();
        $youtube->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $youtube->setLogger($logger);

        // first test fail because we didn't match an url, so YoutubeUrl isn't defined
        $this->assertEmpty($youtube->getContent());

        $youtube->match('https://www.youtube.com/watch?v=UacN1xwVK2Y');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $youtube->getContent());
        // this one will got an empty array
        $this->assertEmpty($youtube->getContent());
        // this one will catch an exception
        $this->assertEmpty($youtube->getContent());

        $this->assertTrue($logHandler->hasWarning('Youtube extract failed for: https://www.youtube.com/watch?v=UacN1xwVK2Y'), 'Warning message matched');
    }
}
