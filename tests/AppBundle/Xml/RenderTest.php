<?php

namespace Tests\AppBundle\Xml;

use AppBundle\Xml\Render;
use PHPUnit\Framework\TestCase;

class RenderTest extends TestCase
{
    private $repo;
    private $router;

    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = $this->getMockBuilder('AppBundle\Repository\ItemRepository')
            ->setMethods(['findByFeed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo->expects($this->any())
            ->method('findByFeed')
            ->willReturn([]);
    }

    protected function tearDown(): void
    {
        unset($this->repo, $this->router);
    }

    public function testRenderBadFormat()
    {
        $this->expectException(\InvalidArgumentException::class);

        $feed = $this->getMockBuilder('AppBundle\Entity\Feed')
            ->disableOriginalConstructor()
            ->getMock();

        $render = new Render('toto', $this->repo, $this->router);
        $render->doRender($feed);
    }

    public function testRenderAtom()
    {
        $feed = $this->getMockBuilder('AppBundle\Entity\Feed')
            ->setMethods(['getFormatter'])
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $render = new Render('tata', $this->repo, $this->router);
        $content = $render->doRender($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);
    }

    public function testRenderRss()
    {
        $feed = $this->getMockBuilder('AppBundle\Entity\Feed')
            ->setMethods(['getFormatter'])
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('rss');

        $render = new Render('tata', $this->repo, $this->router);
        $content = $render->doRender($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));
    }
}
