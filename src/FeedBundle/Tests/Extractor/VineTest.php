<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Vine;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class VineTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://vine.co/v/e7V1hLdF1bP', true),
            array('http://vine.co/v/e7V1hLdF1bP', true),
            array('https://vine.co', false),
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

        $vine = new Vine();
        $vine->setGuzzle($guzzle);
        $this->assertEquals($expected, $vine->match($url));
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

        $vine = new Vine();
        $vine->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $vine->setLogger($logger);

        // first test fail because we didn't match an url, so VineId isn't defined
        $this->assertEmpty($vine->getContent());

        $vine->match('https://vine.co/v/e7V1hLdF1bP');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $vine->getContent());
        // this one will got an empty array
        $this->assertEmpty($vine->getContent());
        // this one will catch an exception
        $this->assertEmpty($vine->getContent());

        $this->assertTrue($logHandler->hasWarning('Vine extract failed for: e7V1hLdF1bP'), 'Warning message matched');
    }
}
