<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Dailymotion;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class DailymotionTest extends AppTestCase
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
        $this->assertSame($expected, $dailymotion->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], json_encode(''))),
            (new Response(400, [], json_encode('oops'))),
        ]);

        $dailymotion = new Dailymotion();
        $dailymotion->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $dailymotion->setLogger($logger);

        // first test fail because we didn't match an url, so DailymotionUrl isn't defined
        $this->assertEmpty($dailymotion->getContent());

        $dailymotion->match('https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $dailymotion->getContent());
        // this one will got an empty array
        $this->assertEmpty($dailymotion->getContent());
        // this one will catch an exception
        $this->assertEmpty($dailymotion->getContent());

        $this->assertTrue($logHandler->hasWarning('Dailymotion extract failed for: https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel'), 'Warning message matched');
    }
}
