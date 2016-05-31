<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Instagram;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class InstagramTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://instagram.com/p/2N5UHfChAZ/', true),
            array('http://instagr.am/p/fA9uwTtkSN/', true),
            array('https://instagram.com/p/4FKIIdJ9LM/?taken-by=j0k', true),
            array('https://www.instagram.com/p/BAqirNbwEc0/', true),
            array('https://instagram.com', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $instagram = new Instagram();
        $this->assertEquals($expected, $instagram->match($url));
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

        $instagram = new Instagram();
        $instagram->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $instagram->setLogger($logger);

        // first test fail because we didn't match an url, so InstagramId isn't defined
        $this->assertEmpty($instagram->getContent());

        $instagram->match('https://instagram.com/p/2N5UHfChAZ/');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $instagram->getContent());
        // this one will got an empty array
        $this->assertEmpty($instagram->getContent());
        // this one will catch an exception
        $this->assertEmpty($instagram->getContent());

        $this->assertTrue($logHandler->hasWarning('Instagram extract failed for: https://instagram.com/p/2N5UHfChAZ/'), 'Warning message matched');
    }
}
