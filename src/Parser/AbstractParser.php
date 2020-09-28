<?php

namespace App\Parser;

abstract class AbstractParser
{
    /**
     * Make a readable content from the given url.
     *
     * @param string $url
     * @param bool   $reloadConfigFiles For SiteConfig files to be reloaded
     *
     * @return string
     */
    abstract public function parse($url, $reloadConfigFiles = false);
}
