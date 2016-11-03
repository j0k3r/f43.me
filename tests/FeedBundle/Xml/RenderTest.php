<?php

namespace Tests\FeedBundle\Xml;

use Api43\FeedBundle\Xml\Render;

class RenderTest extends \PHPUnit_Framework_TestCase
{
    private $dm;
    private $router;

    protected function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ODM\DocumentRepository')
            ->setMethods(['findByFeed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dm->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('Api43FeedBundle:FeedItem'))
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('findByFeed')
            ->willReturn([]);
    }

    protected function tearDown()
    {
        unset($this->dm, $this->router);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRenderBadFormat()
    {
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->disableOriginalConstructor()
            ->getMock();

        $render = new Render('toto', $this->dm, $this->router);
        $render->doRender($feed);
    }

    public function testRenderAtom()
    {
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->setMethods(['getFormatter'])
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $render = new Render('tata', $this->dm, $this->router);
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
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->setMethods(['getFormatter'])
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('rss');

        $render = new Render('tata', $this->dm, $this->router);
        $content = $render->doRender($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));
    }
}
