<?php

namespace AppBundle\Repository;

use AppBundle\Document\FeedLog;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

class FeedLogRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedLog::class);
    }

    /**
     * Find all logs ordered by id desc.
     *
     * @param int|null $limit Items to retrieve
     *
     * @return \Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findAllOrderedById($limit = null)
    {
        $q = $this->createQueryBuilder()
            ->sort('id', 'DESC');

        if (null !== $limit) {
            $q->limit($limit);
        }

        return $q->getQuery()->execute();
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
     * @return FeedLog|null
     */
    public function findLastItemByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId, 1)
            ->getSingleResult();
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
        // this can be a bit ugly but I can't find an other solution to use aggregate function with Doctrine
        $res = $this->getDocumentManager()
            ->getDocumentCollection('AppBundle\Document\FeedLog')
            ->getMongoCollection()
            ->aggregate(
                [
                    '$group' => [
                        '_id' => [
                            'years' => ['$year' => '$created_at'],
                            'months' => ['$month' => '$created_at'],
                            'days' => ['$dayOfMonth' => '$created_at'],
                        ],
                        'number' => ['$sum' => '$items_number'],
                    ],
                ], [
                    '$sort' => ['_id.years' => -1, '_id.months' => -1, '_id.days' => -1],
                ], [
                    '$limit' => $limit,
                ]
            );

        if (!isset($res['result'])) {
            return [];
        }

        $results = [];
        foreach ($res['result'] as $day) {
            $results[$day['_id']['days'] . '/' . $day['_id']['months'] . '/' . $day['_id']['years']] = $day['number'];
        }

        return array_reverse($results, true);
    }

    /**
     * Remove all logs associated to the given Feed id.
     *
     * @param int $feedId Feed id
     *
     * @return array (with key 'n' as number of row affected)
     */
    public function deleteAllByFeedId($feedId)
    {
        return $this->createQueryBuilder()
            ->remove()
            ->field('feed.id')->equals($feedId)
            ->getQuery()
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
        return $this->createQueryBuilder()
            ->count()
            ->field('feed.id')->equals($feedId)
            ->getQuery()
            ->execute();
    }

    /**
     * Retrieve a list of all feed with the last feedlog date.
     *
     * This one isn't used anymore. It was too long to retrieve data. I switched to something else.
     * But I spent too much hour on it, so I can't remove it like that..
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function findLastUpdated()
    {
        $res = $this->getDocumentManager()
            ->getDocumentCollection('AppBundle\Document\FeedLog')
            ->getMongoCollection()
            ->group(
                ['feed' => true],
                ['count' => 0],
                'function (obj, prev) {
                    prev.max_created_at = isNaN(prev.max_created_at) ? Math.max(obj.created_at) : Math.max(prev.max_created_at, obj.created_at);
                }'
            );

        if (!isset($res['retval'])) {
            return [];
        }

        $results = [];
        foreach ($res['retval'] as $oneRes) {
            $results[$oneRes['max_created_at']] = [
                // we get milliseconds, so we convert it to seconds
                'created_at' => new \DateTime('@' . $oneRes['max_created_at'] / 1000),
                'feed_id' => (string) $oneRes['feed']['$id'],
            ];
        }

        // sort by most recent first (and we don't care to keep key)
        rsort($results);

        return $results;
    }

    /**
     * Get the base query to fetch items.
     *
     * @param string   $feedId Feed id
     * @param int|null $limit  Number of items to return
     * @param int|null $skip   Item to skip before applying the limit
     *
     * @return \Doctrine\ODM\MongoDB\Query\Query
     */
    private function getItemsByFeedIdQuery($feedId, $limit = null, $skip = null)
    {
        $q = $this->createQueryBuilder()
            ->field('feed.id')->equals($feedId)
            ->sort('id', 'DESC');

        if (null !== $limit) {
            $q->limit(0);
        }

        if (null !== $skip) {
            $q->skip(0);
        }

        return $q->getQuery();
    }
}
