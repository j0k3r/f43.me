<?php

namespace AppBundle\Parser;

use Graby\Graby;

/**
 * Retrieve content from an internal library instead of a webservice.
 * It's a fallback by default, but can be the only solution if specified.
 */
class Internal extends AbstractParser
{
    protected $graby;

    /**
     * @param Graby $graby
     */
    public function __construct(Graby $graby)
    {
        $this->graby = $graby;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url)
    {
        try {
            $result = $this->graby->fetchContent($url);
        } catch (\Exception $e) {
            return '';
        }

        if (isset($result['html']) && false !== $result['html']) {
            return $result['html'];
        }

        return '';
    }
}
