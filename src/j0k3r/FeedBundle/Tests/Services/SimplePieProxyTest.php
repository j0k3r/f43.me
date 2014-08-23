<?php

namespace j0k3r\FeedBundle\Tests\Services;

use j0k3r\FeedBundle\Services\SimplePieProxy;

class SimplePieProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testRenderBadFormat()
    {
        $proxy = new SimplePieProxy(md5(time()));

        $res = $proxy->setUrl('http://test.com');
        $feed = $proxy->init();

        $this->assertEquals('j0k3r\FeedBundle\Services\SimplePieProxy', get_class($res));
        $this->assertEquals('SimplePie', get_class($feed));
    }
}
