<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Twitter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use TwitterOAuth\Exception\TwitterException;

class TwitterTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://twitter.com/DoerteDev/statuses/50652222386027724', false],
            ['https://twitter.com/DoerteDev/statuses/506522223860277248', true],
            ['http://twitter.com/statuses/506522223860277248', true],
            ['http://twitter.com/_youhadonejob/status/522835690665807872/photo/1', true],
            ['https://mobile.twitter.com/kcimc/status/638877262092337152/photo/1', true],
            ['http://user@:80', false],
        ];
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
            ->will($this->returnValue([
                'user' => [
                    'name'        => 'the name',
                    'screen_name' => 'the_name',
                ],
                'text'              => 'my awesome tweet',
                'created_at'        => 'Sun Oct 19 11:31:10 +0000 2014',
                'extended_entities' => ['media' => [['media_url_https' => 'http://0.0.0.0/image.jpg']]],
            ]));

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $content = $twitter->getContent();

        $this->assertContains('the name', $content);
        $this->assertContains('the_name', $content);
        $this->assertContains('my awesome tweet', $content);
        $this->assertContains('Sun Oct 19', $content);
        $this->assertContains('img', $content);
    }

    public function testContentNoEntities()
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitterOAuth->expects($this->once())
            ->method('get')
            ->will($this->returnValue([
                'user' => [
                    'name'        => 'the name',
                    'screen_name' => 'the_name',
                ],
                'text'       => 'my awesome tweet',
                'created_at' => 'Sun Oct 19 11:31:10 +0000 2014',
            ]));

        $twitter = new Twitter($twitterOAuth);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $content = $twitter->getContent();

        $this->assertContains('the name', $content);
        $this->assertContains('the_name', $content);
        $this->assertContains('my awesome tweet', $content);
        $this->assertContains('Sun Oct 19', $content);
    }

    public function testContentBadResponse()
    {
        $twitterOAuth = $this->getMockBuilder('TwitterOAuth\TwitterOAuth')
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
