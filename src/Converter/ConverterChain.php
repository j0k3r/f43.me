<?php

namespace App\Converter;

class ConverterChain
{
    /** @var array */
    private $converters;

    public function __construct()
    {
        $this->converters = [];
    }

    /**
     * Add an converter to the chain.
     */
    public function addConverter(AbstractConverter $converter, string $alias): void
    {
        $this->converters[$alias] = $converter;
    }

    /**
     * Loop thru all converter and convert content.
     *
     * @param string $html Article content
     */
    public function convert(string $html): string
    {
        foreach ($this->converters as $converter) {
            $html = $converter->convert($html);
        }

        return $html;
    }
}
