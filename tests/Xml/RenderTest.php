<?php

namespace App\Tests\Xml;

use App\Entity\Feed;
use App\Repository\ItemRepository;
use App\Xml\Render;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RenderTest extends TestCase
{
    private ItemRepository $repo;
    private Router $router;

    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturn('https://fake.url');

        $this->repo = $this->getMockBuilder(ItemRepository::class)
            ->onlyMethods(['findByFeed'])
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

    public function testRenderBadFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $feed = new Feed();
        $feed->setName('');
        $feed->setId(66);
        $feed->setSortBy('created_at');

        $render = new Render('toto', $this->repo, $this->router);
        $render->doRender($feed);
    }

    public function testRenderAtom(): void
    {
        $feed = new Feed();
        $feed->setId(66);
        $feed->setSortBy('created_at');
        $feed->setFormatter('atom');
        $feed->setName('');

        $render = new Render('tata', $this->repo, $this->router);
        $content = $render->doRender($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);
    }

    public function testRenderRss(): void
    {
        $feed = new Feed();
        $feed->setId(66);
        $feed->setSortBy('created_at');
        $feed->setFormatter('rss');
        $feed->setName('');

        $render = new Render('tata', $this->repo, $this->router);
        $content = $render->doRender($feed);

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));
    }
}
