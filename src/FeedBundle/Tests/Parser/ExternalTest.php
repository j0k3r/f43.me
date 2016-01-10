<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\External;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class ExternalTest extends \PHPUnit_Framework_TestCase
{
    public function testParseEmpty()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, []),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }

    public function testParse()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['content' => '<div></div>', 'url' => 'http://1.1.1.1/content']))),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'token');
        $this->assertEquals('<div></div>', $external->parse('http://0.0.0.0/content'));
    }

    public function testParseException()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'token');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }
}
