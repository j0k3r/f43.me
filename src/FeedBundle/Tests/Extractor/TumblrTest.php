<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Tumblr;
use Guzzle\Http\Exception\RequestException;

class TumblrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project', true),
            array('http://thecodinglove.com/post/100483712123/monday-morning', true),
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

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testMatchFailRequest()
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
            ->will($this->throwException(new RequestException()));

        $tumblr = new Tumblr($guzzle, 'apikey');
        $tumblr->match('http://lesjoiesducode.fr/post/125256171232/quand-après-une-heure-de-dev-je-teste-mon-code-en');
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
        $this->assertEmpty($tumblr->getContent());

        $tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project');

        // consecutive calls
        $this->assertEquals('<div>content</div>', $tumblr->getContent());
        // this one will got an empty array
        $this->assertEmpty($tumblr->getContent());
        // this one will catch an exception
        $this->assertEmpty($tumblr->getContent());
    }
}
