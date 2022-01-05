<?php

namespace App\Tests\Controller;

class TestControllerTest extends FeedWebTestCase
{
    public function testFeedTest(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('input[type=url]'));
        $this->assertCount(1, $crawler->filter('input[type=hidden]'));
        $this->assertCount(1, $crawler->filter('select'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testFeedTestSubmit(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $form = $crawler->filter('form.custom button[type=submit]')->form();

        $crawler = $client->submit($form, [
            'item_test[link]' => 'https://www.lemonde.fr/planete/article/2015/12/16/bisphenol-a-phtalates-pesticides-bruxelles-condamnee-pour-son-inaction_4833090_3244.html',
            'item_test[parser]' => 'internal',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('details > div > ul'));
        $this->assertStringNotContainsString('We failed to make this item readable, the default text from the feed item will be displayed instead.', (string) $client->getResponse()->getContent());
    }

    public function testFeedTestSubmitWithSiteConfig(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $form = $crawler->filter('form.custom button[type=submit]')->form();

        $crawler = $client->submit($form, [
            'item_test[link]' => 'https://www.lemonde.fr/planete/article/2015/12/16/bisphenol-a-phtalates-pesticides-bruxelles-condamnee-pour-son-inaction_4833090_3244.html',
            'item_test[parser]' => 'internal',
            'item_test[siteconfig]' => 'body: //body',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringNotContainsString('We failed to make this item readable, the default text from the feed item will be displayed instead.', (string) $client->getResponse()->getContent());
    }

    public function testFeedTestSubmitWithSiteConfigNonExistent(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $form = $crawler->filter('form.custom button[type=submit]')->form();

        $crawler = $client->submit($form, [
            'item_test[link]' => 'https://bandito.re',
            'item_test[parser]' => 'internal',
            'item_test[siteconfig]' => 'body: //body',
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringNotContainsString('We failed to make this item readable, the default text from the feed item will be displayed instead.', (string) $client->getResponse()->getContent());
    }
}
