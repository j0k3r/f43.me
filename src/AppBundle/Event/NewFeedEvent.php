<?php

namespace AppBundle\Event;

use AppBundle\Entity\Feed;
use Symfony\Component\EventDispatcher\Event;

class NewFeedEvent extends Event
{
    /**
     * Feed entity.
     *
     * @var Feed
     */
    protected $feed;

    /**
     * Store slug feed that need to be dispatched.
     *
     * @param Feed $feed
     */
    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
    }

    public function getFeed()
    {
        return $this->feed;
    }
}
