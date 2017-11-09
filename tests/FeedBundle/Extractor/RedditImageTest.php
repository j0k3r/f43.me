<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\RedditImage;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class RedditImageTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93', true],
            ['http://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93', true],
            ['https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8', true],
            ['https://i.redd.it/zldsan2mcq0x.jpg', true],
            ['http://i.redd.it/zldsan2mcq0x.jpg', true],
            ['https://i.redd.it/doyo06rfeo0x.gif', true],
            ['http://i.redd.it/', false],
            ['http://i.reddituploads.com/', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $redditImage = new RedditImage();
        $this->assertSame($expected, $redditImage->match($url));
    }

    public function testContent()
    {
        $redditImage = new RedditImage();

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $redditImage->setLogger($logger);

        // first test fail because we didn't match an url, so reddituploadsUrl isn't defined
        $this->assertEmpty($redditImage->getContent());

        $redditImage->match('https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93');

        $this->assertSame('<div><p><img src="https://i.reddituploads.com/21fc8e0b2984423e84fd59fbc58024c8?fit=max&h=1536&w=1536&s=9e3c0fa6d46a642c42eace91833cad93"></p></div>', $redditImage->getContent());
    }
}
