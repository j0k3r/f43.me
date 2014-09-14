<?php

namespace j0k3r\FeedBundle\Tests\Improver;

use j0k3r\FeedBundle\Improver\Nothing;
use Guzzle\Http\Exception\RequestException;

class NothingTest extends \PHPUnit_Framework_TestCase
{
    public function dataUpdateUrl()
    {
        return array(
            array('http://modmyi.com/?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_source=feedburner', 'http://modmyi.com/'),
        );
    }

    /**
     * @dataProvider dataUpdateUrl
     */
    public function testUpdateUrl($url, $expected)
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
            ->method('getEffectiveUrl')
            ->will($this->returnValue($expected));

        $nothing = new Nothing($guzzle);
        $this->assertEquals($expected, $nothing->updateUrl($url));
    }

    public function testUpdateUrlFail()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->throwException(new RequestException()));

        $nothing = new Nothing($guzzle);
        $this->assertEquals('http://0.0.0.0/content', $nothing->updateUrl('http://0.0.0.0/content'));
    }
}
