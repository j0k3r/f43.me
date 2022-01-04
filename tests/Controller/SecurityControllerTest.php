<?php

namespace App\Tests\Controller;

class SecurityControllerTest extends FeedWebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2'));
        $this->assertCount(1, $crawler->filter('input[type=text]'));
        $this->assertCount(1, $crawler->filter('input[type=password]'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testBadLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('button[type=submit]')->form();

        // bad password
        $data = [
            '_username' => 'admin',
            '_password' => 'admin',
        ];

        $client->submit($form, $data);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }
}
