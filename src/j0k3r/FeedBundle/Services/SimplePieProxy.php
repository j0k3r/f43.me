<?php

namespace j0k3r\FeedBundle\Services;

class SimplePieProxy
{
    protected $feed;

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

    public function setUrl($url)
    {
        $this->feed->set_feed_url($url);

        return $this;
    }

    public function init()
    {
        $this->feed->init();

        return $this->feed;
    }
}
