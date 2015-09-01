<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Dailymotion;
use GuzzleHttp\Exception\RequestException;

class DailymotionTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://dai.ly/xockol', true),
            array('http://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true),
            array('https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true),
            array('http://dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel', true),
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

        $dailymotion = new Dailymotion($guzzle);
        $this->assertEquals($expected, $dailymotion->match($url));
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
                $this->returnValue(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $dailymotion = new Dailymotion($guzzle);

        // first test fail because we didn't match an url, so DailymotionUrl isn't defined
        $this->assertEmpty($dailymotion->getContent());

        $dailymotion->match('https://www.dailymotion.com/video/xockol_planete-des-hommes-partie-1-2_travel');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $dailymotion->getContent());
        // this one will got an empty array
        $this->assertEmpty($dailymotion->getContent());
        // this one will catch an exception
        $this->assertEmpty($dailymotion->getContent());
    }
}
