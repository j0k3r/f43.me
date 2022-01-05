<?php

namespace App\Parser;

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
    public function addParser(AbstractParser $parser, $alias): void
    {
        $this->parsers[$alias] = $parser;
    }

    /**
     * Get one parser by alias.
     *
     * @return false|AbstractParser
     */
    public function getParser(string $alias)
    {
        if (\array_key_exists($alias, $this->parsers)) {
            return $this->parsers[$alias];
        }

        return false;
    }

    /**
     * Loop thru all parser to find one that parse the content.
     */
    public function parseAll(string $url): string
    {
        foreach ($this->parsers as $parser) {
            $content = $parser->parse($url);
            if ($content) {
                return $content;
            }
        }

        return '';
    }
}
