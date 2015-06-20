<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Spotify;
use Guzzle\Http\Exception\RequestException;

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
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $spotify = new Spotify($guzzle);
        $this->assertEquals($expected, $spotify->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContent()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException())
            ));

        $spotify = new Spotify($guzzle);

        // first test fail because we didn't match an url, so SpotifyUrl isn't defined
        $this->assertEmpty($spotify->getContent());

        $spotify->match('https://play.spotify.com/artist/4njdEjTnLfcGImKZu1iSrz');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $spotify->getContent());
        // this one will got an empty array
        $this->assertEmpty($spotify->getContent());
        // this one will catch an exception
        $this->assertEmpty($spotify->getContent());
    }
}
