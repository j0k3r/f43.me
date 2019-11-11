<?php

namespace AppBundle\Parser;

class ParserChain
{
    /** @var array */
    private $parsers;

    public function __construct()
    {
        $this->parsers = [];
    }

    /**
     * Add an parser to the chain.
     *
     * @param string $alias
     */
    public function addParser(AbstractParser $parser, $alias)
    {
        $this->parsers[$alias] = $parser;
    }

    /**
     * Get one parser by alias.
     *
     * @param string $alias
     *
     * @return bool|object
     */
    public function getParser($alias)
    {
        if (\array_key_exists($alias, $this->parsers)) {
            return $this->parsers[$alias];
        }

        return false;
    }

    /**
     * Loop thru all parser to find one that parse the content.
     *
     * @param string $url
     *
     * @return string
     */
    public function parseAll($url)
    {
        foreach ($this->parsers as $parser) {
            if ($content = $parser->parse($url)) {
                return $content;
            }
        }

        return '';
    }
}
