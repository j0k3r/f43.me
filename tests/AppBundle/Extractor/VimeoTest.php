<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Vimeo;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class VimeoTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['https://vimeo.com/116087204', true],
            ['http://vimeo.com/116087204', true],
            ['https://vimeo.com/channels/staffpicks/130365792', true],
            ['https://vimeo.com/groups/motion/videos/131034832', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $vimeo = new Vimeo();
        $this->assertSame($expected, $vimeo->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode(['title' => 'my title', 'description' => 'my description', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $vimeo = new Vimeo();
        $vimeo->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $vimeo->setLogger($logger);

        // first test fail because we didn't match an url, so VimeoUrl isn't defined
        $this->assertEmpty($vimeo->getContent());

        $vimeo->match('https://vimeo.com/groups/motion/videos/131034832');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vimeo->getContent());
        // this one will got an empty array
        $this->assertEmpty($vimeo->getContent());
        // this one will catch an exception
        $this->assertEmpty($vimeo->getContent());

        $this->assertTrue($logHandler->hasWarning('Vimeo extract failed for: https://vimeo.com/groups/motion/videos/131034832'), 'Warning message matched');
    }
}
