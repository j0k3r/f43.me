<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Spotify;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class SpotifyTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://open.spotify.com/track/298gs9ATwr2rD9tGYJKlQR', true),
            array('https://open.spotify.com/track/298gs9ATwr2rD9tGYJKlQR', true),
            array('https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz', true),
            array('https://play.spotify.com/album/6yGp5e6Puhx155c8dQ8e6P', true),
            array('https://play.spotify.com/track/2wIC3jqtTK78zQMdj1DRLu', true),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $spotify = new Spotify();
        $this->assertEquals($expected, $spotify->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $spotify = new Spotify();
        $spotify->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $spotify->setLogger($logger);

        // first test fail because we didn't match an url, so SpotifyUrl isn't defined
        $this->assertEmpty($spotify->getContent());

        $spotify->match('https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $spotify->getContent());
        // this one will got an empty array
        $this->assertEmpty($spotify->getContent());
        // this one will catch an exception
        $this->assertEmpty($spotify->getContent());

        $this->assertTrue($logHandler->hasWarning('Spotify extract failed for: https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz'), 'Warning message matched');
    }
}
