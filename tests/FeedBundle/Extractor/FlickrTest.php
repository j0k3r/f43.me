<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Flickr;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class FlickrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            # single photo
            array('https://www.flickr.com/photos/palnick/15000967101/in/photostream/lightbox/', true),
            array('http://www.flickr.com/photos/palnick/15000967102/', true),
            array('https://farm6.staticflickr.com/5581/15000967103_8eb7552825_n.jpg', true),
            array('http://farm6.static.flickr.com/5581/15000967104_8eb7552825_n.jpg', true),
            array('http://farm6.static.flicker.com/5581/15000967104_8eb7552825_n.jpg', false),
            array('http://farm6.static.flickr.com/5581/1500096710_8eb7552825_n.jpg', true),
            array('https://www.flickr.com/photos/europeanspaceagency/15739982196/in/set-72157638315605535', true),
            array('https://www.flickr.com/photos/dfmagazine/8286098812/', true),

            # photo set
            array('https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/', true),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $flickr = new Flickr('apikey');
        $this->assertEquals($expected, $flickr->match($url));
    }

    public function testSinglePhoto()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('stat' => 'ok', 'sizes' => array('size' => array(
                array('label' => 'Medium', 'source' => 'https://0.0.0.0/medium.jpg'),
                array('label' => 'Large', 'source' => 'https://0.0.0.0/large.jpg'),
            )))))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $flickr = new Flickr('apikey');
        $flickr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $flickr->setLogger($logger);

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('http://www.flickr.com/photos/palnick/15000967102/');

        // consecutive calls
        $this->assertEquals('<img src="https://0.0.0.0/large.jpg" />', $flickr->getContent());
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());

        $this->assertTrue($logHandler->hasWarning('Flickr extract failed for: 15000967102'), 'Warning message matched');
    }

    public function testPhotoSet()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('stat' => 'ok', 'photoset' => array('photo' => array(
                array('title' => 'Super title', 'url_l' => 'https://0.0.0.0/medium.jpg'),
                array('title' => 'Ugly title', 'url_o' => 'https://0.0.0.0/large.jpg'),
            )))))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $flickr = new Flickr('apikey');
        $flickr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $flickr->setLogger($logger);

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/');

        // consecutive calls
        $this->assertContains('<div><p>Super title</p><img src="https://0.0.0.0/medium.jpg" /></div>', $flickr->getContent());
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());

        $this->assertTrue($logHandler->hasWarning('Flickr extract failed for: 72157638315605535'), 'Warning message matched');
    }
}
