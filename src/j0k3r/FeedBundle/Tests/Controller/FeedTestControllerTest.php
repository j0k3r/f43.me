<?php

namespace j0k3r\FeedBundle\Tests\Controller;

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
}
