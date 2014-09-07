<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Twitter;

class TwitterTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://twitter.com/DoerteDev/statuses/50652222386027724', false),
            array('https://twitter.com/DoerteDev/statuses/506522223860277248', true),
            array('http://twitter.com/statuses/506522223860277248', true),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($guzzle);
        $this->assertEquals($expected, $twitter->match($url));
        $this->assertEquals($url, $twitter->getUrl());
    }

    public function testContent()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->returnValue(array('html' => '<div></div>')));

        $twitter = new Twitter($guzzle);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $this->assertEquals('<div></div>', $twitter->getContent());
    }

    public function testContentBadResponse()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->returnValue(array()));

        $twitter = new Twitter($guzzle);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $this->assertFalse($twitter->getContent());
    }

    public function testContentBadResponse2()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->throwException(new \Guzzle\Http\Exception\RequestException));

        $twitter = new Twitter($guzzle);
        $twitter->match('https://twitter.com/DoerteDev/statuses/506522223860277248');

        $this->assertFalse($twitter->getContent());
    }

    public function testNoTweet()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $twitter = new Twitter($guzzle);
        $twitter->match('http://localhost');

        $this->assertFalse($twitter->getContent());
    }
}
