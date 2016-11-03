<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Tumblr;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class TumblrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project', true],
            ['http://thecodinglove.com/post/100483712123/monday-morning', true],
            ['http://google.com', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['X-Tumblr-User' => 'test']),
        ]);

        $client->getEmitter()->attach($mock);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);
        $this->assertEquals($expected, $tumblr->match($url));
    }

    public function testMatchFailRequest()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, ['X-Tumblr-User' => 'test']),
        ]);

        $client->getEmitter()->attach($mock);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $tumblr->setLogger($logger);

        $tumblr->match('http://lesjoiesducode.fr/post/125256171232/quand-après-une-heure-de-dev-je-teste-mon-code-en');

        $this->assertTrue($logHandler->hasWarning('Tumblr extract failed for: http://lesjoiesducode.fr/post/125256171232/quand-après-une-heure-de-dev-je-teste-mon-code-en'), 'Warning message matched');
    }

    public function testMatchNotTumblrUser()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['X-Tumblr-User' => null]),
        ]);

        $client->getEmitter()->attach($mock);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);
        $this->assertFalse($tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project'));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            // match()
            new Response(200, ['X-Tumblr-User' => 'test']),
            new Response(200, ['X-Tumblr-User' => 'test'], Stream::factory(json_encode(['response' => ['posts' => [['body' => '<div>content</div>']]]]))),
            new Response(200, ['X-Tumblr-User' => 'test'], Stream::factory(json_encode([]))),
            new Response(400, ['X-Tumblr-User' => 'test'], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $tumblr->setLogger($logger);

        // first test fail because we didn't match an url, so TumblrId isn't defined
        $this->assertEmpty($tumblr->getContent());

        $tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project');

        // consecutive calls
        $this->assertEquals('<div>content</div>', $tumblr->getContent());
        // this one will got an empty array
        $this->assertEmpty($tumblr->getContent());
        // this one will catch an exception
        $this->assertEmpty($tumblr->getContent());

        $this->assertTrue($logHandler->hasWarning('Tumblr extract failed for: 96365413702 & thecodinglove.com'), 'Warning message matched');
    }
}
