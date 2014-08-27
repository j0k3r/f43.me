<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class FeedLogControllerTest extends FeedWebTestCase
{
    public function testUnAuthorized()
    {
        $client = static::createClient();

        $client->request('GET', '/feed/reddit/logs');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/reddit/logs/deleteAll');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/logs');
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

    public function testLogsFeedNotExists()
    {
        $client = static::getAuthorizedClient();

        $client->request('GET', '/feed/toto/logs');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }

    public function testLogsFeed()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/reddit/logs');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('a.secondary.button.radius.small'));
        $this->assertCount(1, $crawler->filter('button.alert.button.radius.small'));
        $this->assertGreaterThan(0, $crawler->filter('table.table-feedlogs tbody tr td')->count());
    }

    public function testDeleteAll()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/reddit/logs');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains('reddit', $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertContains('documents deleted!', $alert[0]);
    }

    public function testDeleteAllFormInvalid()
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/reddit/logs/deleteAll');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains('reddit', $client->getResponse()->headers->get('location'));
    }

    public function testDeleteAllBadSlug()
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/logs/deleteAll');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }
}
