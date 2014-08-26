<?php

namespace j0k3r\FeedBundle\Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;

/**
 * @MongoDB\Document(collection="feeditems")
 * @MongoDB\Document(repositoryClass="j0k3r\FeedBundle\Repository\FeedItemRepository")
 * @MongoDBUnique(fields="link")
 */
class FeedItem
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $title;

    /**
     * @MongoDB\String
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $link;

    /**
     * @MongoDB\String
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $permalink;

    /**
     * @MongoDB\String
     */
    protected $content;

    /**
     * @MongoDB\Date
     */
    protected $published_at;

    /**
     * @MongoDB\Date
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @MongoDB\Date
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated_at;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Feed", inversedBy="feeditems")
     */
    protected $feed;

    /**
     * @MongoDB\ReferenceMany(targetDocument="FeedLog", mappedBy="feeditem")
     */
    protected $feedlogs;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param  string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set link
     *
     * @param  string $link
     * @return self
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set content
     *
     * @param  string $content
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created_at
     *
     * @param  date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param  date $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set permalink
     *
     * @param  string $permalink
     * @return self
     */
    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;

        return $this;
    }

    /**
     * Get permalink
     *
     * @return string $permalink
     */
    public function getPermalink()
    {
        return $this->permalink;
    }

    /**
     * Set feed
     *
     * @param  \j0k3r\FeedBundle\Document\Feed $feed
     * @return self
     */
    public function setFeed(\j0k3r\FeedBundle\Document\Feed $feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return j0k3r\FeedBundle\Document\Feed $feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set published_at
     *
     * @param  date $publishedAt
     * @return self
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at
     *
     * @return date $publishedAt
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    public function __construct()
    {
        $this->feedlogs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Retrieve the "publication" date *only* used in the RSS/Atom feed.
     * Depending on the feed, we want the published_at date or the created_at date
     *
     * @return date
     */
    public function getPubDate()
    {
        return ('published_at' == $this->feed->getSortBy()) ? $this->getPublishedAt() : $this->getCreatedAt();
    }
}
