<?php

namespace j0k3r\FeedBundle\Tests\Parser;

use j0k3r\FeedBundle\Parser\Internal;
use Guzzle\Http\Exception\RequestException;

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
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue(''));

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($external->parse('http://localhost'));
    }

    public function testParse()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('<div></div>'));

        $response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Content-Encoding':
                        return 'deflate';

                    case 'Content-Type':
                        return 'text';
                }
            }));

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($external->parse('http://localhost'));
    }

    public function testParseVideo()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEquals('<iframe src="http://www.youtube.com/embed/8b7t5iUV0pQ" width="560" height="315"></iframe>', $external->parse('https://www.youtube.com/watch?v=8b7t5iUV0pQ'));
    }

    public function testParseGuzzleException()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->throwException(new RequestException()));

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($external->parse('http://localhost'));
    }

    public function testParseFalse()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn(false);

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEmpty($external->parse('http://localhost'));
    }

    public function testParseImage()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('<div></div>'));

        $response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Content-Encoding':
                        return 'deflate';

                    case 'Content-Type':
                        return 'image';
                }
            }));

        $external = new Internal($guzzle, $this->regexs);
        $this->assertEquals('<img src="http://localhost" />', $external->parse('http://localhost'));
    }

    public function testParseGzip()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn(gzencode("<p>Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500, quand un peintre anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte.</p>"));

        $response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Content-Encoding':
                        return 'gzip';

                    case 'Content-Type':
                        return 'text';
                }
            }));

        $external = new Internal($guzzle, $this->regexs);
        $this->assertContains('readability', $external->parse('http://localhost'));
    }
}
