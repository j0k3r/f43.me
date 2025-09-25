<?php

namespace App\Tests\Xml;

use App\Xml\SimplePieProxy;
use PHPUnit\Framework\TestCase;
use SimplePie\SimplePie;

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

        $this->assertSame(SimplePieProxy::class, $res::class);
        $this->assertSame(SimplePie::class, $feed::class);
    }
}
