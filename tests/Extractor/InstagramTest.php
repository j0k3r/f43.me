<?php

namespace App\Tests\Extractor;

use App\Extractor\Instagram;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;

class InstagramTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['https://instagram.com/p/2N5UHfChAZ/', true],
            ['http://instagr.am/p/fA9uwTtkSN/', true],
            ['https://instagram.com/p/4FKIIdJ9LM/?taken-by=j0k', true],
            ['https://www.instagram.com/p/BAqirNbwEc0/', true],
            ['https://instagram.com', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $instagram = new Instagram();
        $this->assertSame($expected, $instagram->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(200, [], (string) json_encode(''))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $instagram = new Instagram();
        $instagram->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $instagram->setLogger($logger);

        // first test fail because we didn't match an url, so InstagramId isn't defined
        $this->assertEmpty($instagram->getContent());

        $instagram->match('https://instagram.com/p/2N5UHfChAZ/');

        // consecutive calls
        $this->assertSame('<div class="f43me-instagram-extracted"><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $instagram->getContent());
        // this one will got an empty array
        $this->assertEmpty($instagram->getContent());
        // this one will catch an exception
        $this->assertEmpty($instagram->getContent());

        $this->assertTrue($logHandler->hasWarning('Instagram extract failed for: https://instagram.com/p/2N5UHfChAZ/'), 'Warning message matched');
    }

    public function testGetImageOnly()
    {
        $client = self::getMockClient([
            (new Response(200, [], (string) json_encode(['title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>']))),
            (new Response(400, [], (string) json_encode('oops'))),
        ]);

        $instagram = new Instagram();
        $instagram->setLogger(new NullLogger());
        $instagram->setClient($client);
        $instagram->match('https://instagram.com/p/2N5UHfChAZ/');

        // first call got a real response
        $this->assertSame('http://0.0.0.0/img.jpg', $instagram->getImageOnly());

        // second call got an error reponse
        $this->assertSame('', $instagram->getImageOnly());
    }
}
