<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Tumblr;
use Guzzle\Http\Exception\RequestException;

class TumblrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project', true),
            array('http://google.com', false),
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

        $response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnValue('test'));

        $tumblr = new Tumblr($guzzle, 'apikey');
        $this->assertEquals($expected, $tumblr->match($url));
    }

    public function testMatchNotTumblrUser()
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
            ->method('getHeader')
            ->will($this->returnValue(null));

        $tumblr = new Tumblr($guzzle, 'apikey');
        $this->assertFalse($tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project'));
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
            ->method('getHeader')
            ->will($this->returnValue('test'));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('response' => array('posts' => array(array('body' => '<div>content</div>'))))),
                $this->returnValue(array()),
                $this->throwException(new RequestException())
            ));

        $tumblr = new Tumblr($guzzle, 'apikey');

        // first test fail because we didn't match an url, so TumblrId isn't defined
        $this->assertFalse($tumblr->getContent());

        $tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project');

        // consecutive calls
        $this->assertEquals('<div>content</div>', $tumblr->getContent());
        $this->assertFalse($tumblr->getContent());
        $this->assertFalse($tumblr->getContent());
    }
}
