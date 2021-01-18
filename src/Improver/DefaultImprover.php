<?php

namespace App\Improver;

use Http\Client\Common\HttpMethodsClientInterface;

/**
 * Default Improver.
 *
 * This class provides Improver methods
 */
class DefaultImprover
{
    /** @var string */
    protected $url;
    /** @var string */
    protected $itemContent;
    /** @var HttpMethodsClientInterface */
    protected $client;

    public function __construct(HttpMethodsClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Set RSS item url.
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Set RSS item content.
     */
    public function setItemContent(string $itemContent): void
    {
        $this->itemContent = $itemContent;
    }

    /**
     * Will tell if this host (of the feed) should be handled by this improver.
     */
    public function match(string $host): bool
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
    public function updateUrl(string $url): string
    {
        // convert bad encoded character
        $url = str_replace('&amp%3B', '&', $url);

        // extract query parameters
        $query = parse_url($url, \PHP_URL_QUERY);
        if (empty($query)) {
            return $url;
        }

        // remove utm parameters (utm_source, utm_medium, utm_campaign, etc ...)
        parse_str($query, $queryExploded);

        $notUtmParameters = array_filter(array_keys($queryExploded), function ($k) {
            return 0 !== strpos((string) $k, 'utm');
        });
        $newQuery = array_intersect_key($queryExploded, array_flip($notUtmParameters));

        // remove all parameters from url to re-add them later
        $url = strtok($url, '?');

        if (empty($newQuery)) {
            return (string) $url;
        }

        // re-add allowed parameters
        return $url . '?' . http_build_query($newQuery);
    }

    /**
     * Further action to be done on the readable content.
     * For example, it may be added to the item content.
     *
     * @param string $readableContent Readable item content
     */
    public function updateContent(string $readableContent): string
    {
        return $readableContent;
    }
}
