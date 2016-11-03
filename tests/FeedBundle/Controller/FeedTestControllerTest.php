<?php

namespace tests\FeedBundle\Controller;

class FeedTestControllerTest extends FeedWebTestCase
{
    public function testFeedTest()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(1, $crawler->filter('input[type=url]'));
        $this->assertCount(1, $crawler->filter('input[type=hidden]'));
        $this->assertCount(1, $crawler->filter('select'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testFeedTestSubmit()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $form = $crawler->filter('form.custom button[type=submit]')->form();

        $crawler = $client->submit($form, [
            'item_test[link]' => 'http://www.lemonde.fr/planete/article/2015/12/16/bisphenol-a-phtalates-pesticides-bruxelles-condamnee-pour-son-inaction_4833090_3244.html',
            'item_test[parser]' => 'internal',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
        $this->assertNotContains('We failed to make this item readable, the default text from the feed item will be displayed instead.', $client->getResponse()->getContent());
    }

    public function testFeedTestSubmitWithSiteConfig()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $form = $crawler->filter('form.custom button[type=submit]')->form();

        $crawler = $client->submit($form, [
            'item_test[link]' => 'http://www.lemonde.fr/planete/article/2015/12/16/bisphenol-a-phtalates-pesticides-bruxelles-condamnee-pour-son-inaction_4833090_3244.html',
            'item_test[parser]' => 'internal',
            'item_test[siteconfig]' => 'body: //body',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
        $this->assertNotContains('We failed to make this item readable, the default text from the feed item will be displayed instead.', $client->getResponse()->getContent());
    }
}
