<?php

namespace App\Tests\Converter;

use App\Converter\Twitter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TwitterTest extends TestCase
{
    public static function dataMatch(): array
    {
        return [
            [
                '<div></div>',
                '<div></div>',
                '',
                0,
            ],
            [
                '<a href="https://twitter.com/elonmusk/status/827720686911291392">test</a>',
                '<a href="https://twitter.com/elonmusk/status/827720686911291392">test</a>',
                '827720686911291392',
                1,
            ],
            [
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Minecraft <a href="https://t.co/lU1YzJjLOZ">pic.twitter.com/lU1YzJjLOZ</a></p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Minecraft <a href="https://t.co/lU1YzJjLOZ"><br /><img src="https://pbs.twimg.com/media/C3ynX_OWcAE-DQA.jpg" /></a></p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '827720686911291392',
                1,
            ],
            [
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Minecraft</p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Minecraft</p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '827720686911291392',
                1,
            ],
            [
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr"><a href="https://t.co/Uhec6sqrYb">https://t.co/Uhec6sqrYb</a></p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '<blockquote class="twitter-tweet"><p lang="en" dir="ltr"><a href="http://www.google.io">http://www.google.io</a></p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>',
                '827720686911291392',
                1,
            ],
        ];
    }

    #[DataProvider('dataMatch')]
    public function testMatch(string $html, string $expected, string $twitterId, int $twitterExtractorOccurence): void
    {
        $twitterExtractor = $this->getMockBuilder(\App\Extractor\Twitter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitterExtractor->expects($this->exactly($twitterExtractorOccurence))
            ->method('match')
            ->with('https://twitter.com/username/' . $twitterId)
            ->willReturn(true);

        $twitterExtractor->expects($this->exactly($twitterExtractorOccurence))
            ->method('retrieveTwitterData')
            ->willReturn([
                'entities' => [
                    'media' => [[
                        'display_url' => 'pic.twitter.com/lU1YzJjLOZ',
                        'media_url_https' => 'https://pbs.twimg.com/media/C3ynX_OWcAE-DQA.jpg',
                    ]],
                    'urls' => [[
                        'url' => 'https://t.co/Uhec6sqrYb',
                        'expanded_url' => 'http://www.google.io',
                    ]],
                ],
            ]);

        $twitterConverter = new Twitter($twitterExtractor);
        $twitterConverter->setLogger(new NullLogger());
        $this->assertStringContainsString($expected, $twitterConverter->convert($html));
    }

    public function testMatchButTwitterExtractFail(): void
    {
        $html = '<blockquote class="twitter-tweet"><p lang="en" dir="ltr">Minecraft <a href="https://t.co/lU1YzJjLOZ">pic.twitter.com/lU1YzJjLOZ</a></p><p>— Elon Musk (@elonmusk) <a href="https://twitter.com/elonmusk/status/827720686911291392">February 4, 2017</a></p></blockquote>';

        $twitterExtractor = $this->getMockBuilder(\App\Extractor\Twitter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $twitterExtractor->expects($this->once())
            ->method('match')
            ->willReturn(true);

        $twitterExtractor->expects($this->once())
            ->method('retrieveTwitterData')
            ->willReturn(false);

        $twitterConverter = new Twitter($twitterExtractor);
        $twitterConverter->setLogger(new NullLogger());

        $this->assertSame($html, $twitterConverter->convert($html));
    }
}
