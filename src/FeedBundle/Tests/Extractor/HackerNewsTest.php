<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\HackerNews;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class HackerNewsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://news.ycombinator.com/item?id=10074364', true, array('text' => 'toto', 'type' => 'story')),
            array('http://news.ycombinator.com/item?id=10074364', true, array('text' => 'toto', 'type' => 'job')),
            // comment
            array('http://news.ycombinator.com/item?id=10077812', false, array('text' => 'toto', 'type' => 'comment')),
            // pollopt
            array('http://news.ycombinator.com/item?id=160705', false, array('text' => 'toto', 'type' => 'pollopt')),
            array('https://goog.co', false),
            array('http://news.ycombinator.com/item?id=rtyui', false),
            array('http://user@:80', false),
        );
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
            new Response(400, [], Stream::factory('oops')),
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
            new Response(200, [], Stream::factory(json_encode(array('text' => 'toto', 'type' => 'story')))),
        ]);

        $client->getEmitter()->attach($mock);

        $hn = new HackerNews();
        $hn->setClient($client);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEquals('<p>toto</p>', $hn->getContent());
    }
}
