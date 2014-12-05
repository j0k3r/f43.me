<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\External;
use Guzzle\Http\Exception\RequestException;

class ExternalTest extends \PHPUnit_Framework_TestCase
{
    public function testParseEmpty()
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

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }

    public function testParse()
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
            ->will($this->returnValue(array('content' => '<div></div>', 'url' => 'http://1.1.1.1/content')));

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEquals('<div></div>', $external->parse('http://0.0.0.0/content'));
    }

    public function testParseException()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->throwException(new RequestException()));

        $external = new External($guzzle, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }
}
