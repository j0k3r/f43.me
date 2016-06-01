<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Reddituploads;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class ReddituploadsTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93', true),
            array('http://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93', true),
            array('https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8', true),
            array('http://i.reddituploads.com/', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $reddituploads = new Reddituploads();
        $this->assertEquals($expected, $reddituploads->match($url));
    }

    public function testContent()
    {
        $reddituploads = new Reddituploads();

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $reddituploads->setLogger($logger);

        // first test fail because we didn't match an url, so reddituploadsUrl isn't defined
        $this->assertEmpty($reddituploads->getContent());

        $reddituploads->match('https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93');

        $this->assertEquals('<div><p><img src="https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93"></p></div>', $reddituploads->getContent());
    }
}
