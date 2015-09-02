<?php

namespace Api43\FeedBundle\Tests\Improver;

use Api43\FeedBundle\Improver\HackerNews;

class HackerNewsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('news.ycombinator.com', true),
            array('google.fr', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $hn = new HackerNews($guzzle);
        $this->assertEquals($expected, $hn->match($url));
    }

    public function testUpdateContent()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $hn = new HackerNews($guzzle);
        $hn->setUrl('http://0.0.0.0/hn');
        $hn->setItemContent('content');
        $this->assertEquals('<p><em>Original article on <a href="http://0.0.0.0/hn">0.0.0.0</a> - content on Hacker News</em></p> readable', $hn->updateContent('readable'));
    }
}
