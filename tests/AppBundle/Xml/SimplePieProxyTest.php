<?php

namespace Tests\AppBundle\Xml;

use AppBundle\Xml\SimplePieProxy;
use PHPUnit\Framework\TestCase;

class SimplePieProxyTest extends TestCase
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

        $this->assertSame('AppBundle\Xml\SimplePieProxy', \get_class($res));
        $this->assertSame('SimplePie', \get_class($feed));
    }
}
