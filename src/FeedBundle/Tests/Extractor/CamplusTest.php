<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Camplus;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class CamplusTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['http://campl.us/rL9Q', true],
            ['http://campl.us/jQKwkTKxLHG', true],
            ['https://campl.us/rL9Q', true],
            ['https://campl.us/hvGw', true],
            ['http://campl.us/ozu1', true],
            ['http://pics.campl.us/f/6/6283.e61ef28b1535e624f30e4ef96fcd3f52.jpg', false],
            ['http://github.com/symfony/symfony', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $camplus = new Camplus();
        $this->assertEquals($expected, $camplus->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([
                'page' => ['tweet' => [
                    'id'       => '123',
                    'username' => 'j0k',
                    'realname' => 'j0k',
                    'text'     => 'yay',
                ]], 'pictures' => [[
                    '480px' => 'http://0.0.0.0/youpi.jpg',
                ]],
            ]))),
        ]);

        $client->getEmitter()->attach($mock);

        $camplus = new Camplus();
        $camplus->setClient($client);

        // first test fail because we didn't match an url, so camplusId isn't defined
        $this->assertEmpty($camplus->getContent());

        $camplus->match('http://campl.us/rL9Q');

        $content = $camplus->getContent();

        $this->assertContains('<h2>Photo from j0k</h2>', $content);
        $this->assertContains('<p><img src="http://0.0.0.0/youpi.jpg" /></p>', $content);
    }

    public function testContentWithException()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $camplus = new Camplus();
        $camplus->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $camplus->setLogger($logger);

        $camplus->match('http://campl.us/rL9Q');

        // this one will catch an exception
        $this->assertEmpty($camplus->getContent());

        $this->assertTrue($logHandler->hasWarning('Camplus extract failed for: rL9Q'), 'Warning message matched');
    }
}
