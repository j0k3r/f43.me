<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="feedlogs")
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\FeedLogRepository")
 */
class FeedLog
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")
     * @Assert\NotBlank()
     */
    protected $items_number;

    /**
     * @MongoDB\Field(type="date")
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
     * @return string $id
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
     * @param string $createdAt
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
     * @return string $createdAt
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
     * @return Feed $feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
