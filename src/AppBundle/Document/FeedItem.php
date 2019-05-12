<?php

namespace AppBundle\Document;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="feeditems")
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\FeedItemRepository")
 * @MongoDBUnique(fields="link")
 */
class FeedItem
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $title;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $link;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $permalink;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $content;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $published_at;

    /**
     * @MongoDB\Field(type="date")
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @MongoDB\Field(type="date")
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

    public function __construct()
    {
        $this->feedlogs = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set title.
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set link.
     *
     * @param string $link
     *
     * @return self
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link.
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string $content
     */
    public function getContent()
    {
        return $this->content;
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
     * Set updated_at.
     *
     * @param string $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at.
     *
     * @return string $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set permalink.
     *
     * @param string $permalink
     *
     * @return self
     */
    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;

        return $this;
    }

    /**
     * Get permalink.
     *
     * @return string $permalink
     */
    public function getPermalink()
    {
        return $this->permalink;
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

    /**
     * Set published_at.
     *
     * @param string $publishedAt
     *
     * @return self
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at.
     *
     * @return string $publishedAt
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Retrieve the "publication" date *only* used in the RSS/Atom feed.
     * Depending on the feed, we want the published_at date or the created_at date.
     *
     * @return string
     */
    public function getPubDate()
    {
        return ('published_at' === $this->feed->getSortBy()) ? $this->getPublishedAt() : $this->getCreatedAt();
    }
}
