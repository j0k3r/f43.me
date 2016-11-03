<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Imgur;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class ImgurTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['http://i.imgur.com/zNUC9TA.jpg', true],
            ['http://i.imgur.com/zNUC9TA', true],
            ['https://i.imgur.com/zNUC9TA.jpg', true],
            ['https://i.imgur.com/zNUC9TA', true],
            ['http://imgur.com/zNUC9TA', true],
            ['https://imgur.com/zNUC9TA', true],
            ['https://imgur.com/a/dLaMy', true],
            ['https://imgur.com/a/dLaMy?gallery', true],
            ['https://imgur.com/gallery/dLaMy', true],
            ['http://imgur.com/gallery/IDuXHMJ', true],
            ['https://imgur.com/duziauziaozaoLaMy', false],
            ['https://imgur.com/Ay', false],
            ['http://imgur.com/UMOCfIk.gifv', true],
            ['https://imgur.com/gallery/GItbbVZ/new', true],
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

        $apiAlbumOrImage = $this->getMockBuilder('Imgur\Api\AlbumOrImage')
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbumOrImage->expects($this->any())
            ->method('find')
            ->will($this->returnValue([
                'id' => 'zNUC9TA',
                'title' => null,
                'description' => null,
                'datetime' => 1474446337,
                'type' => 'image/gif',
                'animated' => true,
                'width' => 720,
                'height' => 720,
                'size' => 15215783,
                'views' => 7628085,
                'bandwidth' => 116067286065555,
                'vote' => null,
                'favorite' => false,
                'nsfw' => false,
                'section' => 'nevertellmetheodds',
                'account_url' => null,
                'account_id' => null,
                'is_ad' => false,
                'in_gallery' => true,
                'gifv' => 'http://i.imgur.com/zNUC9TA.gifv',
                'mp4' => 'http://i.imgur.com/zNUC9TA.mp4',
                'mp4_size' => 888929,
                'link' => 'http://i.imgur.com/zNUC9TA.gif',
                'looping' => true,
            ]));

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($apiAlbumOrImage));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/zNUC9TA');

        $this->assertEquals('<div><img src="http://i.imgur.com/zNUC9TA.gif" /></div>', $imgur->getContent());
    }

    public function testContentAlbum()
    {
        $imgurClient = $this->getMockBuilder('Imgur\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbumOrImage = $this->getMockBuilder('Imgur\Api\AlbumOrImage')
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbumOrImage->expects($this->any())
            ->method('find')
            ->will($this->returnValue([
                'id' => 'dLaMy',
                'title' => 'Building the Spruce Moose',
                'description' => null,
                'datetime' => 1402349793,
                'cover' => 'ZaSpdSG',
                'cover_width' => 5472,
                'cover_height' => 3648,
                'account_url' => 'projectmoose',
                'account_id' => 12282892,
                'privacy' => 'public',
                'layout' => 'blog',
                'views' => 448788,
                'link' => 'http://imgur.com/a/dLaMy',
                'favorite' => false,
                'nsfw' => false,
                'section' => 'DIY',
                'images_count' => 63,
                'in_gallery' => true,
                'is_ad' => false,
                'images' => [
                    [
                        'id' => 'nrKAg6T',
                        'title' => null,
                        'description' => "Here's the finished product in Utah- State no. 3",
                        'datetime' => 1402364481,
                        'type' => 'image/jpeg',
                        'animated' => false,
                        'width' => 5184,
                        'height' => 3456,
                        'size' => 1835179,
                        'views' => 728807,
                        'bandwidth' => 1337491301453,
                        'vote' => null,
                        'favorite' => false,
                        'nsfw' => null,
                        'section' => null,
                        'account_url' => null,
                        'account_id' => null,
                        'is_ad' => false,
                        'in_gallery' => false,
                        'link' => 'http://i.imgur.com/nrKAg6T.jpg',
                    ],
                    [
                        'id' => 'HdcEO2X',
                        'title' => null,
                        'description' => 'Here she is. A 1986 Chevy Bluebird school bus...was an exciting day picking her up!',
                        'datetime' => 1402356596,
                        'type' => 'image/jpeg',
                        'animated' => false,
                        'width' => 3264,
                        'height' => 2448,
                        'size' => 2188601,
                        'views' => 672879,
                        'bandwidth' => 1472663652279,
                        'vote' => null,
                        'favorite' => false,
                        'nsfw' => null,
                        'section' => null,
                        'account_url' => null,
                        'account_id' => null,
                        'is_ad' => false,
                        'in_gallery' => false,
                        'link' => 'http://i.imgur.com/HdcEO2X.jpg',
                    ],
                ],
            ]));

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($apiAlbumOrImage));

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/a/dLaMy');

        $this->assertEquals('<h2>Building the Spruce Moose</h2><p></p><div><p> – Here\'s the finished product in Utah- State no. 3</p><img src="http://i.imgur.com/nrKAg6T.jpg" /></div><div><p> – Here she is. A 1986 Chevy Bluebird school bus...was an exciting day picking her up!</p><img src="http://i.imgur.com/HdcEO2X.jpg" /></div>', $imgur->getContent());
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
            ->will($this->throwException(new \Imgur\Exception\ErrorException()));

        $imgur = new Imgur($imgurClient);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $imgur->setLogger($logger);

        $imgur->match('http://imgur.com/gallery/xxxxxx');

        $this->assertEmpty($imgur->getContent());

        $this->assertTrue($logHandler->hasWarning('Imgur extract failed for: xxxxxx'), 'Warning message matched');
    }
}
