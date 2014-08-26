<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class SecurityControllerTest extends FeedWebTestCase
{
    public function testLogin()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(0, $crawler->filter('h2'));
        $this->assertCount(1, $legend = $crawler->filter('fieldset legend')->extract(array('_text')));
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
        $form['_username'] = 'admin';
        $form['_password'] = 'admin';

        $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }
}
