<?php

namespace j0k3r\FeedBundle\Parser;

class ParserChain
{
    private $parsers;

    public function __construct()
    {
        $this->parsers = array();
    }

    /**
     * Add an parser to the chain
     *
     * @param  AbstractParser    $parser
     * @param  string     $alias
     */
    public function addParser(AbstractParser $parser, $alias)
    {
        $this->parsers[$alias] = $parser;
    }

    /**
     * Get one parser by alias
     *
     * @param string $alias
     *
     * @return bool|object
     */
    public function getParser($alias)
    {
        if (array_key_exists($alias, $this->parsers)) {
           return $this->parsers[$alias];
        }

        return false;
    }

    /**
     * Loop thru all parser to find one that parse the content
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
