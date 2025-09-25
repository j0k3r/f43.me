<?php

namespace App\Tests\Improver;

use App\Improver\HackerNews;
use App\Tests\AppTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class HackerNewsTest extends AppTestCase
{
    public static function dataMatch(): array
    {
        return [
            ['news.ycombinator.com', true],
            ['google.fr', false],
        ];
    }

    #[DataProvider('dataMatch')]
    public function testMatch(string $url, bool $expected): void
    {
        $hn = new HackerNews(self::getMockClient());

        $this->assertSame($expected, $hn->match($url));
    }

    public function testUpdateContent(): void
    {
        $hn = new HackerNews(self::getMockClient());

        $hn->setUrl('http://0.0.0.0/hn');
        $hn->setItemContent('content');
        $this->assertSame('<p><em>Original article on <a href="http://0.0.0.0/hn">0.0.0.0</a> - content on Hacker News</em></p> readable', $hn->updateContent('readable'));
    }
}
