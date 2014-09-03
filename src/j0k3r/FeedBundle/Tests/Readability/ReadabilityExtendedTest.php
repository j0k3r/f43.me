<?php

namespace j0k3r\FeedBundle\Tests\Readability;

use j0k3r\FeedBundle\Readability\ReadabilityExtended;

class ReadabilityExtendedTest extends \PHPUnit_Framework_TestCase
{
    private $readability;
    private $dom;

    protected function setUp()
    {
        $this->readability = new ReadabilityExtended('<html/>', 'http://localhost');
        $this->readability->regexps = array(
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

        $this->dom = new \DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        $this->dom->loadHTML('<html/>');
        $this->dom->registerNodeClass('DOMElement', 'JSLikeHTMLElement');
    }

    protected function tearDown()
    {
        unset($this->readability);
    }

    public function testCleanAttrs()
    {
        $domElement = $this->dom->createElement('div');

        $nodeToAppend = $this->dom->createElement('div');
        $nodeToAppend->setAttribute('class', 'nothing');

        $domElement->appendChild($nodeToAppend);

        $this->readability->cleanAttrs($domElement);

        $this->assertEquals('<div/>', $domElement->innerHTML);
    }

    public function testCleanTags()
    {
        $domElement = $this->dom->createElement('p', 'test');

        $nodeToAppend = $this->dom->createElement('aside');
        $nodeToAppend->setAttribute('class', 'nothing');

        $domElement->appendChild($nodeToAppend);

        $this->readability->cleanTags($domElement);

        $this->assertEquals('test', $domElement->innerHTML);
    }

    public function testMakeImgSrcAbsolute()
    {
        $domElement = $this->dom->createElement('p', 'test');

        $nodeToAppend1 = $this->dom->createElement('img');
        $nodeToAppend1->setAttribute('src', '/img/foo.bar');
        $domElement->appendChild($nodeToAppend1);

        $nodeToAppend2 = $this->dom->createElement('img');
        $nodeToAppend2->setAttribute('src', '/img/foo.bar');
        $nodeToAppend2->setAttribute('originalsrc', '/img/full-foo.bar');
        $domElement->appendChild($nodeToAppend2);

        $nodeToAppend3 = $this->dom->createElement('img');
        $nodeToAppend3->setAttribute('src', '/img/foo.bar');
        $nodeToAppend2->setAttribute('data-src', '/img/full-foo.bar');
        $domElement->appendChild($nodeToAppend3);

        $nodeToAppend3 = $this->dom->createElement('img');
        $nodeToAppend3->setAttribute('src', 'https://localhost/img/foo.bar');
        $domElement->appendChild($nodeToAppend3);

        $nodeToAppend2 = $this->dom->createElement('img');
        $domElement->appendChild($nodeToAppend2);

        $this->readability->makeImgSrcAbsolute($domElement);

        $this->assertEquals('test<img src="http://localhost/img/foo.bar"/><img src="http://localhost/img/full-foo.bar"/><img src="http://localhost/img/foo.bar"/><img src="https://localhost/img/foo.bar"/>', $domElement->innerHTML);
    }

    public function testMakeHrefAbsolute()
    {
        $domElement = $this->dom->createElement('p', 'test');

        $nodeToAppend1 = $this->dom->createElement('a');
        $nodeToAppend1->setAttribute('href', '/img/foo.bar');
        $domElement->appendChild($nodeToAppend1);

        $nodeToAppend2 = $this->dom->createElement('a');
        $nodeToAppend2->setAttribute('href', 'https://localhost/img/foo.bar');
        $domElement->appendChild($nodeToAppend2);

        $this->readability->makeHrefAbsolute($domElement);

        $this->assertEquals('test<a href="http://localhost/img/foo.bar"/><a href="https://localhost/img/foo.bar"/>', $domElement->innerHTML);
    }

    public function testConvertH1ToH2()
    {
        $domElement = $this->dom->createElement('p', 'test');

        $nodeToAppend1 = $this->dom->createElement('h1', 'first h1');
        $domElement->appendChild($nodeToAppend1);

        $nodeToAppend2 = $this->dom->createElement('h1', 'second h1');
        $nodeToAppend2->setAttribute('class', 'title');
        $domElement->appendChild($nodeToAppend2);

        $nodeToAppend3 = $this->dom->createElement('h2', 'second h2');
        $domElement->appendChild($nodeToAppend3);

        $this->readability->convertH1ToH2($domElement);

        $this->assertEquals('test<h2>first h1</h2><h2 class="title">second h1</h2><h2>second h2</h2>', $domElement->innerHTML);

        $domElement = $this->dom->createElement('p', 'test');

        $nodeToAppend1 = $this->dom->createElement('h1', 'first h1');
        $domElement->appendChild($nodeToAppend1);

        $this->readability->convertH1ToH2($domElement);

        $this->assertEquals('test<h1>first h1</h1>', $domElement->innerHTML);
    }

    public function testNotAnObjectArgument()
    {
        $this->assertEmpty($this->readability->cleanAttrs(''));
        $this->assertEmpty($this->readability->cleanTags(''));
        $this->assertEmpty($this->readability->makeImgSrcAbsolute(''));
        $this->assertEmpty($this->readability->makeHrefAbsolute(''));
        $this->assertEmpty($this->readability->convertH1ToH2(''));
    }
}
