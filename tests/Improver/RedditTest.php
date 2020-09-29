<?php

namespace App\Tests\Improver;

use App\Improver\Reddit;
use App\Tests\AppTestCase;

class RedditTest extends AppTestCase
{
    public function dataMatch(): array
    {
        return [
            ['reddit.com', true],
            ['www.reddit.com', true],
            ['google.fr', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch(string $url, bool $expected): void
    {
        $reddit = new Reddit(self::getMockClient());

        $this->assertSame($expected, $reddit->match($url));
    }

    public function testUpdateUrl(): void
    {
        $reddit = new Reddit(self::getMockClient());

        $reddit->setItemContent('<table> <tr><td> <a href="http://www.reddit.com/r/mildlyinteresting/comments/2g9fj0/my_uncle_was_drafted_by_the_ny_giants_in_1943_150/"><img src="http://b.thumbs.redditmedia.com/_bZxxmoG9ENZjwsFe9CDRBA6ycl0aBpiTBwGZpzH3RA.jpg" alt="My uncle was drafted by the NY Giants in 1943. $150 per game, BYO sneakers." title="My uncle was drafted by the NY Giants in 1943. $150 per game, BYO sneakers." /></a> </td><td> submitted by <a href="http://www.reddit.com/user/drepicgames"> drepicgames </a> to <a href="http://www.reddit.com/r/mildlyinteresting/"> mildlyinteresting</a> <br/> <a href="http://i.imgur.com/dvtXt1p.jpg">[link]</a> <a href="http://www.reddit.com/r/mildlyinteresting/comments/2g9fj0/my_uncle_was_drafted_by_the_ny_giants_in_1943_150/">[254 commentaires]</a> </td></tr></table>');
        $this->assertSame('http://i.imgur.com/dvtXt1p.jpg', $reddit->updateUrl('http://0.0.0.0/content'));
    }

    public function testUpdateUrlFail(): void
    {
        $reddit = new Reddit(self::getMockClient());

        $reddit->setItemContent('empty');
        $this->assertSame('http://0.0.0.0/content', $reddit->updateUrl('http://0.0.0.0/content'));
    }

    public function testUpdateContent(): void
    {
        $reddit = new Reddit(self::getMockClient());

        $reddit->setItemContent('empty');
        $this->assertSame('empty<br/><hr/><br/>readable', $reddit->updateContent('readable'));
    }
}
