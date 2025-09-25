<?php

namespace App\Tests\MessageHandler;

use App\Content\Import;
use App\Entity\Feed;
use App\Message\FeedSync;
use App\MessageHandler\FetchItemsHandler;
use App\Repository\FeedRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FetchItemsHandlerTest extends WebTestCase
{
    public function testProcessNoFeed(): void
    {
        static::createClient();

        /** @var ContainerInterface */
        $container = self::getContainer();

        $doctrine = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contentImport = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository = $this->getMockBuilder(FeedRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $handler = new FetchItemsHandler(
            $doctrine,
            $feedRepository,
            $contentImport,
            $container->get('router'),
            new NullLogger(),
            'f43.io'
        );

        $handler->__invoke(new FeedSync(123));
    }

    public function testProcessSuccessfulMessage(): void
    {
        static::createClient();

        /** @var ContainerInterface */
        $container = self::getContainer();

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('isOpen')
            ->willReturn(false); // simulate a closing manager

        $doctrine = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $doctrine->expects($this->once())
            ->method('resetManager')
            ->willReturn($em);

        $contentImport = $this->getMockBuilder(Import::class)
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

        $feedRepository = $this->getMockBuilder(FeedRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($feed);

        $logger = new Logger('foo');
        $logHandler = new TestHandler();
        $logger->pushHandler($logHandler);

        $handler = new FetchItemsHandler(
            $doctrine,
            $feedRepository,
            $contentImport,
            $container->get('router'),
            $logger,
            'f43.io'
        );

        $handler->__invoke(new FeedSync(123));

        $records = $logHandler->getRecords();

        $this->assertSame('Consume f43.feed_new message', $records[0]['message']);
        $this->assertSame('<comment>3</comment> items cached for <info>reddit</info>', $records[1]['message']);
    }
}
