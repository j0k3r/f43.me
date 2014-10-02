<?php

namespace j0k3r\FeedBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FeedItemEvent extends Event
{
    /**
     * Feeds slug
     *
     * @var array
     */
    protected $feedSlugs = array();

    /**
     * Store slug feeds that need to be dispatched
     *
     * @param array $feedSlugs
     */
    public function __construct(array $feedSlugs)
    {
        $this->feedSlugs = $feedSlugs;
    }

    public function getFeedSlugs()
    {
        return $this->feedSlugs;
    }
}
