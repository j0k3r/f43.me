<?php

namespace Api43\FeedBundle\Tests\Improver;

use Api43\FeedBundle\Improver\Nothing;
use GuzzleHttp\Exception\RequestException;

class NothingTest extends \PHPUnit_Framework_TestCase
{
    public function dataUpdateUrl()
    {
        return array(
            array('http://modmyi.com/?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_source=feedburner', 'http://modmyi.com/'),
            array('http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+ac%2Factualites+%28AlloCine+-+RSS+News%3A+Cinema+%26+Series%29', 'http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html'),
        );
    }

    /**
     * @dataProvider dataUpdateUrl
     */
    public function testUpdateUrl($url, $expected)
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
            ->method('getEffectiveUrl')
            ->will($this->returnValue($expected));

        $nothing = new Nothing($guzzle);
        $this->assertEquals($expected, $nothing->updateUrl($url));
    }

    public function testUpdateUrlFail()
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

        $nothing = new Nothing($guzzle);
        $this->assertEquals('http://0.0.0.0/content?not-changed', $nothing->updateUrl('http://0.0.0.0/content'));
    }
}
