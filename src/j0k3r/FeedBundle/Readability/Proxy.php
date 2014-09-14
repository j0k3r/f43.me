<?php

namespace j0k3r\FeedBundle\Readability;

use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Extractor\ExtractorChain;
use j0k3r\FeedBundle\Improver\ImproverChain;
use j0k3r\FeedBundle\Parser\ParserChain;

class Proxy
{
    protected $feed = null;
    protected $extractorChain;
    protected $improverChain;
    protected $parserChain;
    protected $chosenParser = null;
    protected $allowAllParser = false;

    public $url = '';
    public $content = '';
    public $useDefault = false;

    /**
     * Create a new Proxy for Readability
     *
     * @param Browser        $buzz
     * @param ExtractorChain $extractorChain
     * @param ImproverChain  $improverChain
     * @param ParserChain    $parserChain
     */
    public function __construct(ExtractorChain $extractorChain, ImproverChain $improverChain, ParserChain $parserChain)
    {
        $this->extractorChain = $extractorChain;
        $this->improverChain = $improverChain;
        $this->parserChain = $parserChain;
    }

    /**
     * Initialize some common variable
     *
     * @param string    $chosenParser   Could be "internal" or "external"
     * @param null|Feed $feed           Define the Feed object to work on
     * @param bool      $allowAllParser Define if we have to use all *known* parser to get the content if the defined one failed.
     *                                  For example, Internal parser can't make content readable, it will use the External one, etc ..
     *
     * @return Proxy Current object
     */
    public function init($chosenParser, Feed $feed = null, $allowAllParser = false)
    {
        $this->chosenParser = $chosenParser;
        $this->feed = $feed;
        $this->allowAllParser = (bool) $allowAllParser;

        return $this;
    }

    /**
     * Try to retrieve content from a given url
     *
     * @param string      $url         RSS item url
     * @param string|null $itemContent RSS item content, which will be taken if we can't extract content from url
     *
     * @return Proxy
     */
    public function parseContent($url, $itemContent = null)
    {
        // be sure to have a clean workspace :)
        $this->content = '';
        $this->url = '';

        // the feed isn't always defined, for example when we test an url
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (null !== $this->feed) {
            $host = $this->feed->getHost();
        }

        // we loop thru all improver and we are SURE that the default one will match anyway
        $improverAlias = $this->improverChain->match($host);
        $improver = $this->improverChain->getImprover($improverAlias);

        $improver->setUrl($url);
        $improver->setItemContent($itemContent);

        // retrieve custom url ?
        $this->url = $improver->updateUrl($url);

        // try to find a custom extractor for api content (imgur, twitter, etc...)
        $extractorAlias = $this->extractorChain->match($this->url);
        if (false !== $extractorAlias) {
            $this->content = $this->extractorChain
                ->getExtractor($extractorAlias)
                ->getContent();
        }

        $parser = $this->parserChain->getParser(strtolower($this->chosenParser));

        // this means the selected extractor was able to extract content OR
        // no extractor were able to match the url
        if (!$this->content && false !== $parser) {
            $this->content = $parser->parse($this->url);
        }

        // if we allow all parser to be tested to get content, loop through all of them
        if (!$this->content && true === $this->allowAllParser) {
            $this->content = $this->parserChain->parseAll($this->url);
        }

        // do something when readable content failed
        if (!$this->content) {
            $this->content = $itemContent;
            $this->useDefault = true;
        } else {
            // update readable content with something ?
            $this->content = $improver->updateContent($this->content);
        }

        return $this;
    }
}
