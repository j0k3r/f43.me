<?php

namespace App\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;

class FeedControllerTest extends FeedWebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("f43.me")')->count());

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h2.subheader'));
        $this->assertGreaterThan(0, $crawler->filter('tr td img.favicon')->count());

        // private field won't show up
        $this->assertStringNotContainsString('Bonjour', (string) $client->getResponse()->getContent());
    }

    public function testUnAuthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/dashboard');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feeds');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/new');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/reddit/edit');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testDashboard(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/dashboard');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h3.subheader'));
        $this->assertCount(1, $crawler->filter('ul.left'));
        $this->assertCount(9, $crawler->filter('ul.left li'));
        $this->assertCount(1, $crawler->filter('ul.left li.active'));
        $this->assertCount(1, $logout = $crawler->filter('ul.right li.has-form a.alert')->extract(['_text']));
        $this->assertSame('Logout', $logout[0]);
        $this->assertGreaterThan(0, $crawler->filter('table.table-dashboard-feeds tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-dashboard-feeds tr td.items-count')->extract(['_text']));

        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(0, $item);
        }

        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-dashboard-feedlogs tr td.items-count')->extract(['_text']));

        foreach ($items as $item) {
            $this->assertGreaterThan(0, $item);
        }
    }

    public function testFeeds(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feeds');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertGreaterThan(0, $crawler->filter('table.table-feeds tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-feeds tr td.items-count')->extract(['_text']));

        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(0, $item);
        }
    }

    public function testFeedNew(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(1, $crawler->filter('form.custom'));
        $this->assertCount(3, $crawler->filter('input[type=text]'));
        $this->assertCount(2, $crawler->filter('input[type=url]'));
        $this->assertCount(1, $crawler->filter('input[type=checkbox]'));
        $this->assertCount(1, $crawler->filter('input[type=hidden]'));
        $this->assertCount(3, $crawler->filter('select'));
        $this->assertCount(1, $crawler->filter('textarea'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testFeedNewSubmitEmpty(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Form is invalid.', $alert[0]);
        $this->assertGreaterThanOrEqual(1, \count($crawler->filter('small.error')));
    }

    public function dataNewFeedOk(): array
    {
        return [[[
            'feed[name]' => 'Google News',
            'feed[description]' => 'À la une - Google Actualités',
            'feed[host]' => 'http://news.google.com',
            // be sure that link is almost always different
            'feed[link]' => 'http://news.google.fr/?output=rss&rand=' . time(),
            'feed[parser]' => 'external',
            'feed[formatter]' => 'rss',
            'feed[sort_by]' => 'published_at',
            'feed[is_private]' => 1,
        ]]];
    }

    /**
     * @dataProvider dataNewFeedOk
     *
     * This test will need an internet connection to pass.
     */
    public function testFeedNewSubmitBadRss(array $data): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $form = $crawler->filter('button[type=submit]')->form();

        // bad rss link
        $data['feed[link]'] = 'http://google.com';

        $crawler = $client->submit($form, $data);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Form is invalid.', $alert[0]);
        $this->assertGreaterThanOrEqual(1, \count($crawler->filter('small.error')));
    }

    /**
     * @dataProvider dataNewFeedOk
     */
    public function testFeedNewSubmitOk(array $data): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $form = $crawler->filter('button[type=submit]')->form();

        $client->submit($form, $data);
        $location = $client->getResponse()->headers->get('location');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('google-news', (string) $location);

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Feed created!', $alert[0]);
    }

    public function testFeedEditBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/nawak/edit');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    public function testFeedEditOk(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h3.subheader'));
        $this->assertCount(1, $crawler->filter('form.custom'));
        // 2 normal + 2 disabled on the right
        $this->assertCount(5, $crawler->filter('form.custom input[type=text]'));
        $this->assertCount(2, $crawler->filter('form.custom input[type=url]'));
        $this->assertCount(1, $crawler->filter('form.custom input[type=checkbox]'));
        $this->assertCount(1, $crawler->filter('form.custom input[type=hidden]'));
        $this->assertCount(3, $crawler->filter('form.custom select'));
        $this->assertCount(1, $crawler->filter('form.custom textarea'));
        $this->assertCount(1, $crawler->filter('form.custom button[type=submit]'));
        $this->assertCount(2, $crawler->filter('form.custom input[disabled=disabled]'));

        $this->assertCount(1, $crawler->filter('img.favicon'));
        $this->assertCount(5, $crawler->filter('span.info'));
        $this->assertCount(1, $crawler->filter('iframe.pubsubhubbub'));
    }

    public function dataEditFeedOk(): array
    {
        return [[[
            'feed[name]' => 'Bonjour Madame edited !',
            'feed[description]' => 'Bonjour Madame edited !',
            'feed[host]' => 'bonjourmadame.fr',
            'feed[link]' => 'http://feeds2.feedburner.com/BonjourMadame',
            'feed[parser]' => 'internal',
            'feed[formatter]' => 'atom',
            'feed[sort_by]' => 'published_at',
            // 'feed[is_private]' => 0,
        ]]];
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitBadValue(array $data): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/bonjour-madame/edit');

        // bad link
        $data['feed[link]'] = 'uzioau .oa';

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form, $data);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Form is invalid.', $alert[0]);
        // url invalid + feed invalid
        $this->assertGreaterThanOrEqual(1, $crawler->filter('small.error')->count());
        $this->assertStringContainsString('This value is not a valid URL.', (string) $client->getResponse()->getContent());
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitOk(array $data): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/bonjour-madame/edit');

        $form = $crawler->filter('button[type=submit]')->form();

        $client->submit($form, $data);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('bonjour-madame', (string) $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Feed updated!', $alert[0]);
    }

    public function testFeedUpdateBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/edit');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    public function testDeleteFormNotValid(): void
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/delete');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteBadSlug(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/bonjour-madame/edit');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->request('POST', '/feed/nawak/delete', $form->getPhpValues());

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Feed object not found', (string) $client->getResponse()->getContent());
    }

    /**
     * @depends testFeedNewSubmitOk
     *
     * Feed with `google-news` slug will be created
     */
    public function testDeleteOk(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/google-news/edit');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(['_text']));
        $this->assertSame('Feed deleted!', $alert[0]);
    }

    public function testInvalidFeed(): void
    {
        $client = static::createClient();

        $client->request('GET', '/nawak.xml');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Not Found', (string) $client->getResponse()->getContent());
    }

    public function testRedditFeed(): Crawler
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/reddit.xml');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML((string) $client->getResponse()->getContent());

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);

        return $crawler;
    }

    /**
     * @depends testRedditFeed
     */
    public function testRedditFeedContent(Crawler $crawler): void
    {
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/description')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/generator')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/lastBuildDate')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/description')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/guid')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/pubDate')->count());
    }

    public function testHnFeed(): Crawler
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hackernews.xml');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML((string) $client->getResponse()->getContent());

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);

        return $crawler;
    }

    /**
     * @depends testHnFeed
     */
    public function testHnFeedContent(Crawler $crawler): void
    {
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/author')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/generator')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/updated')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/id')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/summary')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/updated')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/id')->count());
    }
}
