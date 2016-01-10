<?php

namespace Api43\FeedBundle\DataFixtures\MongoDB;

use Api43\FeedBundle\Document\FeedLog;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadFeedLogData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $feedLogReddit = new FeedLog();
        $feedLogReddit->setItemsNumber(25);
        $feedLogReddit->setFeed($this->getReference('feed-reddit'));
        $manager->persist($feedLogReddit);
        $this->addReference('feedlog-reddit', $feedLogReddit);

        $feedLog1HackerNews = new FeedLog();
        $feedLog1HackerNews->setItemsNumber(30);
        $feedLog1HackerNews->setFeed($this->getReference('feed-hackernews'));
        $manager->persist($feedLog1HackerNews);
        $this->addReference('feedlog1-hackernews', $feedLog1HackerNews);

        $feedLogBonjourMadame = new FeedLog();
        $feedLogBonjourMadame->setItemsNumber(20);
        $feedLogBonjourMadame->setFeed($this->getReference('feed-bonjourmadame'));
        $manager->persist($feedLogBonjourMadame);
        $this->addReference('feedlog-bonjourmadame', $feedLogBonjourMadame);

        $feedLog2HackerNews = new FeedLog();
        $feedLog2HackerNews->setItemsNumber(3);
        $feedLog2HackerNews->setFeed($this->getReference('feed-hackernews'));
        $manager->persist($feedLog2HackerNews);
        $this->addReference('feedlog2-hackernews', $feedLog2HackerNews);

        $manager->flush();
    }

    public function getOrder()
    {
        return 30;
    }
}
