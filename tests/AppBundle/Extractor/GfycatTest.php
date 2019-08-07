<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Gfycat;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class GfycatTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['http://gfycat.com/RichPepperyFerret', true],
            ['https://gfycat.com/RichPepperyFerret', true],
            ['http://gfycat.com/NeatSpitefulCapeghostfrog', true],
            ['http://www.gfycat.com/NeatSpitefulCapeghostfrog', true],
            ['https://gfycat.com/gifs/detail/ConcernedBlackDipper', true],
            ['http://gfycat.com/', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $gfycat = new Gfycat();
        $this->assertSame($expected, $gfycat->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], json_encode(['gfyItem' => ['title' => 'my title', 'posterUrl' => 'http://0.0.0.0/img.gif', 'width' => 210, 'height' => 120]]))),
            (new Response(200, [], json_encode(''))),
            (new Response(400, [], json_encode('oops'))),
        ]);

        $gfycat = new Gfycat();
        $gfycat->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $gfycat->setLogger($logger);

        // first test fail because we didn't match an url, so GfycatId isn't defined
        $this->assertEmpty($gfycat->getContent());

        $gfycat->match('http://gfycat.com/RichPepperyFerret');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.gif"></p></div><div><iframe src="https://gfycat.com/ifr/RichPepperyFerret" frameborder="0" scrolling="no" allowfullscreen width="210" height="120"></iframe></div>', $gfycat->getContent());
        // this one will got an empty array
        $this->assertEmpty($gfycat->getContent());
        // this one will catch an exception
        $this->assertEmpty($gfycat->getContent());

        $this->assertTrue($logHandler->hasWarning('Gfycat extract failed for: RichPepperyFerret'), 'Warning message matched');
    }
}
