<?php

namespace App\Event;

use App\Entity\Feed;
use Symfony\Contracts\EventDispatcher\Event;

class NewFeedEvent extends Event
{
    public const NAME = 'feed.created';

    /**
     * Store slug feed that need to be dispatched.
     */
    public function __construct(protected Feed $feed)
    {
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }
}
