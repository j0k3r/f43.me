<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Flickr;
use GuzzleHttp\Exception\RequestException;

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
            array('http://farm6.static.flickr.com/5581/1500096710_8eb7552825_n.jpg', false),
            array('https://www.flickr.com/photos/europeanspaceagency/15739982196/in/set-72157638315605535', true),

            # photo set
            array('https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/', true),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $flickr = new Flickr($guzzle, 'apikey');
        $this->assertEquals($expected, $flickr->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSinglePhoto()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('stat' => 'ok', 'sizes' => array('size' => array(
                    array('label' => 'Medium', 'source' => 'https://0.0.0.0/medium.jpg'),
                    array('label' => 'Large', 'source' => 'https://0.0.0.0/large.jpg'),
                )))),
                $this->returnValue(array()),
                $this->throwException(new RequestException('oops', $request))
            ));

        $flickr = new Flickr($guzzle, 'apikey');

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('http://www.flickr.com/photos/palnick/15000967102/');

        // consecutive calls
        $this->assertEquals('<img src="https://0.0.0.0/large.jpg" />', $flickr->getContent());
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testPhotoSet()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('stat' => 'ok', 'photoset' => array('photo' => array(
                    array('title' => 'Super title', 'url_l' => 'https://0.0.0.0/medium.jpg'),
                    array('title' => 'Ugly title', 'url_o' => 'https://0.0.0.0/large.jpg'),
                )))),
                $this->returnValue(array()),
                $this->throwException(new RequestException('oops', $request))
            ));

        $flickr = new Flickr($guzzle, 'apikey');

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/');

        // consecutive calls
        $this->assertContains('<div><p>Super title</p><img src="https://0.0.0.0/medium.jpg" /></div>', $flickr->getContent());
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());
    }
}
