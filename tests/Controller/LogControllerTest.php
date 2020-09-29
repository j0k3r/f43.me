<?php

namespace App\Tests\Controller;

class LogControllerTest extends FeedWebTestCase
{
    public function testUnAuthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/feed/reddit/logs');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('POST', '/feed/reddit/logs/deleteAll');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/logs');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testLogs(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/logs');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertGreaterThan(0, $crawler->filter('table.table-feedlogs tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-feedlogs tr td.items-count')->extract(['_text']));

        foreach ($items as $item) {
            $this->assertGreaterThan(0, $item);
        }
    }

    public function testLogsFeedNotExists(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/toto/logs');
        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    public function testLogsFeed(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/reddit/logs');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('a.secondary.button.radius.small'));
        $this->assertCount(1, $crawler->filter('button.alert.button.radius.small'));
        $this->assertGreaterThan(0, $crawler->filter('table.table-feedlogs tbody tr td')->count());
    }

    public function testDeleteAll(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/reddit/logs');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('reddit', (string) $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertStringContainsString('logs deleted!', $alert[0]);
    }

    public function testDeleteAllFormInvalid(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/reddit/logs/deleteAll');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('reddit', (string) $client->getResponse()->headers->get('location'));
    }

    public function testDeleteAllBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/logs/deleteAll');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }
}
