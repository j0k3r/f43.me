<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Gfycat;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class GfycatTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://gfycat.com/SingleUntriedBudgie', true),
            array('https://gfycat.com/SingleUntriedBudgie', true),
            array('http://gfycat.com/NeatSpitefulCapeghostfrog', true),
            array('http://www.gfycat.com/NeatSpitefulCapeghostfrog', true),
            array('http://gfycat.com/', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $gfycat = new Gfycat();
        $this->assertEquals($expected, $gfycat->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('gfyItem' => array('title' => 'my title', 'gifUrl' => 'http://0.0.0.0/img.gif'))))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $gfycat = new Gfycat();
        $gfycat->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $gfycat->setLogger($logger);

        // first test fail because we didn't match an url, so GfycatId isn't defined
        $this->assertEmpty($gfycat->getContent());

        $gfycat->match('http://gfycat.com/SingleUntriedBudgie');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.gif"></p></div>', $gfycat->getContent());
        // this one will got an empty array
        $this->assertEmpty($gfycat->getContent());
        // this one will catch an exception
        $this->assertEmpty($gfycat->getContent());

        $this->assertTrue($logHandler->hasWarning('Gfycat extract failed for: SingleUntriedBudgie'), 'Warning message matched');
    }
}
