<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Giphy;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class GiphyTest extends TestCase
{
    public function dataMatch()
    {
        return [
            ['http://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true],
            ['http://www.giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true],
            ['https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S', true],
            ['https://giphy.com/gifs/mlb-baseball-nlds-l2Sq2Ri3w1rmrOTHq', true],
            ['https://giphy.com/search/hello-kitty-stickers/', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $giphy = new Giphy();
        $this->assertSame($expected, $giphy->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['title' => 'my title', 'url' => 'http://0.0.0.0/img.jpg']))),
            new Response(200, [], Stream::factory(json_encode(''))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $giphy = new Giphy();
        $giphy->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $giphy->setLogger($logger);

        // first test fail because we didn't match an url, so GiphyUrl isn't defined
        $this->assertEmpty($giphy->getContent());

        $giphy->match('https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p></div>', $giphy->getContent());
        // this one will got an empty array
        $this->assertEmpty($giphy->getContent());
        // this one will catch an exception
        $this->assertEmpty($giphy->getContent());

        $this->assertTrue($logHandler->hasWarning('Giphy extract failed for: https://giphy.com/gifs/linarf-l2SpOiTglzlu7yI3S'), 'Warning message matched');
    }
}
