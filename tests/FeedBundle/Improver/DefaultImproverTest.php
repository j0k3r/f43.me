<?php

namespace Tests\FeedBundle\Improver;

use Api43\FeedBundle\Improver\DefaultImprover;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PHPUnit\Framework\TestCase;

class DefaultImproverTest extends TestCase
{
    public function dataUpdateUrl()
    {
        return [
            ['http://modmyi.com/?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'],
            ['http://modmyi.com/?utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'],
            ['http://modmyi.com/?utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'],
            ['http://modmyi.com/?utm_source=feedburner', 'http://modmyi.com/'],
            ['http://modmyi.com/?utm_source=feedburner&keepme=source', 'http://modmyi.com/?keepme=source'],
            ['http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+ac%2Factualites+%28AlloCine+-+RSS+News%3A+Cinema+%26+Series%29', 'http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html'],
            ['https://code.facebook.com/posts/938078729581886/improving-the-linux-kernel-with-upstream-contributions/?utm_source=codedot_rss_feed&amp%3Butm_medium=rss&amp%3Butm_campaign=RSS%20Feed', 'https://code.facebook.com/posts/938078729581886/improving-the-linux-kernel-with-upstream-contributions/'],
        ];
    }

    /**
     * @dataProvider dataUpdateUrl
     */
    public function testUpdateUrl($url, $expected)
    {
        $client = new Client();

        $response = new Response(200, []);
        $response->setEffectiveUrl($expected);

        $mock = new Mock([
            new Response(200, []),
        ]);

        $client->getEmitter()->attach($mock);

        $default = new DefaultImprover($client);
        $this->assertSame($expected, $default->updateUrl($url));
        $this->assertSame('', $default->updateContent(''));
    }

    public function testUpdateUrlFail()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $default = new DefaultImprover($client);
        $this->assertSame('http://0.0.0.0/content?not-changed', $default->updateUrl('http://0.0.0.0/content'));
    }

    public function testUpdateUrlFailHard()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $default = new DefaultImprover($client);
        $this->assertSame('http:///floooh.github.io/2018/10/06/bombjack.html?oups', $default->updateUrl('http:///floooh.github.io/2018/10/06/bombjack.html'));
    }
}
