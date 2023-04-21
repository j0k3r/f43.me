<?php

namespace App\Xml;

use SimplePie\SimplePie;

class SimplePieProxy
{
    protected SimplePie $feed;

    /**
     * Create a new Proxy for SimplePie.
     *
     * @param string $cache       Path to cache folder
     * @param int    $itemLimit   The maximum number of items to return
     * @param bool   $enableCache Enable caching
     */
    public function __construct($cache, $itemLimit = 25, $enableCache = true)
    {
        $this->feed = new SimplePie();
        $this->feed->set_cache_location($cache);
        $this->feed->set_item_limit($itemLimit);

        // Force the given URL to be treated as a feed
        $this->feed->force_feed(true);
        $this->feed->enable_cache($enableCache);

        // be sure that the cache is writable by SimplePie
        if ($enableCache && !is_writable($cache)) {
            @mkdir($cache, 0777, true);
            chmod($cache, 0777);
        }
    }

    /**
     * Set the URL of the feed you want to parse.
     *
     * @param string $url
     *
     * @see  SimplePie->set_feed_url
     */
    public function setUrl($url): self
    {
        $this->feed->set_feed_url($url);

        return $this;
    }

    /**
     * Initialize the feed object.
     *
     * @see  SimplePie->init
     */
    public function init(): SimplePie
    {
        $this->feed->init();

        return $this->feed;
    }
}
