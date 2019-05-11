<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Giphy;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class GiphyTest extends AppTestCase
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
        $client = self::getMockClient([
            (new Response(200, [], json_encode(['title' => 'my title', 'url' => 'http://0.0.0.0/img.jpg']))),
            (new Response(200, [], json_encode(''))),
            (new Response(400, [], json_encode('oops'))),
        ]);

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
