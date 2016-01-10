<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Vimeo;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class VimeoTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals($expected, $vimeo->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['title' => 'my title', 'description' => 'my description', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $vimeo = new Vimeo();
        $vimeo->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $vimeo->setLogger($logger);

        // first test fail because we didn't match an url, so VimeoUrl isn't defined
        $this->assertEmpty($vimeo->getContent());

        $vimeo->match('https://vimeo.com/groups/motion/videos/131034832');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vimeo->getContent());
        // this one will got an empty array
        $this->assertEmpty($vimeo->getContent());
        // this one will catch an exception
        $this->assertEmpty($vimeo->getContent());

        $this->assertTrue($logHandler->hasWarning('Vimeo extract failed for: https://vimeo.com/groups/motion/videos/131034832'), 'Warning message matched');
    }
}
