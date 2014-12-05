<?php

namespace Api43\FeedBundle\Tests\Services;

use Api43\FeedBundle\Services\RssRender;

class RssRenderTest extends \PHPUnit_Framework_TestCase
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
            ->setMethods(array('findByFeed'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->dm->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('Api43FeedBundle:FeedItem'))
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('findByFeed')
            ->willReturn(array());
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
        $feed = $this->getMock('Api43\FeedBundle\Document\Feed');

        $render = new RssRender('toto', $this->dm, $this->router);
        $render->render($feed);
    }

    public function testRenderAtom()
    {
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->setMethods(array('getFormatter'))
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $render = new RssRender('tata', $this->dm, $this->router);
        $content = $render->render($feed);

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
            ->setMethods(array('getFormatter'))
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('rss');

        $render = new RssRender('tata', $this->dm, $this->router);
        $content = $render->render($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));
    }
}
