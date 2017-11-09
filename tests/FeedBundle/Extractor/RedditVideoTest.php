<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\RedditVideo;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class RedditVideoTest extends \PHPUnit_Framework_TestCase
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
        $redditVideo = new RedditVideo();
        $this->assertSame($expected, $redditVideo->match($url));
    }

    public function testMatchRedditBadRequest()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertFalse($redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/'));
    }

    public function testMatchRedditNotAVideo()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => ['domain' => 'self.gifs']]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertFalse($redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/'));
    }

    public function testMatchReddit()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => ['domain' => 'v.redd.it']]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

        $redditVideo = new RedditVideo();
        $redditVideo->setClient($client);

        $this->assertTrue($redditVideo->match('https://www.reddit.com/r/videos/comments/6rrwyj/that_small_heart_attack/'));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([['data' => ['children' => [['data' => [
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
            ]]]]]]))),
        ]);

        $client->getEmitter()->attach($mock);

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
