<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\External;
use GuzzleHttp\Exception\RequestException;

class ExternalTest extends \PHPUnit_Framework_TestCase
{
    public function testParseEmpty()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->returnValue(array()));

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }

    public function testParse()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->returnValue(array('content' => '<div></div>', 'url' => 'http://1.1.1.1/content')));

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEquals('<div></div>', $external->parse('http://0.0.0.0/content'));
    }

    public function testParseException()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->throwException(new RequestException('oops', $request)));

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }
}
