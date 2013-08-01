<?php

namespace j0k3r\FeedBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * FeedLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FeedLogRepository extends DocumentRepository
{
    /**
     * Find all logs ordered by id desc
     *
     * @param integer $limit Items to retrieve
     *
     * @return Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findAllOrderedById($limit = null)
    {
        $q = $this->createQueryBuilder()
            ->eagerCursor(true)
            ->sort('id', 'DESC');

        if (null !== $limit) {
            $q->limit($limit);
        }

        return $q->getQuery()->execute();
    }

    /**
     * Get the base query to fetch items
     *
     * @param string $feedId Feed id
     * @param int    $limit  Number of items to return
     * @param int    $skip   Item to skip before applying the limit
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
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

    /**
     * Find all logs for a given Feed id
     *
     * @param int $feedId Feed id
     *
     * @return Doctrine\ODM\MongoDB\LoggableCursor
     */
    public function findByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId)
            ->execute();
    }

    /**
     * Retrieve the last log for a given Feed id
     *
     * @param int $feedId Feed id
     *
     * @return j0k3r\FeedBundle\Document\FeedLog
     */
    public function findLastItemByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId, 1)
            ->getSingleResult();
    }

    /**
     * Return an array of total items fetched per day:
     *
     *   array (
     *     '8/6/2013' => 43,
     *     '9/6/2013' => 60,
     *     '11/6/2013' => 55,
     *   )
     *
     * @param integer $limit Limit of results to show in the dashboard chart
     *
     * @return Array
     */
    public function findStatsForLastDays($limit = 20)
    {
        // this can be a bit ugly but I can't find an other solution to use aggregate function with Doctrine
        $res = $this->getDocumentManager()
            ->getDocumentCollection('j0k3r\FeedBundle\Document\FeedLog')
            ->getMongoCollection()
            ->aggregate(
                array(
                    '$group' => array(
                        '_id' => array(
                            'years'  => array('$year' => '$created_at'),
                            'months' => array('$month' => '$created_at'),
                            'days'   => array('$dayOfMonth' => '$created_at')
                        ),
                        'number' => array('$sum' => '$items_number'),
                    )
                ), array(
                    '$limit' => $limit,
                )
            );

        if (!isset($res['result'])) {
            return array();
        }

        $results = array();
        foreach ($res['result'] as $day) {
            $results[$day['_id']['days'].'/'.$day['_id']['months'].'/'.$day['_id']['years']] = $day['number'];
        }

        return array_reverse($results, true);
    }

    /**
     * Remove all logs associated to the given Feed id
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
     * Retrieve a list of all feed with the last feedlog date
     *
     * @return array
     */
    public function findLastUpdated()
    {
        $res = $this->getDocumentManager()
            ->getDocumentCollection('j0k3r\FeedBundle\Document\FeedLog')
            ->getMongoCollection()
            ->group(
                array('feed' => true),
                array('count' => 0),
                'function (obj, prev) {
                    prev.max_created_at = isNaN(prev.max_created_at) ? Math.max(obj.created_at) : Math.max(prev.max_created_at, obj.created_at);
                }'
            );

        if (!isset($res['retval'])) {
            return array();
        }

        $results = array();
        foreach ($res['retval'] as $oneRes) {
            $results[$oneRes['max_created_at']] = array(
                // we get milliseconds, so we convert it to seconds
                'created_at' => new \DateTime('@'.$oneRes['max_created_at']/1000),
                'feed_id'    => (string) $oneRes['feed']['$id']
            );
        }

        // sort by most recent first (and we don't care to keep key)
        rsort($results);

        return $results;
    }
}
