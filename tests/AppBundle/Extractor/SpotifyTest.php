<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Spotify;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class SpotifyTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['http://open.spotify.com/track/298gs9ATwr2rD9tGYJKlQR', true],
            ['https://open.spotify.com/track/298gs9ATwr2rD9tGYJKlQR', true],
            ['https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz', true],
            ['https://play.spotify.com/album/6yGp5e6Puhx155c8dQ8e6P', true],
            ['https://play.spotify.com/track/2wIC3jqtTK78zQMdj1DRLu', true],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $spotify = new Spotify();
        $this->assertSame($expected, $spotify->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], json_encode(''))),
            (new Response(400, [], json_encode('oops'))),
        ]);

        $spotify = new Spotify();
        $spotify->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $spotify->setLogger($logger);

        // first test fail because we didn't match an url, so SpotifyUrl isn't defined
        $this->assertEmpty($spotify->getContent());

        $spotify->match('https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $spotify->getContent());
        // this one will got an empty array
        $this->assertEmpty($spotify->getContent());
        // this one will catch an exception
        $this->assertEmpty($spotify->getContent());

        $this->assertTrue($logHandler->hasWarning('Spotify extract failed for: https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz'), 'Warning message matched');
    }
}
