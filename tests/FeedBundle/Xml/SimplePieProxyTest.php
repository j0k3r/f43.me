<?php

namespace Tests\FeedBundle\Xml;

use Api43\FeedBundle\Xml\SimplePieProxy;

class SimplePieProxyTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDir;

    protected function setUp()
    {
        $this->cacheDir = __DIR__ . '/../../../../../app/cache/tmp-' . md5(time());
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

        $this->assertSame('Api43\FeedBundle\Xml\SimplePieProxy', get_class($res));
        $this->assertSame('SimplePie', get_class($feed));
    }
}
