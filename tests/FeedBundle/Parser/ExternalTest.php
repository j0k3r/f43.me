<?php

namespace Tests\FeedBundle\Parser;

use Api43\FeedBundle\Parser\External;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PHPUnit\Framework\TestCase;

class ExternalTest extends TestCase
{
    public function testParseEmpty()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(''))),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'key');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }

    public function testParse()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(['content' => '<div></div>', 'url' => 'http://1.1.1.1/content']))),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'key');
        $this->assertSame('<div></div>', $external->parse('http://0.0.0.0/content'));
    }

    public function testParseException()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $external = new External($client, 'http//0.0.0.0/api', 'key');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }
}
