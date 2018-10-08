<?php

namespace AppBundle\Repository;

use AppBundle\Document\Feed;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class FeedRepository extends ServiceDocumentRepository
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
     * @return \Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findAllOrderedByDate($limit = null)
    {
        $q = $this->createQueryBuilder()
            ->sort('updated_at', 'DESC');

        if (null !== $limit) {
            $q->limit($limit);
        }

        return $q->getQuery()->execute();
    }

    /**
     * Find feeds for public display.
     *
     * @return \Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findForPublic()
    {
        $q = $this->createQueryBuilder()
            ->field('is_private')->equals(false)
            ->sort('last_item_cached_at', 'DESC');

        return $q->getQuery()->execute();
    }

    /**
     * Find feed by ids.
     * Used in FetchItemCommand to retrieve feed that have / or not items.
     *
     * @param array  $ids  An array of MongoID
     * @param string $type in or notIn
     *
     * @return \Doctrine\ODM\MongoDB\EagerCursor|bool
     */
    public function findByIds($ids, $type = 'in')
    {
        $q = $this->createQueryBuilder()
            ->field('id');

        if ('in' === $type) {
            $q->in($ids);
        } else {
            $q->notIn($ids);
        }

        return $q->getQuery()
            ->execute();
    }
}
