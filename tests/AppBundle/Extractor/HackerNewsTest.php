<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\HackerNews;
use GuzzleHttp\Psr7\Response;
use Tests\AppBundle\AppTestCase;

class HackerNewsTest extends AppTestCase
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
        $response = new Response(200, []);
        if (null !== $valueReturned) {
            $response = new Response(200, [], json_encode($valueReturned));
        }

        $client = self::getMockClient([$response]);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertSame($expected, $hn->match($url));
    }

    public function testMatchGuzzleFail()
    {
        $client = self::getMockClient([(new Response(400, [], json_encode('oops')))]);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertFalse($hn->match('http://news.ycombinator.com/item?id=10074364'));
    }

    public function testContent()
    {
        $client = self::getMockClient([(new Response(200, [], json_encode(['text' => 'toto', 'type' => 'story'])))]);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertSame('<p>toto</p>', $hn->getContent());
    }

    public function testContentWithoutText()
    {
        $client = self::getMockClient([(new Response(200, [], json_encode(['type' => 'story'])))]);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEmpty($hn->getContent());
    }
}
