<?php

namespace App\Tests\Controller;

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

        $client->request('GET', '/feed/reddit/testItem');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testIndexBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/items');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    public function testIndex(): string
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/items');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(1, $crawler->filter('div.reveal-modal'));

        $this->assertGreaterThan(0, $crawler->filter('table.table-items tbody tr td div')->count());

        $this->assertGreaterThan(0, $preview = $crawler
            ->filter('table.table-items tbody tr td a.secondary.button.small.radius')
            ->extract(['_text', 'data-reveal-ajax'])
        );

        return $preview[0][1];
    }

    /**
     * @depends testIndex
     */
    public function testPreview(string $previewLink): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', $previewLink);

        $this->assertSame(200, $client->getResponse()->getStatusCode(), $previewLink);
        $this->assertCount(1, $crawler->filter('a.close-reveal-modal'));
        $this->assertCount(1, $crawler->filter('p span.label'));
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
    }

    public function testPreviewBadId(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/item/3456789/preview');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Item object not found', (string) $client->getResponse()->getContent());
    }

    public function testTestItem(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/testItem');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('a.close-reveal-modal'));
        $this->assertCount(1, $crawler->filter('div.section-container.tabs'));
        $this->assertCount(1, $crawler->filter('div.content[id=modal-content-internal]'));
        $this->assertCount(1, $crawler->filter('div.content[id=modal-content-external]'));
    }

    public function testTestItemBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/testItem');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    public function testPreviewItemInternal(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/blog-wildtrip/previewItem?parser=internal');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
        $this->assertGreaterThanOrEqual(2, $crawler->filter('li strong')->count());
    }

    public function testPreviewItemExternal(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/blog-wildtrip/previewItem?parser=external');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
        $this->assertGreaterThanOrEqual(2, $crawler->filter('li strong')->count());
    }

    public function testPreviewItemBadParser(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/previewItem?parser=nawak');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testPreviewItemBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/previewItem');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
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
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
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
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }
}
