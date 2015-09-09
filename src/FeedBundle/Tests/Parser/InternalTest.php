<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\Internal;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class InternalTest extends \PHPUnit_Framework_TestCase
{
    protected $regexs = array(
        'unlikelyCandidates' => '/combx|comment|community|disqus|extra|foot|header|menu|remark|rss|shoutbox|sidebar|sponsor|ad-break|agegate|pagination|pager|popup|addthis|response|slate_associated_bn|reseaux|sharing|auteur|tag|feedback|meta|kudo|sidebar|copyright|bio|moreInfo|legal|share/i',
        'okMaybeItsACandidate' => '/and|article|body|column|main|shadow/i',
        'positive' => '/article|body|content|entry|hentry|main|page|attachment|pagination|post|text|blog|story/i',
        'negative' => '/combx|comment|com-|contact|foot|footer|_nav|footnote|masthead|media|meta|outbrain|promo|related|scroll|shoutbox|sidebar|sponsor|shopping|tags|tool|widget|header|aside/i',
        'divToPElements' => '/<(a|blockquote|dl|div|img|ol|p|pre|table|ul)/i',
        'replaceBrs' => '/(<br[^>]*>[ \n\r\t]*){2,}/i',
        'replaceFonts' => '/<(\/?)font[^>]*>/i',
        'normalize' => '/\s{2,}/',
        'killBreaks' => '/(<br\s*\/?>(\s|&nbsp;?)*){1,}/',
        'video' => '!//(player\.|www\.)?(youtube|vimeo|viddler|dailymotion)\.com!i',
        'skipFootnoteLink' => '/^\s*(\[?[a-z0-9]{1,2}\]?|^|edit|citation needed)\s*$/i',
        'attrToRemove' => 'onclick|rel|class|target|fs:definition|alt|id|onload|name|onchange',
        'tagToRemove' => 'select|form|header|footer|aside',
    );

    public function testParseEmpty()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, []),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParse()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['Content-Encoding' => 'deflate', 'Content-Type' => 'text/html; charset=iso'], Stream::factory('<div></div>')),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseVideo()
    {
        $internal = new Internal(new Client(), $this->regexs);
        $this->assertContains('<iframe src="http://www.youtube.com/embed/8b7t5iUV0pQ" width="560" height="315"', $internal->parse('https://www.youtube.com/watch?v=8b7t5iUV0pQ'));
    }

    /**
     * This will throw an exception but the fallback will try to retrieve content using file_get_contents.
     */
    public function testParseGuzzleException()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertEmpty($internal->parse('http://foo.bar.youpla'));
    }

    public function testParseFalse()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['Content-Type' => 'text']),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseImage()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['Content-Encoding' => 'deflate', 'Content-Type' => 'image'], Stream::factory('<div></div>')),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertEquals('<img src="http://localhost" />', $internal->parse('http://localhost'));
    }

    public function testParseGzip()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, ['Content-Encoding' => 'gzip', 'Content-Type' => 'text'], Stream::factory(gzencode("<p>Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500, quand un peintre anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte.</p>"))),
        ]);

        $client->getEmitter()->attach($mock);

        $internal = new Internal($client, $this->regexs);
        $this->assertContains('readability', $internal->parse('http://localhost'));
    }
}
