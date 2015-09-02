<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Instagram;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class InstagramTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://instagram.com/p/2N5UHfChAZ/', true),
            array('http://instagr.am/p/fA9uwTtkSN/', true),
            array('https://instagram.com/p/4FKIIdJ9LM/?taken-by=j0k', true),
            array('https://instagram.com', false),
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

        $instagram = new Instagram();
        $instagram->setGuzzle($guzzle);
        $this->assertEquals($expected, $instagram->match($url));
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
                $this->returnValue(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $instagram = new Instagram();
        $instagram->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $instagram->setLogger($logger);

        // first test fail because we didn't match an url, so InstagramId isn't defined
        $this->assertEmpty($instagram->getContent());

        $instagram->match('https://instagram.com/p/2N5UHfChAZ/');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $instagram->getContent());
        // this one will got an empty array
        $this->assertEmpty($instagram->getContent());
        // this one will catch an exception
        $this->assertEmpty($instagram->getContent());

        $this->assertTrue($logHandler->hasWarning('Instagram extract failed for: https://instagram.com/p/2N5UHfChAZ/'), 'Warning message matched');
    }
}
