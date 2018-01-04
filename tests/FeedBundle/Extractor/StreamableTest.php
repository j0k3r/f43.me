<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Streamable;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class StreamableTest extends TestCase
{
    public function dataMatch()
    {
        return [
            ['https://streamable.com/7pfe', true],
            ['http://streamable.com/7pfe', true],
            ['https://www.streamable.com/7pfe', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $streamable = new Streamable();
        $this->assertSame($expected, $streamable->match($url));
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

        $streamable = new Streamable();
        $streamable->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $streamable->setLogger($logger);

        // first test fail because we didn't match an url, so StreamableUrl isn't defined
        $this->assertEmpty($streamable->getContent());

        $streamable->match('https://www.streamable.com/7pfe');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $streamable->getContent());
        // this one will got an empty array
        $this->assertEmpty($streamable->getContent());
        // this one will catch an exception
        $this->assertEmpty($streamable->getContent());

        $this->assertTrue($logHandler->hasWarning('Streamable extract failed for: https://www.streamable.com/7pfe'), 'Warning message matched');
    }
}
