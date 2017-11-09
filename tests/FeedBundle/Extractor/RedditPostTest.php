<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\RedditPost;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class RedditPostTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://v.redd.it/funfg141o3hz', false],
            ['http://v.redd.it/', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $redditPost = new RedditPost();
        $this->assertSame($expected, $redditPost->match($url));
    }

    public function testMatchRedditBadRequest()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertFalse($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchReddit()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => ['is_self' => true]]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertTrue($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchRedditNotSelf()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => ['is_self' => false]]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertFalse($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => [
                'domain' => 'self.jailbreak',
                'is_self' => true,
                'title' => 'the title',
                'selftext' => 'this is the text',
                'score' => 100,
                'author' => 'bob',
                'num_comments' => 100,
                'link_flair_text' => 'GIFS',
                'media' => [
                    'reddit_video' => [
                        'fallback_url' => 'https://v.redd.it/funfg141o3hz/DASH_2_4_M',
                        'width' => 250,
                        'height' => 120,
                    ],
                ],
            ]]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $redditPost->setLogger($logger);

        // first test fail because we didn't match an url, so redditPostUrl isn't defined
        $this->assertEmpty($redditPost->getContent());

        $redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/');

        $this->assertSame('<div><h2>the title</h2><ul><li>Score: 100</li><li>Comments: 100</li><li>Flair: GIFS</li><li>Author: bob</li></ul><p>this is the text</p></div>', $redditPost->getContent());
    }
}
