<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Imgur;
use Guzzle\Http\Exception\RequestException;

class ImgurTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://i.imgur.com/IoKwI7E.jpg', false),
            array('http://i.imgur.com/IoKwI7E', false),
            array('https://i.imgur.com/IoKwI7E.jpg', false),
            array('https://i.imgur.com/IoKwI7E', false),
            array('http://imgur.com/IoKwI7E', true),
            array('https://imgur.com/IoKwI7E', true),
            array('https://imgur.com/a/dLaMy', true),
            array('https://imgur.com/gallery/dLaMy', true),
            array('https://imgur.com/duziauziaozaoLaMy', false),
            array('https://imgur.com/Ay', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $imgur = new Imgur($imgurClient);
        $this->assertEquals($expected, $imgur->match($url));
    }

    public function testContentImage()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $apiImage = $this->getMockBuilder('Imgur\Api\Image')
            ->disableOriginalConstructor()
            ->getMock();

        $image = $this->getMockBuilder('Imgur\Api\Model\Image')
            ->disableOriginalConstructor()
            ->getMock();

        $image->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('title'));
        $image->expects($this->any())
            ->method('getDescription')
            ->will($this->returnValue('description'));
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://localhost'));

        $apiImage->expects($this->any())
            ->method('image')
            ->will($this->returnValue($image));

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($apiImage));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/IoKwI7E');

        $this->assertEquals('<div><p>title – description</p><img src="http://localhost" /></div>', $imgur->getContent());
    }

    public function testContentGallery()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbum = $this->getMockBuilder('Imgur\Api\Album')
            ->disableOriginalConstructor()
            ->getMock();

        $album = $this->getMockBuilder('Imgur\Api\Model\Album')
            ->disableOriginalConstructor()
            ->getMock();

        $image = $this->getMockBuilder('Imgur\Api\Model\Image')
            ->disableOriginalConstructor()
            ->getMock();

        $image->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue(''));
        $image->expects($this->any())
            ->method('getDescription')
            ->will($this->returnValue(''));
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://localhost'));

        $album->expects($this->any())
            ->method('getImages')
            ->will($this->returnValue(array($image)));
        $album->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('album title'));

        $apiAlbum->expects($this->any())
            ->method('album')
            ->will($this->returnValue($album));

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($apiAlbum));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/gallery/IoKwI7E');

        $this->assertEquals('<h2>album title</h2><p></p><div><img src="http://localhost" /></div>', $imgur->getContent());
    }

    public function testNoHashNoType()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://localhost');

        $this->assertEmpty($imgur->getContent());
    }

    public function testImgurFail()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->throwException(new RequestException()));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/gallery/IoKwI7E');

        $this->assertEmpty($imgur->getContent());
    }
}
