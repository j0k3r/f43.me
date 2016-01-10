<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Imgur;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class ImgurTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['http://i.imgur.com/IoKwI7E.jpg', true],
            ['http://i.imgur.com/IoKwI7E', true],
            ['https://i.imgur.com/IoKwI7E.jpg', true],
            ['https://i.imgur.com/IoKwI7E', true],
            ['http://imgur.com/IoKwI7E', true],
            ['https://imgur.com/IoKwI7E', true],
            ['https://imgur.com/a/dLaMy', true],
            ['https://imgur.com/a/dLaMy?gallery', true],
            ['https://imgur.com/gallery/dLaMy', true],
            ['http://imgur.com/gallery/IDuXHMJ', true],
            ['https://imgur.com/duziauziaozaoLaMy', false],
            ['https://imgur.com/Ay', false],
            ['http://imgur.com/UMOCfIk.gifv', true],
            ['http://user@:80', false],
        ];
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
            ->will($this->returnValue([$image]));
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
        $logger = new Logger('test', [$logHandler]);
        $imgur->setLogger($logger);

        $imgur->match('http://imgur.com/gallery/IoKwI7E');

        $this->assertEmpty($imgur->getContent());

        $this->assertTrue($logHandler->hasWarning('Imgur extract failed for: IoKwI7E'), 'Warning message matched');
    }
}
