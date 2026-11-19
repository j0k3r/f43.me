<?php

namespace App\Content;

use App\Converter\ConverterChain;
use App\Entity\Feed;
use App\Extractor\ExtractorChain;
use App\Improver\DefaultImprover;
use App\Improver\ImproverChain;
use App\Parser\Internal;

class Extractor
{
    /** @var string */
    public $url = '';
    /** @var string */
    public $content = '';
    /** @var bool */
    public $useDefault = false;
    /** @var Feed|null */
    protected $feed;
    /** @var bool */
    protected $reloadConfigFiles = false;

    /**
     * Content Extractor will use Extractor, Improver & Parser to get the readable content.
     */
    public function __construct(protected ExtractorChain $extractorChain, protected ImproverChain $improverChain, protected ConverterChain $converterChain, protected Internal $parser)
    {
    }

    public function init(?Feed $feed = null): self
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Force parser to reload config files to use freshly created files.
     * Only used for the Internal parser.
     */
    public function enableReloadConfigFiles(): self
    {
        $this->reloadConfigFiles = true;

        return $this;
    }

    /**
     * Try to retrieve content from a given url.
     *
     * @param string        $url         RSS item url
     * @param string|null   $itemContent RSS item content, which will be taken if we can't extract content from url
     * @param Internal|null $parser      Isolated parser for siteconfig tests; bypasses specialized extractors
     */
    public function parseContent($url, $itemContent = null, ?Internal $parser = null): self
    {
        // be sure to have a clean workspace :)
        $this->content = '';
        $this->url = '';

        // the feed isn't always defined, for example when we test an url
        $host = parse_url($url, \PHP_URL_HOST) ?: '';
        if (null !== $this->feed) {
            $host = $this->feed->getHost();
        }

        // we loop thru all improver and we are SURE that the default one will match anyway
        /** @var DefaultImprover */
        $improver = $this->improverChain->match($host);
        $improver->setUrl($url);
        $improver->setItemContent((string) $itemContent);

        // retrieve custom url ?
        $this->url = $improver->updateUrl($url);

        // try to find a custom extractor for api content (imgur, etc...)
        $extractor = null === $parser ? $this->extractorChain->match($this->url) : false;
        if (false !== $extractor) {
            $this->content = $extractor->getContent();
        }

        // this means the selected extractor wasn't able to extract content OR
        // no extractor were able to match the url
        $parser ??= $this->parser;
        if (!$this->content) {
            $this->content = $parser->parse($this->url, $this->reloadConfigFiles);
        }

        // do something when readable content failed
        if (!$this->content) {
            $this->content = (string) $itemContent;
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
