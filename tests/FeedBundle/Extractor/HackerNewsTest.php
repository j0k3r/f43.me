<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\HackerNews;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class HackerNewsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://news.ycombinator.com/item?id=10074364', true, ['text' => 'toto', 'type' => 'story']],
            ['http://news.ycombinator.com/item?id=10074364', true, ['text' => 'toto', 'type' => 'job']],
            // comment
            ['http://news.ycombinator.com/item?id=10077812', false, ['text' => 'toto', 'type' => 'comment']],
            // pollopt
            ['http://news.ycombinator.com/item?id=160705', false, ['text' => 'toto', 'type' => 'pollopt']],
            ['https://goog.co', false],
            ['http://news.ycombinator.com/item?id=rtyui', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected, $valueReturned = null)
    {
        $client = new Client();

        $response = new Response(200, []);
        if (null !== $valueReturned) {
            $response = new Response(200, [], Stream::factory(json_encode($valueReturned)));
        }

        $mock = new Mock([$response]);

        $client->getEmitter()->attach($mock);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertEquals($expected, $hn->match($url));
    }

    public function testMatchGuzzleFail()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertEquals(false, $hn->match('http://news.ycombinator.com/item?id=10074364'));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['text' => 'toto', 'type' => 'story']))),
        ]);

        $client->getEmitter()->attach($mock);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEquals('<p>toto</p>', $hn->getContent());
    }

    public function testContentWithoutText()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['type' => 'story']))),
        ]);

        $client->getEmitter()->attach($mock);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEmpty($hn->getContent());
    }
}
