<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ItemsCachedEvent extends Event
{
    /**
     * Feeds slug.
     *
     * @var array
     */
    protected $feedSlugs = [];

    /**
     * Store slug feeds that need to be dispatched.
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
