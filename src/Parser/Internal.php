<?php

namespace App\Parser;

use Graby\Graby;

/**
 * Retrieve readable content with Graby.
 */
class Internal
{
    public function __construct(protected Graby $graby)
    {
    }

    public function parse(string $url, bool $reloadConfigFiles = false): string
    {
        if (true === $reloadConfigFiles) {
            $this->graby->reloadConfigFiles();
        }

        try {
            $result = $this->graby->fetchContent($url);
        } catch (\Exception) {
            return '';
        }

        return $result->getHtml();
    }
}
