<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Deviantart;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class DeviantartTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002', true),
            array('http://lndi.deviantart.com/art/Maybe-we-ll-get-luck-and-we-ll-both-live-again-494273522', true),
            array('http://fav.me/d7gab7p', true),
            array('http://sta.sh/06x5m3s9bms', true),

            array('http://www.deviantart.com/browse/all/', false),
            array('http://nixielupus.deviantart.com/', false),
            array('http://sta.sh/', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $deviantart = new Deviantart();
        $this->assertEquals($expected, $deviantart->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array(
                'url' => 'http://0.0.0.0/youpi.jpg',
                'title' => 'youpi',
                'author_url' => 'http://youpi.0.0.0.0',
                'author_name' => 'youpi',
                'category' => 'Pic > Landscape',
                'html' => '<iframe></iframe>',
            )))),
        ]);

        $client->getEmitter()->attach($mock);

        $deviantart = new Deviantart();
        $deviantart->setClient($client);

        // first test fail because we didn't match an url, so DeviantartId isn't defined
        $this->assertEmpty($deviantart->getContent());

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        $content = $deviantart->getContent();

        $this->assertContains('<img src="http://0.0.0.0/youpi.jpg" />', $content);
        $this->assertContains('<p>By <a href="http://youpi.0.0.0.0">@youpi</a></p>', $content);
        $this->assertContains('<iframe></iframe>', $content);
    }

    public function testContentWithException()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $deviantart = new Deviantart();
        $deviantart->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $deviantart->setLogger($logger);

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        // this one will catch an exception
        $this->assertEmpty($deviantart->getContent());

        $this->assertTrue($logHandler->hasWarning('Deviantart extract failed for: http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002'), 'Warning message matched');
    }
}
