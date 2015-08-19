<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\HackerNews;
use Guzzle\Http\Exception\RequestException;

class HackerNewsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://news.ycombinator.com/item?id=10074364', true, array('text' => 'toto', 'type' => 'story')),
            array('http://news.ycombinator.com/item?id=10074364', true, array('text' => 'toto', 'type' => 'job')),
            // comment
            array('http://news.ycombinator.com/item?id=10077812', false, array('text' => 'toto', 'type' => 'comment')),
            // pollopt
            array('http://news.ycombinator.com/item?id=160705', false, array('text' => 'toto', 'type' => 'pollopt')),
            array('https://goog.co', false),
            array('http://news.ycombinator.com/item?id=rtyui', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected, $valueReturned = null)
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
            ->will($this->returnValue($valueReturned));

        $hn = new HackerNews($guzzle);
        $this->assertEquals($expected, $hn->match($url));
    }

    public function testMatchGuzzleFail()
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

        $hn = new HackerNews($guzzle);
        $this->assertEquals(false, $hn->match('http://news.ycombinator.com/item?id=10074364'));
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
            ->will($this->returnValue(array('text' => 'toto', 'type' => 'story')));

        $hn = new HackerNews($guzzle);

        // first test fail because we didn't match an url, so HackerNewsId isn't defined
        $this->assertEmpty($hn->getContent());

        $hn->match('http://news.ycombinator.com/item?id=10074364');
        $this->assertEquals('<p>toto</p>', $hn->getContent());
    }
}
