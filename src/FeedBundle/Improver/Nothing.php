<?php

namespace Api43\FeedBundle\Improver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Default Improver (aka Nothing).
 *
 * This class provides Improver methods
 */
class Nothing
{
    protected $url;
    protected $itemContent;
    protected $guzzle;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Set RSS item url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set RSS item content.
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
     * Could be the case for Reddit (retrieving the [link] url instead of the Reddit one).
     *
     * @param string $url RSS item url
     *
     * @return string Url to be used to retrieve content
     */
    public function updateUrl($url)
    {
        try {
            $response = $this->guzzle
                ->get(
                    $url,
                    // force user agent for some provider (to avoid bad browser detection)
                    array('headers' => array('User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.92 Safari/535.2'))
                );
        } catch (RequestException $e) {
            // catch timeout, ssl verification that failed, etc ...
            return $url.(strpos($url, '?') ? '&' : '?').'not-changed';
        }

        // remove utm parameters & fragment
        $effectiveUrl = preg_replace('/((\?)?(&(amp;)?)?utm_(.*?)\=[^&]+)|(#(.*?)\=[^&]+)/', '', urldecode($response->getEffectiveUrl()));

        return $effectiveUrl;
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
