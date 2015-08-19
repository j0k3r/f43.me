<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Github;
use Guzzle\Http\Exception\RequestException;

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
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $github = new Github($guzzle);
        $this->assertEquals($expected, $github->match($url));
    }

    public function testContent()
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
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('<div>README</div>'),
                $this->returnValue(''),
                $this->throwException(new RequestException())
            ));

        $github = new Github($guzzle);

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
}
