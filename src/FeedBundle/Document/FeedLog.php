<?php

namespace Api43\FeedBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="feedlogs")
 * @MongoDB\Document(repositoryClass="Api43\FeedBundle\Repository\FeedLogRepository")
 */
class FeedLog
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Int
     * @Assert\NotBlank()
     */
    protected $items_number;

    /**
     * @MongoDB\Date
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Feed", inversedBy="feeds")
     */
    protected $feed;

    /**
     * @MongoDB\ReferenceOne(targetDocument="FeedItem", inversedBy="feeditems")
     */
    protected $feeditem;

    /**
     * Get id.
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set items_number.
     *
     * @param int $itemsNumber
     *
     * @return self
     */
    public function setItemsNumber($itemsNumber)
    {
        $this->items_number = $itemsNumber;

        return $this;
    }

    /**
     * Get items_number.
     *
     * @return int $itemsNumber
     */
    public function getItemsNumber()
    {
        return $this->items_number;
    }

    /**
     * Set created_at.
     *
     * @param date $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set feed.
     *
     * @param Feed $feed
     *
     * @return self
     */
    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed.
     *
     * @return Api43\FeedBundle\Document\Feed $feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
