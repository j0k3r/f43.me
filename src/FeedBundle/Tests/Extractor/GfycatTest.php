<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Gfycat;
use GuzzleHttp\Exception\RequestException;

class GfycatTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://gfycat.com/SingleUntriedBudgie', true),
            array('https://gfycat.com/SingleUntriedBudgie', true),
            array('http://gfycat.com/NeatSpitefulCapeghostfrog', true),
            array('http://www.gfycat.com/NeatSpitefulCapeghostfrog', true),
            array('http://gfycat.com/', false),
            array('https://goog.co', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $gfycat = new Gfycat($guzzle);
        $this->assertEquals($expected, $gfycat->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContent()
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
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('gfyItem' => array('title' => 'my title', 'gifUrl' => 'http://0.0.0.0/img.gif'))),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $gfycat = new Gfycat($guzzle);

        // first test fail because we didn't match an url, so GfycatId isn't defined
        $this->assertEmpty($gfycat->getContent());

        $gfycat->match('http://gfycat.com/SingleUntriedBudgie');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.gif"></p></div>', $gfycat->getContent());
        // this one will got an empty array
        $this->assertEmpty($gfycat->getContent());
        // this one will catch an exception
        $this->assertEmpty($gfycat->getContent());
    }
}
