<?php

namespace App\Tests\Extractor;

use App\Extractor\Twitch;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class TwitchTest extends AppTestCase
{
    public function dataMatch(): array
    {
        return [
            ['https://www.twitch.tv/tomfawkes/v/91819468', true],
            ['https://twitch.tv/tomfawkes/v/91819468', true],
            ['http://www.twitch.tv/tomfawkes/v/91819468', true],
            ['https://www.twitch.tv/directory/game/Clustertruck', false],
            ['http://google.com', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch(string $url, bool $expected): void
    {
        $twitch = new Twitch('apikey');
        $this->assertSame($expected, $twitch->match($url));
    }

    public function testContent(): void
    {
        $client = self::getMockClient([
            new Response(200, [], (string) json_encode(['title' => 'hihi', 'description' => 'hoho', 'preview' => 'http://0.0.0.0/image.jpg'])),
            new Response(200, [], (string) json_encode([])),
            new Response(400, [], (string) json_encode('oops')),
        ]);

        $twitch = new Twitch('apikey');
        $twitch->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $twitch->setLogger($logger);

        // first test fail because we didn't match an url, so TwitchId isn't defined
        $this->assertEmpty($twitch->getContent());

        $twitch->match('https://www.twitch.tv/tomfawkes/v/91819468');

        // consecutive calls
        $this->assertSame('<div><h2>hihi</h2><p>hoho</p><p><img src="http://0.0.0.0/image.jpg"></p><iframe src="https://player.twitch.tv/?video=v91819468" frameborder="0" scrolling="no" height="378" width="620"></iframe></div>', $twitch->getContent());
        // this one will got an empty array
        $this->assertEmpty($twitch->getContent());
        // this one will catch an exception
        $this->assertEmpty($twitch->getContent());

        $this->assertTrue($logHandler->hasWarning('Twitch extract failed for: 91819468'), 'Warning message matched');
    }
}
