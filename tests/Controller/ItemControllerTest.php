<?php

namespace App\Tests\Controller;

use App\Content\Extractor;
use App\Xml\SimplePieProxy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use SimplePie\Item;
use SimplePie\SimplePie;

class ItemControllerTest extends FeedWebTestCase
{
    public function testUnAuthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/feed/reddit/items');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/reddit/previewItem');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testIndexBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/items');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('object not found', (string) $client->getResponse()->getContent());
    }

    public function testIndex(): string
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/items');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h3'));
        $this->assertCount(1, $crawler->filter('dialog'));

        $this->assertGreaterThan(0, $crawler->filter('table tbody tr td div')->count());

        $this->assertGreaterThan(0, $preview = $crawler
            ->filter('table tbody tr td button.open-modal')
            ->extract(['_text', 'data-url'])
        );

        return $preview[0][1];
    }

    #[Depends('testIndex')]
    public function testPreview(string $previewLink): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', $previewLink);

        $this->assertSame(200, $client->getResponse()->getStatusCode(), $previewLink);
        $this->assertCount(1, $crawler->filter('p mark'));
        $this->assertCount(1, $crawler->filter('ul li a'));
    }

    public function testPreviewBadId(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/item/3456789/preview');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('object not found', (string) $client->getResponse()->getContent());
    }

    public static function previewUrls(): iterable
    {
        yield 'default' => [''];
        yield 'legacy internal link' => ['?parser=internal'];
        yield 'legacy external link' => ['?parser=external'];
        yield 'parser parameter is ignored' => ['?parser=nawak'];
    }

    #[DataProvider('previewUrls')]
    public function testPreviewItem(string $query): void
    {
        $client = static::getAuthorizedClient();
        $client->disableReboot();
        $item = $this->createStub(Item::class);
        $item->method('get_permalink')->willReturn('https://preview.example/article');
        $item->method('get_description')->willReturn('Feed description');
        $item->method('get_title')->willReturn('Preview title');
        $rss = $this->createStub(SimplePie::class);
        $rss->method('get_item')->willReturn($item);
        $simplePie = $this->createStub(SimplePieProxy::class);
        $simplePie->method('setUrl')->willReturnSelf();
        $simplePie->method('init')->willReturn($rss);
        static::getContainer()->set(SimplePieProxy::class, $simplePie);

        $extractor = $this->createMock(Extractor::class);
        $extractor->expects($this->once())->method('init')->willReturnSelf();
        $extractor->expects($this->once())->method('parseContent')->with('https://preview.example/article', 'Feed description')->willReturnSelf();
        $extractor->content = '<p>Graby preview marker</p>';
        $extractor->url = 'https://preview.example/article';
        static::getContainer()->set(Extractor::class, $extractor);

        $crawler = $client->request('GET', '/feed/blog-wildtrip/previewItem' . $query);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Graby preview marker', $crawler->text());
    }

    public function testPreviewItemBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/previewItem');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('object not found', (string) $client->getResponse()->getContent());
    }

    public function testDeleteAll(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/items');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('hackernews', (string) $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('p.success')->extract(['_text']));
        $this->assertStringContainsString('items deleted!', $alert[0]);
    }

    public function testDeleteAllFormInvalid(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/hackernews/items/deleteAll');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('hackernews', (string) $client->getResponse()->headers->get('location'));
    }

    public function testDeleteAllBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/items/deleteAll');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('object not found', (string) $client->getResponse()->getContent());
    }
}
