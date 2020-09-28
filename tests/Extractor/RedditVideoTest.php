<?php

namespace App\Tests\Extractor;

use App\Extractor\RedditVideo;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class RedditVideoTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $redditVideo = new RedditVideo();
        $this->assertSame($expected, $redditVideo->match($url));
    }

    public function testMatchRedditBadRequest()
    {
        $client = self::getMockClient([(new Response(400, [], (string) json_encode('oops')))]);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertFalse($redditVideo->match('https://v.redd.it/funfg141o3hz'));
    }

    public function testMatchRedditNotAVideo()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['domain' => 'self.gifs']]]]]])))]);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertFalse($redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/'));
    }

    public function testMatchReddit()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['domain' => 'v.redd.it']]]]]])))]);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertTrue($redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/'));
    }

    public function testContent()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => [
            'domain' => 'v.redd.it',
            'thumbnail' => 'http://image.reddit',
            'preview' => [
                'images' => [
                    [
                        'source' => [
                            'url' => 'http://image.reddit.preview',
                        ],
                    ],
                ],
            ],
            'title' => 'the title',
            'score' => 100,
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

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $redditVideo->setLogger($logger);

        // first test fail because we didn't match an url, so redditvideoUrl isn't defined
        $this->assertEmpty($redditVideo->getContent());

        $redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/');

        $this->assertSame('<div><h2>the title</h2><ul><li>Score: 100</li><li>Comments: 100</li><li>Flair: GIFS</li></ul><p><img src="http://image.reddit.preview"></p></div><iframe src="https://v.redd.it/funfg141o3hz/DASH_2_4_M" frameborder="0" scrolling="no" width="250" height="120" allowfullscreen></iframe>', $redditVideo->getContent());
    }
}
