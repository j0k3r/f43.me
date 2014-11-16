<?php

namespace j0k3r\FeedBundle\Extractor;

abstract class AbstractExtractor
{
    /**
     * Will tell if this url should be handled by this extrator.
     *
     * @param string $url
     *
     * @return bool
     */
    abstract public function match($url);

    /**
     * Return the content from the extractor
     *
     * @return string|false Content expanded
     */
    abstract public function getContent();
}
