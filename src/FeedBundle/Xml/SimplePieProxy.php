<?php

namespace Api43\FeedBundle\Xml;

class SimplePieProxy
{
    protected $feed;

    /**
     * Create a new Proxy for SimplePie.
     *
     * @param string $cache        Path to cache folder
     * @param int    $item_limit   The maximum number of items to return.
     * @param bool   $enable_cache Enable caching
     */
    public function __construct($cache, $item_limit = 25, $enable_cache = true)
    {
        $this->feed = new \SimplePie();
        $this->feed->set_cache_location($cache);
        $this->feed->set_item_limit($item_limit);

        // Force the given URL to be treated as a feed
        $this->feed->force_feed(true);
        $this->feed->enable_cache($enable_cache);

        // be sure that the cache is writable by SimplePie
        if ($enable_cache && !is_writable($cache)) {
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
    public function setUrl($url)
    {
        $this->feed->set_feed_url($url);

        return $this;
    }

    /**
     * Initialize the feed object.
     *
     * @return \SimplePie
     *
     * @see  SimplePie->init
     */
    public function init()
    {
        $this->feed->init();

        return $this->feed;
    }
}
