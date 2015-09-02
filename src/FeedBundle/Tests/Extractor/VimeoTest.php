<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Vimeo;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class VimeoTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://vimeo.com/116087204', true),
            array('http://vimeo.com/116087204', true),
            array('https://vimeo.com/channels/staffpicks/130365792', true),
            array('https://vimeo.com/groups/motion/videos/131034832', true),
            array('https://goog.co', false),
            array('http://user@:80', false),
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

        $vimeo = new Vimeo();
        $vimeo->setGuzzle($guzzle);
        $this->assertEquals($expected, $vimeo->match($url));
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
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('title' => 'my title', 'description' => 'my description', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $vimeo = new Vimeo();
        $vimeo->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $vimeo->setLogger($logger);

        // first test fail because we didn't match an url, so VimeoUrl isn't defined
        $this->assertEmpty($vimeo->getContent());

        $vimeo->match('https://vimeo.com/groups/motion/videos/131034832');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vimeo->getContent());
        // this one will got an empty array
        $this->assertEmpty($vimeo->getContent());
        // this one will catch an exception
        $this->assertEmpty($vimeo->getContent());

        $this->assertTrue($logHandler->hasWarning('Vimeo extract failed for: https://vimeo.com/groups/motion/videos/131034832'), 'Warning message matched');
    }
}
