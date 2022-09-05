<?php

namespace App\Tests\Extractor;

use App\Extractor\Spotify;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class SpotifyTest extends AppTestCase
{
    public function dataMatch(): array
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
    public function testMatch(string $url, bool $expected): void
    {
        $spotify = new Spotify();
        $this->assertSame($expected, $spotify->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([
            new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>'])),
            new Response(200, [], (string) json_encode('')),
            new Response(400, [], (string) json_encode('oops')),
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
