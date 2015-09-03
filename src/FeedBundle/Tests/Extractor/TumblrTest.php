<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Tumblr;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class TumblrTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project', true),
            array('http://thecodinglove.com/post/100483712123/monday-morning', true),
            array('http://google.com', false),
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
            ->method('getHeader')
            ->will($this->returnValue('test'));

        $tumblr = new Tumblr('apikey');
        $tumblr->setGuzzle($guzzle);
        $this->assertEquals($expected, $tumblr->match($url));
    }

    public function testMatchFailRequest()
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
            ->will($this->throwException(new RequestException('oops', $request)));

        $tumblr = new Tumblr('apikey');
        $tumblr->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $tumblr->setLogger($logger);

        $tumblr->match('http://lesjoiesducode.fr/post/125256171232/quand-après-une-heure-de-dev-je-teste-mon-code-en');

        $this->assertTrue($logHandler->hasWarning('Tumblr extract failed for: http://lesjoiesducode.fr/post/125256171232/quand-après-une-heure-de-dev-je-teste-mon-code-en'), 'Warning message matched');
    }

    public function testMatchNotTumblrUser()
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
            ->method('getHeader')
            ->will($this->returnValue(null));

        $tumblr = new Tumblr('apikey');
        $tumblr->setGuzzle($guzzle);
        $this->assertFalse($tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project'));
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
            ->method('getHeader')
            ->will($this->returnValue('test'));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('response' => array('posts' => array(array('body' => '<div>content</div>'))))),
                $this->returnValue(array()),
                $this->throwException(new RequestException('oops', $request))
            ));

        $tumblr = new Tumblr('apikey');
        $tumblr->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $tumblr->setLogger($logger);

        // first test fail because we didn't match an url, so TumblrId isn't defined
        $this->assertEmpty($tumblr->getContent());

        $tumblr->match('http://thecodinglove.com/post/96365413702/client-giving-us-his-feedback-on-his-new-project');

        // consecutive calls
        $this->assertEquals('<div>content</div>', $tumblr->getContent());
        // this one will got an empty array
        $this->assertEmpty($tumblr->getContent());
        // this one will catch an exception
        $this->assertEmpty($tumblr->getContent());

        $this->assertTrue($logHandler->hasWarning('Tumblr extract failed for: 96365413702 & thecodinglove.com'), 'Warning message matched');
    }
}
