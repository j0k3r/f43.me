<?php

namespace tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Instagram;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class InstagramTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://instagram.com/p/2N5UHfChAZ/', true],
            ['http://instagr.am/p/fA9uwTtkSN/', true],
            ['https://instagram.com/p/4FKIIdJ9LM/?taken-by=j0k', true],
            ['https://www.instagram.com/p/BAqirNbwEc0/', true],
            ['https://instagram.com', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
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
            new Response(200, [], Stream::factory(json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $instagram = new Instagram();
        $instagram->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
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
