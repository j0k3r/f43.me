<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * Find all items for a given Feed id.
     *
     * @param int    $feedId Feed id
     * @param string $sortBy Feed sort by
     *
     * @return mixed
     */
    public function findByFeed($feedId, $sortBy)
    {
        return $this->getItemsByFeedIdQuery($feedId, ['sort_by' => $sortBy])
            ->execute();
    }

    /**
     * Retrieve the last item for a given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return array|object|null
     */
    public function findLastItemByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId, ['limit' => 1])
            ->getOneOrNullResult();
    }

    /**
     * Return feeds which HAVE items.
     *
     * @return array of id
     */
    public function findAllFeedWithItems()
    {
        $items = $this->createQueryBuilder('i')
            ->select('f.id, COUNT(i)')
            ->leftJoin('i.feed', 'f')
            ->groupBy('f.id')
            ->getQuery()
            ->getArrayResult();

        $res = [];
        foreach ($items as $item) {
            $res[] = $item['id'];
        }

        return $res;
    }

    /**
     * Retrieve all links from cached item for a given id.
     * Link are used as a "unique" key for item.
     *
     * @param int $feedId Feed id
     *
     * @return array
     */
    public function getAllLinks($feedId)
    {
        $res = $this->createQueryBuilder('i')
            ->select('i.permalink')
            ->leftJoin('i.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId)
            ->orderBy('i.publishedAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // store as key to avoid duplicate (even if it doesn't have to happen)
        // and also because it's faster to isset than in_array to match a value
        $results = [];
        foreach ($res as $item) {
            $results[$item['permalink']] = true;
        }

        return $results;
    }

    /**
     * Find all items starting at $skip.
     * Used to remove all old items.
     * I can't find a way to perform the remove in one query (remove & skip doesn't want to work *well* together).
     *
     * @param int $feedId Feed id
     * @param int $skip   Items to keep
     *
     * @return mixed
     */
    public function findOldItemsByFeedId($feedId, $skip = 100)
    {
        return $this->createQueryBuilder('i')
            ->select('i, f')
            ->leftJoin('i.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId)
            ->orderBy('i.publishedAt', 'desc')
            ->setFirstResult((int) $skip)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove all items associated to the given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return int
     */
    public function deleteAllByFeedId($feedId)
    {
        return $this->getEntityManager()
            ->createQuery("DELETE FROM AppBundle\Entity\Item i WHERE i.feed = :feedId")
            ->setParameter('feedId', $feedId)
            ->execute();
    }

    /**
     * Count items for a given feed id.
     *
     * @param int $feedId Feed id
     *
     * @return int
     */
    public function countByFeedId($feedId)
    {
        return $this->createQueryBuilder('i')
            ->select('count(i.id)')
            ->leftJoin('i.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get the base query to fetch items.
     *
     * @param int   $feedId  Feed id
     * @param array $options limit, sort_by, skip
     *
     * @return \Doctrine\ORM\Query
     */
    private function getItemsByFeedIdQuery($feedId, $options = [])
    {
        $q = $this->createQueryBuilder('i')
            ->select('i, f')
            ->leftJoin('i.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId);

        if (isset($options['sort_by']) && $options['sort_by']) {
            // convert `created_at` in `createdAt`
            $sort = str_replace('_a', 'A', $options['sort_by']);
            $q->orderBy('i.' . $sort, 'DESC');
        } else {
            $q->orderBy('i.publishedAt', 'DESC');
        }

        if (isset($options['limit']) && $options['limit']) {
            $q->setMaxResults($options['limit']);
        }

        if (isset($options['skip']) && $options['skip']) {
            $q->setFirstResult($options['skip']);
        }

        return $q->getQuery();
    }
}
