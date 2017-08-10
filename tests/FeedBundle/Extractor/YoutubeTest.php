<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Youtube;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class YoutubeTest extends \PHPUnit_Framework_TestCase
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
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

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
