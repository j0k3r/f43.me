<?php

namespace AppBundle\Converter;

class ConverterChain
{
    private $converters;

    public function __construct()
    {
        $this->converters = [];
    }

    /**
     * Add an converter to the chain.
     *
     * @param AbstractConverter $converter
     * @param string            $alias
     */
    public function addConverter(AbstractConverter $converter, $alias)
    {
        $this->converters[$alias] = $converter;
    }

    /**
     * Loop thru all converter and convert content.
     *
     * @param string $html Article content
     *
     * @return string
     */
    public function convert($html)
    {
        foreach ($this->converters as $converter) {
            $html = $converter->convert($html);
        }

        return $html;
    }
}
