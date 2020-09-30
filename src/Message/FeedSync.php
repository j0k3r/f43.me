<?php

namespace App\Message;

class FeedSync
{
    private $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
    }

    public function getfeedId(): int
    {
        return $this->feedId;
    }
}
