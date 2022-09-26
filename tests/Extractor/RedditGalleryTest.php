<?php

namespace App\Tests\Extractor;

use App\Extractor\RedditGallery;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class RedditGalleryTest extends AppTestCase
{
    public function dataMatch(): array
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
    public function testMatch(string $url, bool $expected): void
    {
        $redditGallery = new RedditGallery();
        $this->assertSame($expected, $redditGallery->match($url));
    }

    public function testMatchRedditBadRequest(): void
    {
        $client = self::getMockClient([new Response(400, [], (string) json_encode('oops'))]);

        $redditGallery = new RedditGallery();
        $redditGallery->setClient($client);

        $this->assertFalse($redditGallery->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchReddit(): void
    {
        $client = self::getMockClient([new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['is_gallery' => true]]]]]]))]);

        $redditGallery = new RedditGallery();
        $redditGallery->setClient($client);

        $this->assertTrue($redditGallery->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testMatchRedditNotSelf(): void
    {
        $client = self::getMockClient([new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => ['is_gallery' => false]]]]]]))]);

        $redditGallery = new RedditGallery();
        $redditGallery->setClient($client);

        $this->assertFalse($redditGallery->match('https://www.reddit.com/r/jailbreak/comments/7bnvuq/request_tweak_that_allows_more_than_140/'));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([new Response(200, [], (string) json_encode([['data' => ['children' => [['data' => [
            'is_gallery' => true,
            'title' => 'the title',
            'selftext_html' => null,
            'score' => 100,
            'author' => 'bob',
            'num_comments' => 100,
            'link_flair_text' => 'GIFS',
            'media' => null,
            'gallery_data' => [
                'items' => [[
                    'caption' => 'coucou',
                    'media_id' => 'fjuhi9ld26q91',
                ]],
            ],
            'media_metadata' => [
                'fjuhi9ld26q91' => [
                    's' => [
                        'u' => 'https://preview.redd.it/dnwyvs4r26q91.png?width=1004&format=png&auto=webp&s=82f7c8878a36a21bbdaf626c65e522d1c3ac90fd',
                    ],
                ],
            ],
        ]]]]]]))]);

        $redditGallery = new RedditGallery();
        $redditGallery->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $redditGallery->setLogger($logger);

        // first test fail because we didn't match an url, so redditGalleryUrl isn't defined
        $this->assertEmpty($redditGallery->getContent());

        $redditGallery->match('https://www.reddit.com/gallery/xodu0r');

        $this->assertSame('<div><h2>the title</h2><ul><li>Score: 100</li><li>Comments: 100</li><li>Flair: GIFS</li><li>Author: bob</li></ul><p>coucou</p><p><img src="https://preview.redd.it/dnwyvs4r26q91.png?width=1004&format=png&auto=webp&s=82f7c8878a36a21bbdaf626c65e522d1c3ac90fd" /></p></div>', $redditGallery->getContent());
    }
}
