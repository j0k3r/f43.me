<?php

namespace App\Tests\Extractor;

use App\Extractor\Flickr;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class FlickrTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            // single photo
            ['https://www.flickr.com/photos/palnick/15000967101/in/photostream/lightbox/', true],
            ['http://www.flickr.com/photos/palnick/15000967102/', true],
            ['https://farm6.staticflickr.com/5581/15000967103_8eb7552825_n.jpg', true],
            ['http://farm6.static.flickr.com/5581/15000967104_8eb7552825_n.jpg', true],
            ['http://farm6.static.flicker.com/5581/15000967104_8eb7552825_n.jpg', false],
            ['http://farm6.static.flickr.com/5581/1500096710_8eb7552825_n.jpg', true],
            ['https://www.flickr.com/photos/europeanspaceagency/15739982196/in/set-72157638315605535', true],
            ['https://www.flickr.com/photos/dfmagazine/8286098812/', true],
            ['https://www.flickr.com/photos/64871835@N04/29186070533/in/dateposted-public/', true],

            // photo set
            ['https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/', true],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $flickr = new Flickr();
        $this->assertSame($expected, $flickr->match($url));
    }

    public function testSinglePhoto()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode([
                'flickr_type' => 'photo',
                'url' => 'https://0.0.0.0/large.jpg',
                'title' => 'title',
                'author_name' => 'mememe',
                'author_url' => 'https://0.0.0.0/me',
                'html' => '<a data-flickr-embed="true"></a>',
            ]))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $flickr = new Flickr();
        $flickr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $flickr->setLogger($logger);

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('http://www.flickr.com/photos/palnick/15000967102/');

        // consecutive calls
        $content = (string) $flickr->getContent();

        $this->assertStringContainsString('<a data-flickr-embed="true"></a>', $content);
        $this->assertStringContainsString('<h2>title</h2>', $content);
        $this->assertStringContainsString('data-flickr-embed', $content);
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());

        $this->assertTrue($logHandler->hasWarning('Flickr extract failed for: http://www.flickr.com/photos/palnick/15000967102/'), 'Warning message matched');
    }

    public function testPhotoSet()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode([
                'flickr_type' => 'album',
                'thumbnail_url' => 'https://0.0.0.0/small.jpg',
                'title' => 'title',
                'author_name' => 'mememe',
                'author_url' => 'https://0.0.0.0/me',
                'html' => '<a data-flickr-embed="true"></a>',
            ]))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $flickr = new Flickr();
        $flickr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $flickr->setLogger($logger);

        // first test fail because we didn't match an url, so FlickrId isn't defined
        $this->assertEmpty($flickr->getContent());

        $flickr->match('https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/');

        // consecutive calls
        $content = (string) $flickr->getContent();

        $this->assertStringContainsString('<a data-flickr-embed="true"></a>', $content);
        $this->assertStringContainsString('<h2>title</h2>', $content);
        $this->assertStringContainsString('data-flickr-embed', $content);
        // this one will got an empty array
        $this->assertEmpty($flickr->getContent());
        // this one will catch an exception
        $this->assertEmpty($flickr->getContent());

        $this->assertTrue($logHandler->hasWarning('Flickr extract failed for: https://www.flickr.com/photos/europeanspaceagency/sets/72157638315605535/'), 'Warning message matched');
    }
}
