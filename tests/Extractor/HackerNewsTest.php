<?php

namespace App\Tests\Extractor;

use App\Extractor\HackerNews;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;

class HackerNewsTest extends AppTestCase
{
    public function dataMatch(): array
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
    public function testMatch(string $url, bool $expected, array $valueReturned = null): void
    {
        $response = new Response(200, []);
        if (null !== $valueReturned) {
            $response = new Response(200, [], (string) json_encode($valueReturned));
        }

        $client = self::getMockClient([$response]);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertSame($expected, $hn->match($url));
    }

    public function testMatchGuzzleFail(): void
    {
        $client = self::getMockClient([(new Response(400, [], (string) json_encode('oops')))]);

        $hn = new HackerNews();
        $hn->setClient($client);
        $this->assertFalse($hn->match('http://news.ycombinator.com/item?id=10074364'));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode(['text' => 'toto', 'type' => 'story'])))]);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertSame('<p>toto</p>', $hn->getContent());
    }

    public function testContentWithoutText(): void
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode(['type' => 'story'])))]);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEmpty($hn->getContent());
    }
}
