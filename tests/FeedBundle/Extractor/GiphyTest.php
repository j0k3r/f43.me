<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Giphy;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class GiphyTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true),
            array('http://www.giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true),
            array('https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true),
            array('https://giphy.com/gifs/mlb-baseball-nlds-l2Sq2Ri3w1rmrOTHq', true),
            array('https://giphy.com/search/hello-kitty-stickers/', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $giphy = new Giphy();
        $this->assertEquals($expected, $giphy->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('title' => 'my title', 'image' => 'http://0.0.0.0/img.jpg')))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $giphy = new Giphy();
        $giphy->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $giphy->setLogger($logger);

        // first test fail because we didn't match an url, so GiphyUrl isn't defined
        $this->assertEmpty($giphy->getContent());

        $giphy->match('https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p></div>', $giphy->getContent());
        // this one will got an empty array
        $this->assertEmpty($giphy->getContent());
        // this one will catch an exception
        $this->assertEmpty($giphy->getContent());

        $this->assertTrue($logHandler->hasWarning('Giphy extract failed for: https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S'), 'Warning message matched');
    }
}
