<?php

namespace App\Tests\Parser;

use App\Parser\Internal;
use Graby\Content;
use Graby\HttpClient\EffectiveResponse;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class InternalTest extends TestCase
{
    public function testParseEmpty(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent(''));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseFalse(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent(''));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseOk(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent('<p>test</p>'));

        $internal = new Internal($graby);
        $this->assertNotEmpty($internal->parse('http://localhost'));
    }

    public function testParseException(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    private function getGrabyContent(string $html): Content
    {
        return new Content(
            new EffectiveResponse(
                new Uri('http://website.test/content.html'),
                new Response(200, [], '')
            ),
            // html
            $html,
            // title
            '',
            // language
            null,
            // date
            null,
            // authors
            [],
            // image
            null,
            // is ads
            false,
        );
    }
}
