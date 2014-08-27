<?php

namespace j0k3r\FeedBundle\DataFixtures\MongoDB;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use j0k3r\FeedBundle\Document\Feed;

class LoadFeedData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $feedReddit = new Feed();
        $feedReddit->setName('Reddit');
        $feedReddit->setDescription('Reddit');
        $feedReddit->setLink('http://reddit.com/.rss');
        $feedReddit->setParser('internal');
        $feedReddit->setFormatter('rss');
        $feedReddit->setHost('http://reddit.com');
        $feedReddit->setIsPrivate(false);
        $feedReddit->setSortBy('created_at');
        $feedReddit->setNbItems(3);
        $feedReddit->setLastItemCachedAt(new \DateTime());
        $manager->persist($feedReddit);
        $this->addReference('feed-reddit', $feedReddit);

        $feedHN = new Feed();
        $feedHN->setName('HackerNews');
        $feedHN->setDescription('');
        $feedHN->setLink('https://news.ycombinator.com/rss');
        $feedHN->setParser('internal');
        $feedHN->setFormatter('atom');
        $feedHN->setHost('news.ycombinator.com');
        $feedHN->setIsPrivate(false);
        $feedHN->setSortBy('published_at');
        $feedHN->setNbItems(3);
        $feedHN->setLastItemCachedAt(new \DateTime());
        $manager->persist($feedHN);
        $this->addReference('feed-hackernews', $feedHN);

        $feedMadame = new Feed();
        $feedMadame->setName('Bonjour Madame');
        $feedMadame->setDescription('TOUS LES MATINS 10h, une nouvelle photo, une nouvelle fracture de l\'oeil ');
        $feedMadame->setLink('http://feeds2.feedburner.com/BonjourMadame');
        $feedMadame->setParser('external');
        $feedMadame->setFormatter('rss');
        $feedMadame->setHost('bonjourmadame.fr');
        $feedMadame->setIsPrivate(true);
        $feedMadame->setSortBy('published_at');
        $feedMadame->setNbItems(2);
        $feedMadame->setLastItemCachedAt(new \DateTime());
        $manager->persist($feedMadame);
        $this->addReference('feed-bonjourmadame', $feedMadame);

        $manager->flush();
    }

    public function getOrder()
    {
        return 10;
    }
}
