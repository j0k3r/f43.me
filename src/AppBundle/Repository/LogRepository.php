<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Find all logs ordered by id desc.
     *
     * @param int|null $limit Items to retrieve
     *
     * @return array
     */
    public function findAllOrderedById($limit = null)
    {
        $q = $this->createQueryBuilder('l')
            ->select('l, f')
            ->leftJoin('l.feed', 'f')
            ->orderBy('l.id', 'DESC');

        if (null !== $limit) {
            $q->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Find all logs for a given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return mixed
     */
    public function findByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId)
            ->execute();
    }

    /**
     * Retrieve the last log for a given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return array|object|null
     */
    public function findLastItemByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId, 1)
            ->getOneOrNullResult();
    }

    /**
     * Return an array of total items fetched per day:.
     *
     *   array (
     *     '8/6/2013' => 43,
     *     '9/6/2013' => 60,
     *     '11/6/2013' => 55,
     *   )
     *
     * @param int $limit Limit of results to show in the dashboard chart
     *
     * @return array
     */
    public function findStatsForLastDays($limit = 20)
    {
        $res = $this->createQueryBuilder('l')
            ->select('DATE(l.createdAt) as date, count(l.id) as total')
            ->groupBy('date')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $results = [];
        foreach ($res as $result) {
            list($year, $month, $day) = explode('-', $result['date']);
            $results[$day . '/' . $month . '/' . $year] = $result['total'];
        }

        return $results;
    }

    /**
     * Remove all logs associated to the given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return int
     */
    public function deleteAllByFeedId($feedId)
    {
        return $this->getEntityManager()
            ->createQuery("DELETE FROM AppBundle\Entity\Log l WHERE l.feed = :feedId")
            ->setParameter('feedId', $feedId)
            ->execute();
    }

    /**
     * Count all feed logs by feed id.
     *
     * @param int $feedId Feed id
     *
     * @return int Number of items
     */
    public function countByFeedId($feedId)
    {
        return $this->createQueryBuilder('l')
            ->select('count(l.id)')
            ->leftJoin('l.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get the base query to fetch items.
     *
     * @param int      $feedId Feed id
     * @param int|null $limit  Number of items to return
     * @param int|null $skip   Item to skip before applying the limit
     *
     * @return \Doctrine\ORM\Query
     */
    private function getItemsByFeedIdQuery($feedId, $limit = null, $skip = null)
    {
        $q = $this->createQueryBuilder('l')
            ->select('l, f')
            ->leftJoin('l.feed', 'f')
            ->where('f.id = :feedId')->setParameter('feedId', $feedId)
            ->orderBy('l.id', 'desc');

        if (null !== $limit) {
            $q->setMaxResults($limit);
        }

        if (null !== $skip) {
            $q->setFirstResult($skip);
        }

        return $q->getQuery();
    }
}
