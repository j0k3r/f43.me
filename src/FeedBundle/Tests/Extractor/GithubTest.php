<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Github;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class GithubTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://github.com/j0k3r/f43.me', true),
            array('http://github.com/symfony/symfony', true),
            array('https://github.com/pomm-project/ModelManager', true),
            array('https://github.com/Strider-CD/strider', true),
            array('https://github.com/phpcr/phpcr.github.io/', true),
            array('https://gitlab.com/gitlab', false),
            array('https://github.com/alebcay/awesome-shell/blob/master/README.md', false),
            array('https://github.com/msporny/dna/pull/1', true),
            array('https://github.com/octocat/Hello-World/issues/212', true),
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

        $github = new Github();
        $github->setGuzzle($guzzle);
        $this->assertEquals($expected, $github->match($url));
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
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('<div>README</div>'),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $github = new Github();
        $github->setGuzzle($guzzle);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('http://www.github.com/photos/palnick');

        // consecutive calls
        $this->assertEquals('<div>README</div>', $github->getContent());
        // this one will got an empty array
        $this->assertEmpty($github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }

    public function testIssue()
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
                $this->returnValue(array(
                    'html_url' => 'http://1.1.1.1',
                    'title' => 'test',
                    'comments' => 0,
                    'created_at' => '2015-08-04T23:49:04Z',
                    'body_html' => 'body',
                    'user' => array('html_url' => 'http://2.2.2.2', 'login' => 'login'), )),
                $this->throwException(new RequestException('oops', $request))
            ));

        $github = new Github();
        $github->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $github->setLogger($logger);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('https://github.com/octocat/Hello-World/issues/212');

        // consecutive calls
        $this->assertEquals('<div><em>Issue on Github</em><h2><a href="http://1.1.1.1">test</a></h2><ul><li>by <a href="http://2.2.2.2">login</a></li><li>on 05/08/2015</li><li>0 comments</li></ul></ul>body</div>', $github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }

    public function testPR()
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
                $this->returnValue(array(
                    'base' => array('description' => 'test', 'repo' => array('html_url' => 'http://0.0.0.0', 'full_name' => 'name', 'description' => 'desc')),
                    'html_url' => 'http://1.1.1.1',
                    'title' => 'test',
                    'commits' => 0,
                    'comments' => 0,
                    'created_at' => '2015-08-04T23:49:04Z',
                    'body_html' => 'body',
                    'user' => array('html_url' => 'http://2.2.2.2', 'login' => 'login'), )),
                $this->throwException(new RequestException('oops', $request))
            ));

        $github = new Github();
        $github->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $github->setLogger($logger);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('https://github.com/msporny/dna/pull/1');

        // consecutive calls
        $this->assertEquals('<div><em>Pull request on Github</em><h2><a href="http://0.0.0.0">name</a></h2><p>desc</p><h3>PR: <a href="http://1.1.1.1">test</a></h3><ul><li>by <a href="http://2.2.2.2">login</a></li><li>on 05/08/2015</li><li>0 commits</li><li>0 comments</li></ul>body</div>', $github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }
}
