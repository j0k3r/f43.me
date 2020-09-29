<?php

namespace App\Extractor;

class ExtractorChain
{
    /** @var array */
    private $extractors;

    public function __construct()
    {
        $this->extractors = [];
    }

    /**
     * Add an extractor to the chain.
     *
     * @param string $alias
     */
    public function addExtractor(AbstractExtractor $extractor, $alias): void
    {
        $this->extractors[$alias] = $extractor;
    }

    /**
     * Loop thru all extractor and return one that match.
     *
     * @param string $url An url
     *
     * @return AbstractExtractor|false
     */
    public function match($url)
    {
        foreach ($this->extractors as $alias => $extractor) {
            if (true === $extractor->match($url)) {
                return $extractor;
            }
        }

        return false;
    }
}
