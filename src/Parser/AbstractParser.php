<?php

namespace App\Parser;

abstract class AbstractParser
{
    /**
     * Make a readable content from the given url.
     *
     * @param bool $reloadConfigFiles For SiteConfig files to be reloaded
     */
    abstract public function parse(string $url, bool $reloadConfigFiles = false): string;
}
