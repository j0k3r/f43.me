<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * DefaultParser
 *
 * This class provides parser methods
 */
class DefaultParser
{
    protected $url;
    protected $itemContent;

    /**
     *
     * @param string $itemContent RSS item content
     * @param string $url         RSS item url
     */
    public function __construct($url, $itemContent)
    {
        $this->url = $url;
        $this->itemContent = $itemContent;
    }

    /**
     * This a method to retrieve url from the item content.
     * For example, if we want to extract the url from the item instead of using the default link.
     * Could be the case for Reddit (retrieving the [link] url instead of the Reddit one)
     *
     *
     * @return string Url to be used to retrieve content
     */
    public function retrieveUrl()
    {
        return $this->url;
    }

    /**
     * Further action to be done on the readable content.
     * For example, it may be added to the item content.
     *
     * @param string $content Readable item content
     *
     * @return string
     */
    public function updateContent($content)
    {
        return $content;
    }
}
