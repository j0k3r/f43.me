<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'log')]
#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Log
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'items_number', type: 'integer')]
    protected $itemsNumber;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected $createdAt;

    /**
     * @var Feed|null
     */
    #[ORM\JoinColumn(name: 'feed_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Feed::class, inversedBy: 'logs')]
    protected $feed;

    /**
     * @var Item|null
     */
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'logs')]
    protected $item;

    public function __construct(Feed $feed)
    {
        $this->feed = $feed;
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
     * Set itemsNumber.
     *
     * @param int $itemsNumber
     *
     * @return self
     */
    public function setItemsNumber($itemsNumber)
    {
        $this->itemsNumber = $itemsNumber;

        return $this;
    }

    /**
     * Get itemsNumber.
     *
     * @return int $itemsNumber
     */
    public function getItemsNumber()
    {
        return $this->itemsNumber;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
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
     * @return \DateTime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function timestamps(): void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Return feed.
     *
     * @return Feed|null
     */
    public function getFeed()
    {
        return $this->feed;
    }
}
