<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="item"
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ItemRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Item
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="title", type="text")
     */
    protected $title;

    /**
     * @ORM\Column(name="link", type="string", length=191)
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $link;

    /**
     * @ORM\Column(name="permalink", type="string")
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $permalink;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     */
    protected $publishedAt;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Feed", inversedBy="items")
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id")
     */
    protected $feed;

    /**
     * @ORM\OneToMany(targetEntity="Log", mappedBy="item")
     */
    protected $logs;

    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
        $this->logs = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int $id
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
     * Set createdAt.
     *
     * @param string|\DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return string|\DateTime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param string|\DateTime $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return string|\DateTime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
     * Set publishedAt.
     *
     * @param string|\DateTime $publishedAt
     *
     * @return self
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Get publishedAt.
     *
     * @return string|\DateTime $publishedAt
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Retrieve the "publication" date *only* used in the RSS/Atom feed.
     * Depending on the feed, we want the published_at date or the created_at date.
     *
     * @return string|\DateTime
     */
    public function getPubDate()
    {
        return ('published_at' === $this->feed->getSortBy()) ? $this->getPublishedAt() : $this->getCreatedAt();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function timestamps()
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }

        $this->updatedAt = new \DateTime();
    }

    /**
     * Return feed.
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
