<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ItemsCachedEvent extends Event
{
    public const NAME = 'item.cached';

    /**
     * Feeds slug.
     *
     * @var array
     */
    protected $feedSlugs = [];

    /**
     * Store slug feeds that need to be dispatched.
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
