<?php

namespace j0k3r\FeedBundle\Services;

class SimplePieProxy
{
    protected $feed;

    public function __construct($cache)
    {
        $this->feed = new \SimplePie();
        $this->feed->set_cache_location($cache);

        // be sure that the cache is writable by SimplePie
        if (!is_writable($cache)) {
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
