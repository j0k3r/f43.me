<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\Deviantart;
use Guzzle\Http\Exception\RequestException;

class DeviantartTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002', true),
            array('http://lndi.deviantart.com/art/Maybe-we-ll-get-luck-and-we-ll-both-live-again-494273522', true),
            array('http://fav.me/d7gab7p', true),
            array('http://sta.sh/06x5m3s9bms', true),

            array('http://www.deviantart.com/browse/all/', false),
            array('http://nixielupus.deviantart.com/', false),
            array('http://sta.sh/', false),
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

        $deviantart = new Deviantart($guzzle);
        $this->assertEquals($expected, $deviantart->match($url));
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
            ->will($this->returnValue(array(
                'url' => 'http://0.0.0.0/youpi.jpg',
                'title' => 'youpi',
                'author_url' => 'http://youpi.0.0.0.0',
                'author_name' => 'youpi',
                'category' => 'Pic > Landscape',
                'html' => '<iframe></iframe>',
            )));

        $deviantart = new Deviantart($guzzle);

        // first test fail because we didn't match an url, so DeviantartId isn't defined
        $this->assertEmpty($deviantart->getContent());

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        // consecutive calls
        $this->assertContains('<img src="http://0.0.0.0/youpi.jpg" />', $deviantart->getContent());
        $this->assertContains('<p>By <a href="http://youpi.0.0.0.0">@youpi</a></p>', $deviantart->getContent());
        $this->assertContains('<iframe></iframe>', $deviantart->getContent());
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContentWithException()
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
            ->will($this->throwException(new RequestException()));

        $deviantart = new Deviantart($guzzle);

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        // this one will catch an exception
        $this->assertEmpty($deviantart->getContent());
    }
}
