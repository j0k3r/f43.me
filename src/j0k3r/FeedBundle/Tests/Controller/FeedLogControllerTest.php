<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class FeedLogControllerTest extends FeedWebTestCase
{
    public function testUnAuthorized()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/feed/reddit/logs');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $crawler = $client->request('GET', '/feed/reddit/logs/deleteAll');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $crawler = $client->request('GET', '/logs');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    public function testLogs()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/logs');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('h1')->count());
        $this->assertEquals(1, $crawler->filter('h2.title')->count());
        $this->assertGreaterThan(0, $crawler->filter('table.table-feedlogs tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-feedlogs tr td.items-count')->extract(array('_text')));

        foreach ($items as $item) {
            $this->assertGreaterThan(0, $item);
        }
    }
}
