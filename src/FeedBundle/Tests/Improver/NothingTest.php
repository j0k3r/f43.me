<?php

namespace Api43\FeedBundle\Tests\Improver;

use Api43\FeedBundle\Improver\Nothing;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class NothingTest extends \PHPUnit_Framework_TestCase
{
    public function dataUpdateUrl()
    {
        return array(
            array('http://modmyi.com/?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_medium=feed&utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_campaign=Feed%3A+home_all+%28MMi+%7C+Homepage+All%29', 'http://modmyi.com/'),
            array('http://modmyi.com/?utm_source=feedburner', 'http://modmyi.com/'),
            array('http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html?utm_source=feedburner&utm_medium=feed&utm_campaign=Feed%3A+ac%2Factualites+%28AlloCine+-+RSS+News%3A+Cinema+%26+Series%29', 'http://www.allocine.fr/article/fichearticle_gen_carticle=18636758.html'),
        );
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

        $nothing = new Nothing($client);
        $this->assertEquals($expected, $nothing->updateUrl($url));
    }

    public function testUpdateUrlFail()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $nothing = new Nothing($client);
        $this->assertEquals('http://0.0.0.0/content?not-changed', $nothing->updateUrl('http://0.0.0.0/content'));
    }
}
