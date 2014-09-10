<?php

namespace j0k3r\FeedBundle\Improver;

/**
 * Default Improver (aka Nothing)
 *
 * This class provides Improver methods
 */
class Nothing
{
    protected $url;
    protected $itemContent;

    /**
     * Set RSS item url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set RSS item content
     *
     * @param string $itemContent
     */
    public function setItemContent($itemContent)
    {
        $this->itemContent = $itemContent;
    }

    /**
     * Will tell if this host (of the feed) should be handled by this improver.
     *
     * @param string $host
     *
     * @return bool
     */
    public function match($host)
    {
        return true;
    }

    /**
     * This a method to retrieve url from the item content.
     * For example, if we want to extract the url from the item instead of using the default link.
     * Could be the case for Reddit (retrieving the [link] url instead of the Reddit one)
     *
     * @param string $url RSS item url
     *
     * @return string Url to be used to retrieve content
     */
    public function updateUrl($url)
    {
        return $url;
    }

    /**
     * Further action to be done on the readable content.
     * For example, it may be added to the item content.
     *
     * @param string $readableContent Readable item content
     *
     * @return string
     */
    public function updateContent($readableContent)
    {
        return $readableContent;
    }
}
