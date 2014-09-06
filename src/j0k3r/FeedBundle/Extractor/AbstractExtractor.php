<?php

namespace j0k3r\FeedBundle\Extractor;

abstract class AbstractExtractor
{
    protected $url;

    /**
     * Will tell if this url should be handled by this extrator.
     *
     * @param  string $url
     *
     * @return bool
     */
    abstract public function match($url);

    /**
     * Return the url after the extractor update it
     *
     * @return string Url updated
     */
    abstract public function getUrl();

    /**
     * Return the content from the extractor
     *
     * @return string Content expanded
     */
    abstract public function getContent();
}
