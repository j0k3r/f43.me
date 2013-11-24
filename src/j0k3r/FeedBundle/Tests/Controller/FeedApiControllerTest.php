<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class FeedApiControllerTest extends FeedWebTestCase
{
    public function testIndex()
    {
        $client = static::getClient(array('HTTP_HOST' => 'api.f43me.dev'));

        $client->request('GET', '/');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testInvalidFeed()
    {
        $client = static::getClient(array('HTTP_HOST' => 'api.f43me.dev'));

        $client->request('GET', '/nawak');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Feed does not exists.', $client->getResponse()->getContent());
    }

    public function testRedditFeed()
    {
        $client = static::getClient(array('HTTP_HOST' => 'api.f43me.dev'));

        $crawler = $client->request('GET', '/reddit');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($client->getResponse()->getContent());

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        return $crawler;
    }

    /**
     * @depends testRedditFeed
     */
    public function testFeedContent($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/description')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/pubDate')->count());
    }
}
