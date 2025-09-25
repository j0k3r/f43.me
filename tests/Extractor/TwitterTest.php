<?php

namespace App\Tests\Extractor;

use App\Extractor\Twitter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwitterOAuth\Exception\TwitterException;
use TwitterOAuth\TwitterOAuth;

class TwitterTest extends TestCase
{
    public static function dataMatch(): array
    {
        return [
            ['https://twitter.com/DoerteDev/statuses/50652222386027724', false],
            ['https://twitter.com/DoerteDev/statuses/506522223860277248', true],
            ['http://twitter.com/statuses/506522223860277248', true],
            ['https://twitter.com/iBSparkes/status/1138294142394437632', true],
            ['http://twitter.com/_youhadonejob/status/522835690665807872/photo/1', true],
            ['https://mobile.twitter.com/kcimc/status/638877262092337152/photo/1', true],
            ['http://user@:80', false],
        ];
    }

    #[DataProvider('dataMatch')]
    public function testMatch(string $url, bool $expected): void
    {
        $twitterOAuth = $this->getMockBuilder(TwitterOAuth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($twitterOAuth);
        $this->assertSame($expected, $twitter->match($url));
    }

    public function testContent(): void
    {
        $twitterOAuth = $this->getMockBuilder(TwitterOAuth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->willReturn([
                'user' => [
                    'name' => 'the name',
                    'screen_name' => 'the_name',
                ],
                'full_text' => 'My #awesome @tweet https://t.co/123456789 https://t.co/AfwH2EVRO3',
                'created_at' => 'Sun Oct 19 11:31:10 +0000 2014',
                'extended_entities' => [
                    'media' => [
                        [
                            'media_url_https' => 'http://0.0.0.0/image.jpg',
                            'url' => 'https://t.co/AfwH2EVRO3',
                        ],
                    ],
                ],
                'entities' => [
                    'urls' => [
                        [
                            'url' => 'https://t.co/123456789',
                            'expanded_url' => 'http://1.1.1.1',
                            'display_url' => 'http://1.1.1...',
                        ],
                    ],
                    'user_mentions' => [
                        [
                            'screen_name' => 'tweet',
                        ],
                    ],
                    'hashtags' => [
                        [
                            'text' => 'awesome',
                        ],
                    ],
                ],
                'quoted_status' => [
                    'user' => [
                        'name' => 'myself',
                        'screen_name' => 'myself',
                    ],
                    'full_text' => 'myself !!',
                    'created_at' => 'Mon Nov 20 11:31:10 +0000 2014',
                    'entities' => [
                        'urls' => [],
                        'user_mentions' => [],
                        'hashtags' => [],
                    ],
                ],
            ]);

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $content = (string) $twitter->getContent();

        $this->assertStringContainsString('the name', $content);
        $this->assertStringContainsString('<a href="https://twitter.com/the_name">@the_name</a>', $content, 'username ok');
        $this->assertStringContainsString('<a href="https://twitter.com/hashtag/awesome?src=hash">#awesome</a>', $content, 'Hashtag ok');
        $this->assertStringContainsString('<a href="https://twitter.com/tweet">@tweet</a>', $content, 'mention ok');
        $this->assertStringContainsString('<a href="http://1.1.1.1">http://1.1.1...</a>', $content, 'link ok');
        $this->assertStringContainsString('Sun Oct 19', $content);
        $this->assertStringContainsString('<img src="http://0.0.0.0/image.jpg" />', $content, 'media ok');
        $this->assertStringContainsString('@myself', $content, 'quote status ok');
    }

    public function testContentNoEntities(): void
    {
        $twitterOAuth = $this->getMockBuilder(TwitterOAuth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->willReturn([
                'user' => [
                    'name' => 'the name',
                    'screen_name' => 'the_name',
                ],
                'full_text' => 'my awesome tweet',
                'created_at' => 'Sun Oct 19 11:31:10 +0000 2014',
                'entities' => [
                    'urls' => [],
                    'user_mentions' => [],
                    'hashtags' => [],
                ],
            ]);

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $content = (string) $twitter->getContent();

        $this->assertStringContainsString('the name', $content);
        $this->assertStringContainsString('the_name', $content);
        $this->assertStringContainsString('my awesome tweet', $content);
        $this->assertStringContainsString('Sun Oct 19', $content);
    }

    public function testContentBadResponse(): void
    {
        $twitterOAuth = $this->getMockBuilder(TwitterOAuth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->will($this->throwException(new TwitterException()));

        $twitter = new Twitter($twitterOAuth);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $twitter->setLogger($logger);

        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $this->assertEmpty($twitter->getContent());

        $this->assertTrue($logHandler->hasWarning('Twitter extract failed for: 506522223860277248'), 'Warning message matched');
    }

    public function testNoTweet(): void
    {
        $twitterOAuth = $this->getMockBuilder(TwitterOAuth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('http://localhost');

        $this->assertEmpty($twitter->getContent());
    }
}
