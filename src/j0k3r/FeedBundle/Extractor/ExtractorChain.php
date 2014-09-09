<?php

namespace j0k3r\FeedBundle\Extractor;

class ExtractorChain
{
    private $extractors;

    public function __construct()
    {
        $this->extractors = array();
    }

    /**
     * Add an extrator to the chain
     *
     * @param AbstractExtractor $extractor
     * @param string            $alias
     */
    public function addExtractor(AbstractExtractor $extractor, $alias)
    {
        $this->extractors[$alias] = $extractor;
    }

    /**
     * Get one extractor by alias
     *
     * @param string $alias
     *
     * @return bool|object
     */
    public function getExtractor($alias)
    {
        if (array_key_exists($alias, $this->extractors)) {
           return $this->extractors[$alias];
        }

        return false;
    }

    /**
     * Loop thru all extractor to find one that match
     *
     * @param string $url An url
     *
     * @return string|false
     */
    public function match($url)
    {
        foreach ($this->extractors as $alias => $extractor) {
            if (true === $extractor->match($url)) {
                return $alias;
            }
        }

        return false;
    }
}
