<?php

namespace Api43\FeedBundle\Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Api43\FeedBundle\Validator\Constraints as FeedAssert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;

/**
 * @MongoDB\Document(collection="feeds")
 * @MongoDB\Document(repositoryClass="Api43\FeedBundle\Repository\FeedRepository")
 * @MongoDBUnique(fields="slug")
 * @MongoDBUnique(fields="link")
 */
class Feed
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     * @FeedAssert\ConstraintRss
     */
    protected $link;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     */
    protected $host;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $logo;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $color;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $parser;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $formatter;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $nb_items = 0;

    /**
     * @Gedmo\Slug(fields={"name"}, updatable=false, unique=true)
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    protected $slug;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $is_private = false;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $sort_by;

    /**
     * @MongoDB\Date
     */
    protected $last_item_cached_at;

    /**
     * @MongoDB\Date
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @MongoDB\Date
     * @Gedmo\Timestampable(on="change", field={"name", "description", "link", "host", "parser", "formatter", "is_private", "sort_by"})
     */
    protected $updated_at;

    /**
     * @MongoDB\ReferenceMany(targetDocument="FeedItem", mappedBy="feed")
     */
    protected $feeditems;

    /**
     * @MongoDB\ReferenceMany(targetDocument="FeedLog", mappedBy="feed")
     */
    protected $feedlogs;

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
     * Set name.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
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
     * Set updated_at.
     *
     * @param date $updatedAt
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
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function __construct()
    {
        $this->feeditems = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add feeditems.
     *
     * @param FeedItem $feeditems
     */
    public function addFeeditem(FeedItem $feeditems)
    {
        $this->feeditems[] = $feeditems;
    }

    /**
     * Remove feeditems.
     *
     * @param FeedItem $feeditems
     */
    public function removeFeeditem(FeedItem $feeditems)
    {
        $this->feeditems->removeElement($feeditems);
    }

    /**
     * Get feeditems.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $feeditems
     */
    public function getFeeditems()
    {
        return $this->feeditems;
    }

    /**
     * Set parser.
     *
     * @param string $parser
     *
     * @return self
     */
    public function setParser($parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Get parser.
     *
     * @return string $parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Add feedlogs.
     *
     * @param FeedLog $feedlogs
     */
    public function addFeedlog(FeedLog $feedlogs)
    {
        $this->feedlogs[] = $feedlogs;
    }

    /**
     * Remove feedlogs.
     *
     * @param FeedLog $feedlogs
     */
    public function removeFeedlog(FeedLog $feedlogs)
    {
        $this->feedlogs->removeElement($feedlogs);
    }

    /**
     * Get feedlogs.
     *
     * @return FeedLog[] $feedlogs
     */
    public function getFeedlogs()
    {
        return $this->feedlogs;
    }

    /**
     * Set formatter.
     *
     * @param string $formatter
     *
     * @return self
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Get formatter.
     *
     * @return string $formatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Set host.
     *
     * @param string $host
     *
     * @return self
     */
    public function setHost($host)
    {
        $parsedHost = parse_url($host, PHP_URL_HOST);

        // be sure that host doesn't have http
        if ($parsedHost) {
            $host = $parsedHost;
        }

        $this->host = $host;

        return $this;
    }

    /**
     * Get host.
     *
     * @return string $host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set is_private.
     *
     * @param bool $isPrivate
     *
     * @return self
     */
    public function setIsPrivate($isPrivate)
    {
        $this->is_private = $isPrivate;

        return $this;
    }

    /**
     * Get is_private.
     *
     * @return bool $isPrivate
     */
    public function getIsPrivate()
    {
        return $this->is_private;
    }

    /**
     * Set sort_by.
     *
     * @param string $sortBy
     *
     * @return self
     */
    public function setSortBy($sortBy)
    {
        $this->sort_by = $sortBy;

        return $this;
    }

    /**
     * Get sort_by.
     *
     * @return string $sortBy
     */
    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Set lastItemCachedAt.
     *
     * @param \DateTime $lastItemCachedAt
     *
     * @return self
     */
    public function setLastItemCachedAt($lastItemCachedAt)
    {
        $this->last_item_cached_at = $lastItemCachedAt;

        return $this;
    }

    /**
     * Get lastItemCachedAt.
     *
     * @return \DateTime $lastItemCachedAt
     */
    public function getLastItemCachedAt()
    {
        return $this->last_item_cached_at;
    }

    /**
     * Set nbItems.
     *
     * @param int $nbItems
     *
     * @return self
     */
    public function setNbItems($nbItems)
    {
        $this->nb_items = $nbItems;

        return $this;
    }

    /**
     * Get nbItems.
     *
     * @return int $nbItems
     */
    public function getNbItems()
    {
        return $this->nb_items;
    }

    /**
     * Set logo.
     *
     * @param int $logo
     *
     * @return self
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return int $logo
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set color.
     *
     * @param int $color
     *
     * @return self
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return int $color
     */
    public function getColor()
    {
        return $this->color;
    }
}
