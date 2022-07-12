<?php

namespace App\Tests\Parser;

use App\Parser\External;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;

class ExternalTest extends AppTestCase
{
    public function testParseEmpty(): void
    {
        $client = self::getMockClient([new Response(200, [], (string) json_encode(''))]);

        $external = new External($client, 'http//0.0.0.0/api');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }

    public function testParse(): void
    {
        $client = self::getMockClient([new Response(200, [], (string) json_encode(['content' => '<div></div>', 'url' => 'http://1.1.1.1/content']))]);

        $external = new External($client, 'http//0.0.0.0/api');
        $this->assertSame('<div></div>', $external->parse('http://0.0.0.0/content'));
    }

    public function testParseException(): void
    {
        $client = self::getMockClient([new Response(400, [], (string) json_encode('oops'))]);

        $external = new External($client, 'http//0.0.0.0/api');
        $this->assertEmpty($external->parse('http://0.0.0.0/content'));
    }
}
