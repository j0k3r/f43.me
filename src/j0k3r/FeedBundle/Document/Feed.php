<?php

namespace j0k3r\FeedBundle\Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;

use j0k3r\FeedBundle\Formatter;

/**
 * @MongoDB\Document(collection="feeds")
 * @MongoDB\Document(repositoryClass="j0k3r\FeedBundle\Repository\FeedRepository")
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
     * @MongoDB\String
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @MongoDB\String
     */
    protected $description;

    /**
     * @MongoDB\String
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $link;

    /**
     * @MongoDB\String
     */
    protected $parser;

    /**
     * @MongoDB\String
     */
    protected $formatter;

    /**
     * @Gedmo\Slug(fields={"name"}, updatable=false, unique=true)
     * @MongoDB\String
     * @MongoDB\Index
     */
    protected $slug;

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
     * @MongoDB\ReferenceMany(targetDocument="FeedItem", mappedBy="feed")
     */
    protected $feeditems;

    /**
     * @MongoDB\ReferenceMany(targetDocument="FeedLog", mappedBy="feed")
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
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set link
     *
     * @param string $link
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
     * Set created_at
     *
     * @param date $createdAt
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
     * @param date $updatedAt
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
     * Set slug
     *
     * @param string $slug
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get slug
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
     * Add feeditems
     *
     * @param j0k3r\FeedBundle\Document\FeedItem $feeditems
     */
    public function addFeeditem(\j0k3r\FeedBundle\Document\FeedItem $feeditems)
    {
        $this->feeditems[] = $feeditems;
    }

    /**
    * Remove feeditems
    *
    * @param <variableType$feeditems
    */
    public function removeFeeditem(\j0k3r\FeedBundle\Document\FeedItem $feeditems)
    {
        $this->feeditems->removeElement($feeditems);
    }

    /**
     * Get feeditems
     *
     * @return Doctrine\Common\Collections\Collection $feeditems
     */
    public function getFeeditems()
    {
        return $this->feeditems;
    }

    /**
     * Set parser
     *
     * @param string $parser
     * @return self
     */
    public function setParser($parser)
    {
        $this->parser = $parser;
        return $this;
    }

    /**
     * Get parser
     *
     * @return string $parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Add feedlogs
     *
     * @param j0k3r\FeedBundle\Document\FeedLog $feedlogs
     */
    public function addFeedlog(\j0k3r\FeedBundle\Document\FeedLog $feedlogs)
    {
        $this->feedlogs[] = $feedlogs;
    }

    /**
    * Remove feedlogs
    *
    * @param <variableType$feedlogs
    */
    public function removeFeedlog(\j0k3r\FeedBundle\Document\FeedLog $feedlogs)
    {
        $this->feedlogs->removeElement($feedlogs);
    }

    /**
     * Get feedlogs
     *
     * @return Doctrine\Common\Collections\Collection $feedlogs
     */
    public function getFeedlogs()
    {
        return $this->feedlogs;
    }

    /**
     * Set formatter
     *
     * @param string $formatter
     * @return self
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * Get formatter
     *
     * @return string $formatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Return main domain from link:
     *     http://site.com/feed/index.xml -> http://site.com
     *
     * @return string
     */
    public function getHost()
    {
        $url = parse_url($this->getLink());

        return $url['scheme'].'://'.$url['host'];
    }

    /**
     * Render the feed in specified format
     *
     * @param collection  $items  All items to render
     *
     * @return string
     *
     * @throws \InvalidArgumentException if given format formatter does not exists
     */
    public function render($items)
    {
        switch ($this->getFormatter()) {
            case 'rss':
                $formatter = new Formatter\RssFormatter($this, $items);
                break;

            case 'atom':
                $formatter = new Formatter\AtomFormatter($this, $items);
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf("Format '%s' is not available. Please see documentation.", $format)
                );
                break;
        }

        return $formatter->render();
    }
}
