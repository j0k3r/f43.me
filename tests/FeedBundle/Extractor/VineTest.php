<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Vine;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class VineTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://vine.co/v/e7V1hLdF1bP', true),
            array('http://vine.co/v/e7V1hLdF1bP', true),
            array('https://vine.co', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $vine = new Vine();
        $this->assertEquals($expected, $vine->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $vine = new Vine();
        $vine->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $vine->setLogger($logger);

        // first test fail because we didn't match an url, so VineId isn't defined
        $this->assertEmpty($vine->getContent());

        $vine->match('https://vine.co/v/e7V1hLdF1bP');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vine->getContent());
        // this one will got an empty array
        $this->assertEmpty($vine->getContent());
        // this one will catch an exception
        $this->assertEmpty($vine->getContent());

        $this->assertTrue($logHandler->hasWarning('Vine extract failed for: e7V1hLdF1bP'), 'Warning message matched');
    }
}
