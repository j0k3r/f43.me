<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Camplus;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class CamplusTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://campl.us/rL9Q', true),
            array('http://campl.us/jQKwkTKxLHG', true),
            array('https://campl.us/rL9Q', true),
            array('https://campl.us/hvGw', true),
            array('http://campl.us/ozu1', true),
            array('http://pics.campl.us/f/6/6283.e61ef28b1535e624f30e4ef96fcd3f52.jpg', false),
            array('http://github.com/symfony/symfony', false),
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

        $camplus = new Camplus();
        $camplus->setGuzzle($guzzle);
        $this->assertEquals($expected, $camplus->match($url));
    }

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
            ->will($this->returnValue(array(
                'page' => array('tweet' => array(
                    'id' => '123',
                    'username' => 'j0k',
                    'realname' => 'j0k',
                    'text' => 'yay',
                )), 'pictures' => array(array(
                    '480px' => 'http://0.0.0.0/youpi.jpg',
                )),
            )));

        $camplus = new Camplus();
        $camplus->setGuzzle($guzzle);

        // first test fail because we didn't match an url, so camplusId isn't defined
        $this->assertEmpty($camplus->getContent());

        $camplus->match('http://campl.us/rL9Q');

        $this->assertContains('<h2>Photo from j0k</h2>', $camplus->getContent());
        $this->assertContains('<p><img src="http://0.0.0.0/youpi.jpg" /></p>', $camplus->getContent());
    }

    public function testContentWithException()
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
            ->will($this->throwException(new RequestException('oops', $request)));

        $camplus = new Camplus();
        $camplus->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $camplus->setLogger($logger);

        $camplus->match('http://campl.us/rL9Q');

        // this one will catch an exception
        $this->assertEmpty($camplus->getContent());

        $this->assertTrue($logHandler->hasWarning('Camplus extract failed for: rL9Q'), 'Warning message matched');
    }
}
