<?php

namespace App\Tests\Xml;

use App\Xml\SimplePieProxy;
use PHPUnit\Framework\TestCase;

class SimplePieProxyTest extends TestCase
{
    /** @var string */
    protected $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = realpath(__DIR__) . '/../../var/cache/tmp-' . md5((string) time());
    }

    protected function tearDown(): void
    {
        @rmdir($this->cacheDir);
    }

    public function testRenderBadFormat(): void
    {
        $proxy = new SimplePieProxy($this->cacheDir);

        $res = $proxy->setUrl('http://test.com');
        $feed = $proxy->init();

        $this->assertSame('App\Xml\SimplePieProxy', \get_class($res));
        $this->assertSame('SimplePie\SimplePie', \get_class($feed));
    }
}
