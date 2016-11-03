<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Dailymotion;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class DailymotionTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['http://dai.ly/xockol', true],
            ['http://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['http://dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $dailymotion = new Dailymotion();
        $this->assertEquals($expected, $dailymotion->match($url));
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

        $dailymotion = new Dailymotion();
        $dailymotion->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $dailymotion->setLogger($logger);

        // first test fail because we didn't match an url, so DailymotionUrl isn't defined
        $this->assertEmpty($dailymotion->getContent());

        $dailymotion->match('https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $dailymotion->getContent());
        // this one will got an empty array
        $this->assertEmpty($dailymotion->getContent());
        // this one will catch an exception
        $this->assertEmpty($dailymotion->getContent());

        $this->assertTrue($logHandler->hasWarning('Dailymotion extract failed for: https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel'), 'Warning message matched');
    }
}
