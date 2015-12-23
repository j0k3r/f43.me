<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Streamable;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class StreamableTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://streamable.com/7pfe', true),
            array('http://streamable.com/7pfe', true),
            array('https://www.streamable.com/7pfe', true),
            array('https://www.streamable.com', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $streamable = new Streamable();
        $this->assertEquals($expected, $streamable->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $streamable = new Streamable();
        $streamable->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $streamable->setLogger($logger);

        // first test fail because we didn't match an url, so StreamableUrl isn't defined
        $this->assertEmpty($streamable->getContent());

        $streamable->match('https://www.streamable.com/watch?v=UacN1xwVK2Y');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $streamable->getContent());
        // this one will got an empty array
        $this->assertEmpty($streamable->getContent());
        // this one will catch an exception
        $this->assertEmpty($streamable->getContent());

        $this->assertTrue($logHandler->hasWarning('Streamable extract failed for: https://www.streamable.com/watch?v=UacN1xwVK2Y'), 'Warning message matched');
    }
}
