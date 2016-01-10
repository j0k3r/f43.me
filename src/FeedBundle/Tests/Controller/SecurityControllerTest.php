<?php

namespace Api43\FeedBundle\Tests\Controller;

class SecurityControllerTest extends FeedWebTestCase
{
    public function testLogin()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(0, $crawler->filter('h2'));
        $this->assertCount(1, $legend = $crawler->filter('fieldset legend')->extract(['_text']));
        $this->assertEquals('Secured area', $legend[0]);
        $this->assertCount(1, $crawler->filter('input[type=text]'));
        $this->assertCount(1, $crawler->filter('input[type=password]'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testBadLogin()
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

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }
}
