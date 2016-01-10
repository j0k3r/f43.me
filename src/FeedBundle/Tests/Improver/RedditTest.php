<?php

namespace Api43\FeedBundle\Tests\Improver;

use Api43\FeedBundle\Improver\Reddit;
use GuzzleHttp\Client;

class RedditTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['reddit.com', true],
            ['google.fr', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $reddit = new Reddit(new Client());
        $this->assertEquals($expected, $reddit->match($url));
    }

    public function testUpdateUrl()
    {
        $reddit = new Reddit(new Client());
        $reddit->setItemContent('<table> <tr><td> <a href="http://www.reddit.com/r/mildlyinteresting/comments/2g9fj0/my_uncle_was_drafted_by_the_ny_giants_in_1943_150/"><img src="http://b.thumbs.redditmedia.com/_bZxxmoG9ENZjwsFe9CDRBA6ycl0aBpiTBwGZpzH3RA.jpg" alt="My uncle was drafted by the NY Giants in 1943. $150 per game, BYO sneakers." title="My uncle was drafted by the NY Giants in 1943. $150 per game, BYO sneakers." /></a> </td><td> submitted by <a href="http://www.reddit.com/user/drepicgames"> drepicgames </a> to <a href="http://www.reddit.com/r/mildlyinteresting/"> mildlyinteresting</a> <br/> <a href="http://i.imgur.com/dvtXt1p.jpg">[link]</a> <a href="http://www.reddit.com/r/mildlyinteresting/comments/2g9fj0/my_uncle_was_drafted_by_the_ny_giants_in_1943_150/">[254 commentaires]</a> </td></tr></table>');
        $this->assertEquals('http://i.imgur.com/dvtXt1p.jpg', $reddit->updateUrl('http://0.0.0.0/content'));
    }

    public function testUpdateUrlFail()
    {
        $reddit = new Reddit(new Client());
        $reddit->setItemContent('empty');
        $this->assertEquals('http://0.0.0.0/content', $reddit->updateUrl('http://0.0.0.0/content'));
    }

    public function testUpdateContent()
    {
        $reddit = new Reddit(new Client());
        $reddit->setItemContent('empty');
        $this->assertEquals('empty<br/><hr/><br/>readable', $reddit->updateContent('readable'));
    }
}
