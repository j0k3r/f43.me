<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Log;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadLogData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $logReddit = new Log($this->getReference('feed-reddit'));
        $logReddit->setItemsNumber(25);
        $manager->persist($logReddit);
        $this->addReference('log-reddit', $logReddit);

        $log1HackerNews = new Log($this->getReference('feed-hackernews'));
        $log1HackerNews->setItemsNumber(30);
        $manager->persist($log1HackerNews);
        $this->addReference('log1-hackernews', $log1HackerNews);

        $logBonjourMadame = new Log($this->getReference('feed-bonjourmadame'));
        $logBonjourMadame->setItemsNumber(20);
        $manager->persist($logBonjourMadame);
        $this->addReference('log-bonjourmadame', $logBonjourMadame);

        $log2HackerNews = new Log($this->getReference('feed-hackernews'));
        $log2HackerNews->setItemsNumber(3);
        $manager->persist($log2HackerNews);
        $this->addReference('log2-hackernews', $log2HackerNews);

        $manager->flush();
    }

    public function getOrder()
    {
        return 30;
    }
}
