<?php

namespace j0k3r\FeedBundle\Tests\Controller;

class FeedControllerTest extends FeedWebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

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
        $client = static::createClient();

        $client->request('GET', '/dashboard');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feeds');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/new');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));

        $client->request('GET', '/feed/reddit/edit');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
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
        $this->assertGreaterThanOrEqual(1, count($crawler->filter('small.error')));
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
        )));
    }

    /**
     * @dataProvider dataNewFeedOk
     *
     * This test will need an internet connection to pass.
     */
    public function testFeedNewSubmitBadRss($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

        $form = $crawler->filter('button[type=submit]')->form();

        // bad rss link
        $data['feedbundle_feedtype[link]'] = 'http://google.com';

        $crawler = $client->submit($form, $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Form is invalid.', $alert[0]);
        $this->assertGreaterThanOrEqual(1, count($crawler->filter('small.error')));
    }

    /**
     * @dataProvider dataNewFeedOk
     */
    public function testFeedNewSubmitOk($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/new');

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
            'feedbundle_feedtype[formatter]' => 'atom',
            'feedbundle_feedtype[sort_by]' => 'published_at',
            // 'feedbundle_feedtype[is_private]' => 0,
        )));
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitBadValue($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        // bad link
        $data['feedbundle_feedtype[link]'] = 'uzioau .oa';

        $form = $crawler->filter('button[type=submit]')->form();

        $crawler = $client->submit($form, $data);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Form is invalid.', $alert[0]);
        // url invalid + feed invalid
        $this->assertGreaterThanOrEqual(1, $crawler->filter('small.error')->count());
        $this->assertContains('This value is not a valid URL.', $client->getResponse()->getContent());
    }

    /**
     * @dataProvider dataEditFeedOk
     */
    public function testFeedEditSubmitOk($data)
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

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

    public function testDeleteBadSlug()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/hackernews/edit');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $crawler = $client->request('POST', '/feed/nawak/delete', $form->getPhpValues());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('Unable to find Feed document.', $client->getResponse()->getContent());
    }

    /**
     * @depends testFeedNewSubmitOk
     *
     * Feed with `google-news` slug will be created
     */
    public function testDeleteOk()
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/google-news/edit');

        $form = $crawler->filter('form.delete_form button[type=submit]')->form();

        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();
        $this->assertCount(1, $alert = $crawler->filter('div.alert-box')->extract(array('_text')));
        $this->assertEquals('Document deleted!', $alert[0]);
    }

    public function testInvalidFeed()
    {
        $client = static::createClient();

        $client->request('GET', '/nawak.xml');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('does not exists.', $client->getResponse()->getContent());
    }

    public function testRedditFeed()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/reddit.xml');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($client->getResponse()->getContent());

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);

        return $crawler;
    }

    /**
     * @depends testRedditFeed
     */
    public function testRedditFeedContent($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/description')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/generator')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/lastBuildDate')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/description')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/guid')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//channel/item/pubDate')->count());
    }

    public function testHnFeed()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hackernews.xml');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        libxml_use_internal_errors(true);

        $xml = new \DOMDocument();
        $xml->loadXML($client->getResponse()->getContent());

        $errors = libxml_get_errors();
        $this->assertEmpty($errors, var_export($errors, true));

        libxml_use_internal_errors(false);

        return $crawler;
    }

    /**
     * @depends testHnFeed
     */
    public function testHnFeedContent($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/author')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/generator')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/updated')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/id')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/title')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/summary')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/link')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/updated')->count());
        $this->assertGreaterThan(0, $crawler->filterXPath('//feed/entry/id')->count());
    }
}
