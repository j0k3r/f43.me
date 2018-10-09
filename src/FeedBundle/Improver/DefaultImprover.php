<?php

namespace Api43\FeedBundle\Improver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Default Improver.
 *
 * This class provides Improver methods
 */
class DefaultImprover
{
    protected $url;
    protected $itemContent;
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
            $response = $this->client
                ->get(
                    $url,
                    // force user agent for some provider (to avoid bad browser detection)
                    ['headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.92 Safari/535.2',
                    ]]
                );
        } catch (RequestException $e) {
            // catch timeout, ssl verification that failed, etc ...
            return $url . (strpos($url, '?') ? '&' : '?') . 'not-changed';
        } catch (\Exception $e) {
            // in case anything else happend
            return $url . (strpos($url, '?') ? '&' : '?') . 'oups';
        }

        $url = $response->getEffectiveUrl();
        // convert bad encoded character
        $url = str_replace('&amp%3B', '&', $url);

        // extract query parameters
        $query = parse_url($url, PHP_URL_QUERY);
        if (empty($query)) {
            return $url;
        }

        // remove utm parameters (utm_source, utm_medium, utm_campaign, etc ...)
        parse_str($query, $queryExploded);

        $notUtmParameters = array_filter(array_keys($queryExploded), function ($k) {
            return 0 !== strpos($k, 'utm');
        });
        $newQuery = array_intersect_key($queryExploded, array_flip($notUtmParameters));

        // remove all parameters from url to re-add them later
        $url = strtok($url, '?');

        if (empty($newQuery)) {
            return $url;
        }

        // re-add allowed parameters
        return $url . '?' . http_build_query($newQuery);
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
