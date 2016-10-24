<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Vidme;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class VidmeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://vid.me/WaJr', true),
            array('http://vid.me/e/WaJr', true),
            array('https://vid.me', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $vidme = new Vidme();
        $this->assertEquals($expected, $vidme->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('video' => array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'embed_url' => 'http://0.0.0.0/embed'))))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $vidme = new Vidme();
        $vidme->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $vidme->setLogger($logger);

        // first test fail because we didn't match an url, so VidmeId isn't defined
        $this->assertEmpty($vidme->getContent());

        $vidme->match('https://vid.me/WaJr');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe src="http://0.0.0.0/embed"></iframe></div>', $vidme->getContent());
        // this one will got an empty array
        $this->assertEmpty($vidme->getContent());
        // this one will catch an exception
        $this->assertEmpty($vidme->getContent());

        $this->assertTrue($logHandler->hasWarning('Vidme extract failed for: https://vid.me/WaJr'), 'Warning message matched');
    }
}
