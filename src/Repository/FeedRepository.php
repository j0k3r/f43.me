<?php

namespace App\Repository;

use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feed::class);
    }

    /**
     * Find feeds ordered by updated date.
     *
     * @param int|null $limit Items to retrieve
     *
     * @return array
     */
    public function findAllOrderedByDate($limit = null)
    {
        $q = $this->createQueryBuilder('f')
            ->orderBy('f.updatedAt', 'desc');

        if (null !== $limit) {
            $q->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Find feeds for public display.
     *
     * @return array
     */
    public function findForPublic()
    {
        return $this->createQueryBuilder('f')
            ->where('f.isPrivate = :isPrivate')->setParameter('isPrivate', false)
            ->orderBy('f.lastItemCachedAt', 'desc')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Find feed by ids.
     * Used in FetchItemCommand to retrieve feed that have / or not items.
     *
     * @param array  $ids  An array of id
     * @param string $type in or notIn
     *
     * @return mixed
     */
    public function findByIds($ids, $type = 'in')
    {
        $q = $this->createQueryBuilder('f');

        if ('in' === $type) {
            $q->where('f.id IN (:ids)')->setParameter('ids', $ids);
        } else {
            $q->where('f.id NOT IN (:ids)')->setParameter('ids', $ids);
        }

        return $q->getQuery()->execute();
    }
}
