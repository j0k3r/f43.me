<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Imgur;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class ImgurTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://i.imgur.com/IoKwI7E.jpg', true),
            array('http://i.imgur.com/IoKwI7E', true),
            array('https://i.imgur.com/IoKwI7E.jpg', true),
            array('https://i.imgur.com/IoKwI7E', true),
            array('http://imgur.com/IoKwI7E', true),
            array('https://imgur.com/IoKwI7E', true),
            array('https://imgur.com/a/dLaMy', true),
            array('https://imgur.com/a/dLaMy?gallery', true),
            array('https://imgur.com/gallery/dLaMy', true),
            array('http://imgur.com/gallery/IDuXHMJ', true),
            array('https://imgur.com/duziauziaozaoLaMy', false),
            array('https://imgur.com/Ay', false),
            array('http://imgur.com/UMOCfIk.gifv', true),
            array('http://user@:80', false),
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

    public function testContentAlbum()
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
        $imgur->match('http://imgur.com/a/IoKwI7E');

        $this->assertEquals('<h2>album title</h2><p></p><div><img src="http://localhost" /></div>', $imgur->getContent());
    }

    public function testContentGallery()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $apiGallery = $this->getMockBuilder('Imgur\Api\Gallery')
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

        $apiGallery->expects($this->any())
            ->method('image')
            ->will($this->returnValue($image));

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($apiGallery));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/gallery/IoKwI7E');

        $this->assertEquals('<div><img src="http://localhost" /></div>', $imgur->getContent());
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
            ->will($this->throwException(new \Guzzle\Http\Exception\RequestException()));

        $imgur = new Imgur($imgurClient);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $imgur->setLogger($logger);

        $imgur->match('http://imgur.com/gallery/IoKwI7E');

        $this->assertEmpty($imgur->getContent());

        $this->assertTrue($logHandler->hasWarning('Imgur extract failed for: IoKwI7E'), 'Warning message matched');
    }
}
