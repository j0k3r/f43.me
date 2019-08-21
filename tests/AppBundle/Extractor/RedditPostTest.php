<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\RedditPost;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class RedditPostTest extends AppTestCase
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
        $client = self::getMockClient([(new Response(400, [], (string) json_encode('oops')))]);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertFalse($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchReddit()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['is_self' => true]]]]]])))]);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertTrue($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchRedditNotSelf()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['is_self' => false]]]]]])))]);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $this->assertFalse($redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testContent()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => [
            'domain' => 'self.jailbreak',
            'is_self' => true,
            'title' => 'the title',
            'selftext_html' => '&lt;div class="md"&gt;&lt;p&gt;test&lt;/p&gt;&lt;/div&gt;',
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
        ]]]]]])))]);

        $redditPost = new RedditPost();
        $redditPost->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $redditPost->setLogger($logger);

        // first test fail because we didn't match an url, so redditPostUrl isn't defined
        $this->assertEmpty($redditPost->getContent());

        $redditPost->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/');

        $this->assertSame('<div><h2>the title</h2><ul><li>Score: 100</li><li>Comments: 100</li><li>Flair: GIFS</li><li>Author: bob</li></ul></div><div class="md"><p>test</p></div>', $redditPost->getContent());
    }
}
