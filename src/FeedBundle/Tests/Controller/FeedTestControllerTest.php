<?php

namespace Api43\FeedBundle\Tests\Controller;

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

        $crawler = $client->submit($form, array(
            'feedbundle_itemtesttype[link]' => 'http://www.nextinpact.com/news/89458-gouvernement-valls2-fleur-pellerin-a-culture.htm',
            'feedbundle_itemtesttype[parser]' => 'internal'
        ));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('ul.no-bullet'));
        $this->assertCount(1, $crawler->filter('div[itemprop=articleBody]'));
    }
}
