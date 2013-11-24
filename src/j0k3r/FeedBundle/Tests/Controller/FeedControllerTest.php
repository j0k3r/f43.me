<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class FeedControllerTest extends FeedWebTestCase
{
    public function testIndex()
    {
        $client = static::getClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($crawler->filter('html:contains("f43.me")')->count() > 0);

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h2.subheader'));
        $this->assertGreaterThan(0, $crawler->filter('tr td div img.favicon')->count());

        // private field won't show up
        $this->assertNotContains('Bonjour', $client->getResponse()->getContent());
    }

    public function testUnAuthorized()
    {
        $client = static::getClient();

        $client->request('GET', '/dashboard');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://f43me.dev/login'));

        $client->request('GET', '/feeds');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://f43me.dev/login'));

        $client->request('GET', '/feed/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://f43me.dev/login'));

        $client->request('GET', '/feed/reddit/edit');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://f43me.dev/login'));
    }

    public function testDashboard()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/dashboard');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h3.subheader'));
        $this->assertCount(1, $crawler->filter('ul.left'));
        $this->assertCount(9, $crawler->filter('ul.left li'));
        $this->assertCount(1, $crawler->filter('ul.left li.active'));
        $this->assertCount(1, $logout = $crawler->filter('ul.right li.has-form a.alert')->extract(array('_text')));
        $this->assertEquals('Logout', $logout[0]);
        $this->assertGreaterThan(0, $crawler->filter('table.table-dashboard-feeds tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-dashboard-feeds tr td.items-count')->extract(array('_text')));

        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(0, $item);
        }

        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-dashboard-feedlogs tr td.items-count')->extract(array('_text')));

        foreach ($items as $item) {
            $this->assertGreaterThan(0, $item);
        }
    }

    public function testFeeds()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feeds');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertGreaterThan(0, $crawler->filter('table.table-feeds tbody tr td img.favicon')->count());
        $this->assertGreaterThan(0, $items = $crawler->filter('table.table-feeds tr td.items-count')->extract(array('_text')));

        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(0, $item);
        }
    }

    public function testFeedNew()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(1, $crawler->filter('form.custom'));
        $this->assertCount(2, $crawler->filter('input[type=text]'));
        $this->assertCount(1, $crawler->filter('input[type=url]'));
        $this->assertCount(1, $crawler->filter('input[type=checkbox]'));
        $this->assertCount(1, $crawler->filter('input[type=hidden]'));
        $this->assertCount(3, $crawler->filter('select'));
        $this->assertCount(1, $crawler->filter('textarea'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
    }

    public function testFeedNewSubmitEmpty()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Form is invalid.', $alert[0]);
        $this->assertCount(4, $crawler->filter('small.error'));
    }

    public function dataNewFeedOk()
    {
        return array(array(array(
            'feedbundle_feedtype[name]' => 'Google News',
            'feedbundle_feedtype[description]' => 'À la une - Google Actualités',
            'feedbundle_feedtype[host]' => 'news.google.com',
            // be sure that link is almost always different
            'feedbundle_feedtype[link]' => 'http://news.google.fr/?output=rss&rand='.time(),
            'feedbundle_feedtype[parser]' => 'external',
            'feedbundle_feedtype[formatter]' => 'rss',
            'feedbundle_feedtype[sort_by]' => 'published_at',
            // 'feedbundle_feedtype[is_private]' => 0,
            'feedbundle_feedtype[_token]' => '',
        )));
    }

    /**
     * @dataProvider dataNewFeedOk
     */
    public function testFeedNewSubmitBadRss($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        // retrieve csrf_token
        $token = $crawler->filter('input[id=feedbundle_feedtype__token]')->extract(array('_text', 'value'));
        $data['feedbundle_feedtype[_token]'] = $token[0][1];

        $form = $crawler->filter('button[type=submit]')->form();

        // bad rss link
        $data['feedbundle_feedtype[link]'] = 'http://google.com';

        $crawler = $client->submit($form, $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Form is invalid.', $alert[0]);
        $this->assertCount(1, $crawler->filter('small.error'));
    }

    /**
     * @dataProvider dataNewFeedOk
     */
    public function testFeedNewSubmitOk($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        // retrieve csrf_token
        $token = $crawler->filter('input[id=feedbundle_feedtype__token]')->extract(array('_text', 'value'));
        $data['feedbundle_feedtype[_token]'] = $token[0][1];

        $form = $crawler->filter('button[type=submit]')->form();

        $client->submit($form, $data);
        $location = $client->getResponse()->headers->get('location');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains('google-news', $location);

        $location = str_replace('/edit', '', $location);
        $slug = substr($location, strrpos($location, '/')+1, strlen($location));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Document created!', $alert[0]);
    }

    public function testFeedEditBadSlug()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/nawak/edit');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }

    public function testFeedEditOk()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('h2.title'));
        $this->assertCount(2, $crawler->filter('h3.subheader'));
        $this->assertCount(1, $crawler->filter('form.custom'));
        // 2 normal + 2 disabled on the right
        $this->assertCount(4, $crawler->filter('form.custom input[type=text]'));
        $this->assertCount(1, $crawler->filter('form.custom input[type=url]'));
        $this->assertCount(1, $crawler->filter('form.custom input[type=checkbox]'));
        $this->assertCount(1, $crawler->filter('form.custom input[type=hidden]'));
        $this->assertCount(3, $crawler->filter('form.custom select'));
        $this->assertCount(1, $crawler->filter('form.custom textarea'));
        $this->assertCount(1, $crawler->filter('form.custom button[type=submit]'));
        $this->assertCount(2, $crawler->filter('form.custom input[disabled=disabled]'));

        $this->assertCount(1, $crawler->filter('img.favicon'));
        $this->assertCount(5, $crawler->filter('span.info'));
        $this->assertCount(1, $crawler->filter('iframe.pubsubhubbub'));
    }

    public function dataEditFeedOk()
    {
        return array(array(array(
            'feedbundle_feedtype[name]' => 'HN edited !',
            'feedbundle_feedtype[description]' => 'Hackernews edited !',
            'feedbundle_feedtype[host]' => 'news.ycombinator.com',
            'feedbundle_feedtype[link]' => 'https://news.ycombinator.com/rss',
            'feedbundle_feedtype[parser]' => 'internal',
            'feedbundle_feedtype[formatter]' => 'rss',
            'feedbundle_feedtype[sort_by]' => 'published_at',
            // 'feedbundle_feedtype[is_private]' => 0,
            'feedbundle_feedtype[_token]' => '',
        )));
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitBadValue($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        // retrieve csrf_token
        $token = $crawler->filter('form.custom input[id=feedbundle_feedtype__token]')->extract(array('_text', 'value'));
        $data['feedbundle_feedtype[_token]'] = $token[0][1];

        // bad link
        $data['feedbundle_feedtype[link]'] = 'uzioauzoa';

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form, $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Form is invalid.', $alert[0]);
        // url invalid + feed invalid
        $this->assertCount(2, $crawler->filter('small.error'));
        $this->assertContains('This value is not a valid URL.', $client->getResponse()->getContent());
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitOk($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        // retrieve csrf_token
        $token = $crawler->filter('form.custom input[id=feedbundle_feedtype__token]')->extract(array('_text', 'value'));
        $data['feedbundle_feedtype[_token]'] = $token[0][1];

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form, $data);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains('hackernews', $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Document updated!', $alert[0]);
    }

    public function testFeedUpdateBadSlug()
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/update');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }

    public function testDeleteFormNotValid()
    {
        $client = static::getAuthorizedClient();

        $client->request('POST', '/feed/nawak/delete');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains('/feeds', $client->getResponse()->headers->get('location'));
    }

    /**
     * I can't make this test OK.
     * CSRF token is always invalid....
     */
    /*
    public function testDeleteBadSlug()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        $token = $crawler->filter('form.delete_form input[id=form__token]')->extract(array('_text', 'value'));

        $client->request('POST', '/feed/nawak/delete', array(
            'form[slug]' => 'nawak',
            'form[_token]' => $token[0][1],
        ));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }*/

    /**
     * @depends testFeedNewSubmitOk
     *
     * Feed with `google-news` slug will be created
     */
    public function testDeleteOk()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/google-news/edit');

        $token = $crawler->filter('form.delete_form input[id=form__token]')->extract(array('_text', 'value'));

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form, array(
            'form[slug]' => 'google-news',
            'form[_token]' => $token[0][1],
        ));

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Document deleted!', $alert[0]);
    }
}
