<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Instagram;
use Guzzle\Http\Exception\RequestException;

class InstagramTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://instagram.com/p/2N5UHfChAZ/', true),
            array('http://instagr.am/p/fA9uwTtkSN/', true),
            array('https://instagram.com', false),
            array('https://goog.co', false),
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

        $instagram = new Instagram($guzzle);
        $this->assertEquals($expected, $instagram->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
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
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException())
            ));

        $instagram = new Instagram($guzzle);

        // first test fail because we didn't match an url, so InstagramId isn't defined
        $this->assertEmpty($instagram->getContent());

        $instagram->match('https://instagram.com/p/2N5UHfChAZ/');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $instagram->getContent());
        // this one will got an empty array
        $this->assertEmpty($instagram->getContent());
        // this one will catch an exception
        $this->assertEmpty($instagram->getContent());
    }
}
