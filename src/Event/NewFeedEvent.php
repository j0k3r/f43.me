<?php

namespace App\Event;

use App\Entity\Feed;
use Symfony\Contracts\EventDispatcher\Event;

class NewFeedEvent extends Event
{
    public const NAME = 'feed.created';

    /**
     * Feed entity.
     *
     * @var Feed
     */
    protected $feed;

    /**
     * Store slug feed that need to be dispatched.
     */
    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }
}
