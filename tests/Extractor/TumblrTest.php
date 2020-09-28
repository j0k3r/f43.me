<?php

namespace App\Tests\Extractor;

use App\Extractor\Tumblr;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class TumblrTest extends AppTestCase
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
        $client = self::getMockClient([(new Response(200, ['X-Tumblr-User' => 'test']))]);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);
        $this->assertSame($expected, $tumblr->match($url));
    }

    public function testMatchFailRequest()
    {
        $client = self::getMockClient([(new Response(400, ['X-Tumblr-User' => 'test']))]);

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
        $client = self::getMockClient([(new Response(200, ['X-Tumblr-User' => null]))]);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);
        $this->assertFalse($tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project'));
    }

    public function testContent()
    {
        $client = self::getMockClient([
            // match()
            (new Response(200, ['X-Tumblr-User' => 'test'])),
            (new Response(200, ['X-Tumblr-User' => 'test'], (string) json_encode(['response' => ['posts' => [['body' => '<div>content</div>']]]]))),
            (new Response(200, ['X-Tumblr-User' => 'test'], (string) json_encode([]))),
            (new Response(400, ['X-Tumblr-User' => 'test'], (string) json_encode('oops'))),
        ]);

        $tumblr = new Tumblr('apikey');
        $tumblr->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $tumblr->setLogger($logger);

        // first test fail because we didn't match an url, so TumblrId isn't defined
        $this->assertEmpty($tumblr->getContent());

        $tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project');

        // consecutive calls
        $this->assertSame('<div>content</div>', $tumblr->getContent());
        // this one will got an empty array
        $this->assertEmpty($tumblr->getContent());
        // this one will catch an exception
        $this->assertEmpty($tumblr->getContent());

        $this->assertTrue($logHandler->hasWarning('Tumblr extract failed for: 96365413702 & thecodinglove.com'), 'Warning message matched');
    }
}
