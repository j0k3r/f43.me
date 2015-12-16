<?php

namespace Api43\FeedBundle\Tests\Xml;

use Api43\FeedBundle\Xml\SimplePieProxy;

class SimplePieProxyTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDir;

    protected function setUp()
    {
        $this->cacheDir = __DIR__.'/../../../../../app/cache/tmp-'.md5(time());
    }

    protected function tearDown()
    {
        @rmdir($this->cacheDir);
    }

    public function testRenderBadFormat()
    {
        $proxy = new SimplePieProxy($this->cacheDir);

        $res = $proxy->setUrl('http://test.com');
        $feed = $proxy->init();

        $this->assertEquals('Api43\FeedBundle\Xml\SimplePieProxy', get_class($res));
        $this->assertEquals('SimplePie', get_class($feed));
    }
}
