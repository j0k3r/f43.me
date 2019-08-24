<?php

namespace Tests\AppBundle\Consumer;

use AppBundle\Consumer\FetchItems;
use AppBundle\Entity\Feed;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Swarrot\Broker\Message;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FetchItemsTest extends WebTestCase
{
    public function testProcessNoFeed()
    {
        static::createClient();

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $contentImport = $this->getMockBuilder('AppBundle\Content\Import')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository = $this->getMockBuilder('AppBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $processor = new FetchItems(
            $doctrine,
            $feedRepository,
            $contentImport,
            $container->get('router.test'),
            new NullLogger(),
            'f43.io'
        );

        $processor->process(new Message(json_encode(['feed_id' => 123])), []);
    }

    public function testProcessSuccessfulMessage()
    {
        static::createClient();

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->willReturn(false); // simulate a closing manager

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $doctrine->expects($this->once())
            ->method('resetEntityManager')
            ->willReturn($em);

        $contentImport = $this->getMockBuilder('AppBundle\Content\Import')
            ->disableOriginalConstructor()
            ->getMock();
        $contentImport->expects($this->once())
            ->method('setEntityManager');
        $contentImport->expects($this->once())
            ->method('process')
            ->willReturn(3);

        $feed = new Feed();
        $feed->setId(123);
        $feed->setSlug('reddit');

        $feedRepository = $this->getMockBuilder('AppBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($feed);

        $logger = new Logger('foo');
        $logHandler = new TestHandler();
        $logger->pushHandler($logHandler);

        $processor = new FetchItems(
            $doctrine,
            $feedRepository,
            $contentImport,
            $container->get('router.test'),
            $logger,
            'f43.io'
        );

        $processor->process(new Message(json_encode(['feed_id' => 123])), []);

        $records = $logHandler->getRecords();

        $this->assertSame('Consume f43.feed_new message', $records[0]['message']);
        $this->assertSame('<comment>3</comment> items cached for <info>reddit</info>', $records[1]['message']);
    }
}
