<?php

namespace App\Tests\Extractor;

use App\Extractor\Ifttt;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class IftttTest extends AppTestCase
{
    public function dataMatch(): array
    {
        return [
            ['https://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak', true],
            ['http://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak', true],
            ['https://ifttt.com/recipes/385105', true],
            ['https://ifttt.com', false],
            ['https://goog.co', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch(string $url, bool $expected): void
    {
        $ifttt = new Ifttt();
        $this->assertSame($expected, $ifttt->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([
            new Response(200, [], (string) json_encode(['title' => 'my title', 'description' => 'Cool stuff bro'])),
            new Response(200, [], (string) json_encode('')),
            new Response(400, [], (string) json_encode('oops')),
        ]);

        $ifttt = new Ifttt();
        $ifttt->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $ifttt->setLogger($logger);

        // first test fail because we didn't match an url, so IftttUrl isn't defined
        $this->assertEmpty($ifttt->getContent());

        $ifttt->match('https://ifttt.com/recipes/385105-receive-notifications-for-a-jailbreak');

        // consecutive calls
        $this->assertSame('<div><h2>my title</h2><p>Cool stuff bro</p><p><a href="https://ifttt.com/recipes/385105"><img src="https://ifttt.com/recipe_embed_img/385105"></a></p></div>', $ifttt->getContent());
        // this one will got an empty array
        $this->assertEmpty($ifttt->getContent());
        // this one will catch an exception
        $this->assertEmpty($ifttt->getContent());

        $this->assertTrue($logHandler->hasWarning('Ifttt extract failed for: 385105'), 'Warning message matched');
    }
}
