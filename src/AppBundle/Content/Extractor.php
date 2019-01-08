<?php

namespace AppBundle\Content;

use AppBundle\Converter\ConverterChain;
use AppBundle\Document\Feed;
use AppBundle\Extractor\ExtractorChain;
use AppBundle\Improver\ImproverChain;
use AppBundle\Parser\ParserChain;

class Extractor
{
    public $url = '';
    public $content = '';
    public $useDefault = false;
    protected $feed = null;
    protected $extractorChain;
    protected $improverChain;
    protected $converterChain;
    protected $parserChain;
    protected $parser;
    protected $allowAllParser = false;
    protected $reloadConfigFiles = false;

    /**
     * Content Extractor will use Extractor, Improver & Parser to get the readable content.
     *
     * @param ExtractorChain $extractorChain
     * @param ImproverChain  $improverChain
     * @param ConverterChain $converterChain
     * @param ParserChain    $parserChain
     */
    public function __construct(ExtractorChain $extractorChain, ImproverChain $improverChain, ConverterChain $converterChain, ParserChain $parserChain)
    {
        $this->extractorChain = $extractorChain;
        $this->improverChain = $improverChain;
        $this->converterChain = $converterChain;
        $this->parserChain = $parserChain;
    }

    /**
     * Initialize some common variable.
     *
     * @param string    $chosenParser   Could be "internal" or "external"
     * @param Feed|null $feed           Define the Feed object to work on
     * @param bool      $allowAllParser Define if we have to use all *known* parser to get the content if the defined one failed.
     *                                  For example, Internal parser can't make content readable, it will use the External one, etc ..
     *
     * @return Extractor Current object
     */
    public function init($chosenParser, Feed $feed = null, $allowAllParser = false)
    {
        $this->parser = $this->parserChain->getParser(strtolower($chosenParser));
        if (false === $this->parser) {
            throw new \InvalidArgumentException(sprintf('The given parser "%s" does not exists.', $chosenParser));
        }

        $this->feed = $feed;
        $this->allowAllParser = (bool) $allowAllParser;

        return $this;
    }

    /**
     * Force parser to reload config files to use freshly created files.
     * Only used for the Internal parser.
     *
     * @return Extractor Current object
     */
    public function enableReloadConfigFiles()
    {
        $this->reloadConfigFiles = true;

        return $this;
    }

    /**
     * Try to retrieve content from a given url.
     *
     * @param string      $url         RSS item url
     * @param string|null $itemContent RSS item content, which will be taken if we can't extract content from url
     *
     * @return Extractor
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
        $improver = $this->improverChain->match($host);
        $improver->setUrl($url);
        $improver->setItemContent($itemContent);

        // retrieve custom url ?
        $this->url = $improver->updateUrl($url);

        // try to find a custom extractor for api content (imgur, twitter, etc...)
        $extractor = $this->extractorChain->match($this->url);
        if (false !== $extractor) {
            $this->content = $extractor->getContent();
        }

        // this means the selected extractor wasn't able to extract content OR
        // no extractor were able to match the url
        if (!$this->content) {
            $this->content = $this->parser->parse($this->url, $this->reloadConfigFiles);
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

        // give content to each converter to convert some html into a better one
        $this->content = $this->converterChain->convert($this->content);

        return $this;
    }
}
