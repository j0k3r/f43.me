<?php

namespace Tests\FeedBundle\Improver;

use Api43\FeedBundle\Improver\HackerNews;
use GuzzleHttp\Client;

class HackerNewsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['news.ycombinator.com', true],
            ['google.fr', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $hn = new HackerNews(new Client());
        $this->assertEquals($expected, $hn->match($url));
    }

    public function testUpdateContent()
    {
        $hn = new HackerNews(new Client());
        $hn->setUrl('http://0.0.0.0/hn');
        $hn->setItemContent('content');
        $this->assertEquals('<p><em>Original article on <a href="http://0.0.0.0/hn">0.0.0.0</a> - content on Hacker News</em></p> readable', $hn->updateContent('readable'));
    }
}
