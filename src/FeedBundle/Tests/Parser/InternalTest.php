<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\Internal;
use GuzzleHttp\Exception\RequestException;

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
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue(''));

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParse()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new \GuzzleHttp\Message\Response(
            200,
            array(
                'Content-Encoding' => 'deflate',
                'Content-Type' => 'text/html; charset=iso',
            ),
            \GuzzleHttp\Stream\Stream::factory('<div></div>')
        );

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseVideo()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertContains('<iframe src="http://www.youtube.com/embed/8b7t5iUV0pQ" width="560" height="315"', $internal->parse('https://www.youtube.com/watch?v=8b7t5iUV0pQ'));
    }

    /**
     * This will throw an exception but the fallback will try to retrieve content using file_get_contents.
     */
    public function testParseGuzzleException()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->throwException(new RequestException('oops', $request)));

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($internal->parse('http://foo.bar.youpla'));
    }

    public function testParseFalse()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn(false);

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseImage()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new \GuzzleHttp\Message\Response(
            200,
            array(
                'Content-Encoding' => 'deflate',
                'Content-Type' => 'image',
            ),
            \GuzzleHttp\Stream\Stream::factory('<div></div>')
        );

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertEquals('<img src="http://localhost" />', $internal->parse('http://localhost'));
    }

    public function testParseGzip()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new \GuzzleHttp\Message\Response(
            200,
            array(
                'Content-Encoding' => 'gzip',
                'Content-Type' => 'text',
            ),
            \GuzzleHttp\Stream\Stream::factory(gzencode("<p>Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500, quand un peintre anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte.</p>"))
        );

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $internal = new Internal($guzzle, $this->regexs);
        $this->assertContains('readability', $internal->parse('http://localhost'));
    }
}
