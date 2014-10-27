<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Twitter;
use TwitterOAuth\Exception\TwitterException;

class TwitterTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://twitter.com/DoerteDev/statuses/50652222386027724', false),
            array('https://twitter.com/DoerteDev/statuses/506522223860277248', true),
            array('http://twitter.com/statuses/506522223860277248', true),
            array('http://twitter.com/_youhadonejob/status/522835690665807872/photo/1', true),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($twitterOAuth);
        $this->assertEquals($expected, $twitter->match($url));
    }

    public function testContent()
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->will($this->returnValue(array(
                'user' => array(
                    'name' => 'the name',
                    'screen_name' => 'the_name',
                ),
                'text' => 'my awesome tweet',
                'created_at' => 'Sun Oct 19 11:31:10 +0000 2014',
                'extended_entities' => array('media' => array(array('media_url_https' => 'http://0.0.0.0/image.jpg'))),
            )));

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $content = $twitter->getContent();

        $this->assertContains('the name', $content);
        $this->assertContains('the_name', $content);
        $this->assertContains('my awesome tweet', $content);
        $this->assertContains('Sun Oct 19', $content);
        $this->assertContains('img', $content);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContentBadResponse()
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->will($this->throwException(new TwitterException()));

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $this->assertEmpty($twitter->getContent());
    }

    public function testNoTweet()
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('http://localhost');

        $this->assertEmpty($twitter->getContent());
    }
}
