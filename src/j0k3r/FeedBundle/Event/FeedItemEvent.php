<?php

namespace j0k3r\FeedBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FeedItemEvent extends Event
{
    /**
     * Feeds api url
     *
     * @var array
     */
    protected $feedUrls = array();

    /**
     * Store feed urls that need to be dispatched
     *
     * @param array $feedUrls
     */
    public function __construct(array $feedUrls)
    {
        $this->feedUrls = $feedUrls;
    }

    public function getFeedUrls()
    {
        return $this->feedUrls;
    }
}
