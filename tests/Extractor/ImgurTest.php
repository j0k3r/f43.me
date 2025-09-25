<?php

namespace App\Tests\Extractor;

use App\Extractor\Imgur;
use Imgur\Api\Album;
use Imgur\Api\Image;
use Imgur\Client;
use Imgur\Exception\ErrorException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImgurTest extends TestCase
{
    public static function dataMatch(): array
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
            ['https://imgur.com/signin', false],
            ['https://imgur.com/signin/456q4z', false],
            ['http://imgur.com/UMOCfIk.gifv', true],
            ['https://imgur.com/gallery/GItbbVZ/new', true],
            ['http://user@:80', false],
        ];
    }

    #[DataProvider('dataMatch')]
    public function testMatch(string $url, bool $expected): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $imgur = new Imgur($imgurClient);
        $this->assertSame($expected, $imgur->match($url));
    }

    public function testContentImage(): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbum = $this->getMockBuilder(Album::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbum->expects($this->any())
            ->method('album')
            ->willReturn([
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
            ]);

        $imgurClient->expects($this->any())
            ->method('api')
            ->willReturn($apiAlbum);

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/a/zNUC9TA');

        $this->assertSame('<div><img src="http://i.imgur.com/zNUC9TA.gif" /></div>', $imgur->getContent());
    }

    public function testContentMp4(): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiImage = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiImage->expects($this->any())
            ->method('image')
            ->willReturn([
                'id' => '1S10bkI',
                'title' => null,
                'description' => null,
                'datetime' => 1509133400,
                'type' => 'video/mp4',
                'animated' => true,
                'width' => 720,
                'height' => 898,
                'size' => 2131051,
                'views' => 4413346,
                'bandwidth' => 9405065406646,
                'vote' => null,
                'favorite' => false,
                'nsfw' => false,
                'section' => 'gifs',
                'account_url' => null,
                'account_id' => null,
                'is_ad' => false,
                'in_gallery' => true,
                'gifv' => 'https://i.imgur.com/1S10bkI.gifv',
                'mp4' => 'https://i.imgur.com/1S10bkI.mp4',
                'mp4_size' => 2131051,
                'link' => 'https://i.imgur.com/1S10bkI.mp4',
            ]);

        $imgurClient->expects($this->any())
            ->method('api')
            ->willReturn($apiImage);

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/1S10bkI');

        $this->assertSame('<video width="720" height="898" controls="controls"><source src="https://i.imgur.com/1S10bkI.mp4" type="video/mp4" /></video>', $imgur->getContent());
    }

    public function testContentAlbum(): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbum = $this->getMockBuilder(Album::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiAlbum->expects($this->any())
            ->method('album')
            ->willReturn([
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
            ]);

        $imgurClient->expects($this->any())
            ->method('api')
            ->willReturn($apiAlbum);

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://imgur.com/a/dLaMy');

        $this->assertSame('<h2>Building the Spruce Moose</h2><p></p><div><p> – Here\'s the finished product in Utah- State no. 3</p><img src="http://i.imgur.com/nrKAg6T.jpg" /></div><div><p> – Here she is. A 1986 Chevy Bluebird school bus...was an exciting day picking her up!</p><img src="http://i.imgur.com/HdcEO2X.jpg" /></div>', $imgur->getContent());
    }

    public function testNoHashNoType(): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $imgur = new Imgur($imgurClient);
        $imgur->match('http://localhost');

        $this->assertEmpty($imgur->getContent());
    }

    public function testImgurFail(): void
    {
        $imgurClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $imgurClient->expects($this->any())
            ->method('api')
            ->will($this->throwException(new ErrorException()));

        $imgur = new Imgur($imgurClient);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $imgur->setLogger($logger);

        $imgur->match('http://imgur.com/gallery/xxxxxx');

        $this->assertEmpty($imgur->getContent());

        $this->assertTrue($logHandler->hasWarning('Imgur extract failed with "album" for: xxxxxx'), 'Warning message matched (for album)');
        $this->assertTrue($logHandler->hasWarning('Imgur extract failed with "image" for: xxxxxx'), 'Warning message matched (for image)');
    }
}
