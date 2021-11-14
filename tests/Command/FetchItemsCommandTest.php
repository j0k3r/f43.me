<?php

namespace App\Tests\Command;

use App\Command\FetchItemsCommand;
use App\Content\Import;
use App\Message\FeedSync;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Envelope;

class FetchItemsCommandTest extends WebTestCase
{
    /** @var TestHandler */
    private $handler;
    /** @var \Symfony\Component\Console\Command\Command */
    private $command;
    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        static::createClient();

        $simplePieItem = $this->getMockBuilder('SimplePie_Item')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieItem->expects($this->any())
            ->method('get_description')
            ->willReturn('description');

        $simplePieItem->expects($this->any())
            ->method('get_permalink')
            ->willReturn('https://wildtrip.blog/sri-lanka-3-semaines-quoi-voir.html');

        $simplePie = $this->getMockBuilder('SimplePie')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->any())
            ->method('get_items')
            ->willReturn([$simplePieItem]);

        $simplePie->expects($this->any())
            ->method('get_description')
            ->willReturn('description');

        $simplePieProxy = $this->getMockBuilder('App\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->willReturnSelf();

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->willReturn($simplePie);

        $bus = $this->getMockBuilder('Symfony\Component\Messenger\MessageBusInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch');

        $logger = new Logger('import');
        $this->handler = new TestHandler();
        $logger->pushHandler($this->handler);

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $container->get('app.parser.chain.test')->addParser(
            $container->get('app.parser.internal.test'),
            'internal'
        );

        $container->get('app.parser.chain.test')->addParser(
            $container->get('app.parser.external.test'),
            'external'
        );

        $container->get('app.improver.chain.test')->addImprover(
            $container->get('app.improver.default_improver.test'),
            'default_improver'
        );

        $container->get('app.improver.chain.test')->addImprover(
            $container->get('app.improver.hackernews.test'),
            'hackernews'
        );

        $import = new Import(
            $simplePieProxy,
            $container->get('app.content.extractor.test'),
            $container->get('event_dispatcher.test'),
            $container->get('em.test'),
            $logger,
            $container->get('app.repository.feed.test'),
            $container->get('app.repository.item.test')
        );

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get('app.repository.feed.test'),
            $container->get('app.repository.item.test'),
            $import,
            $container->get('router.test'),
            'f43.me',
            self::$kernel->getContainer()->get('messenger.transport.fetch_items.test'),
            $bus
        ));

        $this->command = $application->find('feed:fetch-items');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testWrongSlug(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'toto',
        ]);

        $this->assertMatchesRegularExpression('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testHN(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'hackernews',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);
        $this->assertStringContainsString('HackerNews', $records[0]['message']);

        $this->assertMatchesRegularExpression('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testNew(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'new',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertIsIterable($records, '$records is not interable');
        $this->assertGreaterThan(0, count($records), "expecting at least one record");
        $this->assertStringContainsString('Working on', $records[0]['message']);

        $this->assertMatchesRegularExpression('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testOld(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'old',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);

        $this->assertMatchesRegularExpression('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testUsingQueue(): void
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $bus = $this->getMockBuilder('Symfony\Component\Messenger\MessageBusInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch')
            ->willReturn(new Envelope(new FeedSync(555)));

        $connection = $this->getMockBuilder('Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('countMessagesInQueues')
            ->willReturn(0);

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get('app.repository.feed.test'),
            $container->get('app.repository.item.test'),
            null,
            $container->get('router.test'),
            'f43.me',
            new AmqpTransport($connection),
            $bus
        ));

        $command = $application->find('feed:fetch-items');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'old',
            '--use_queue' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertMatchesRegularExpression('`feeds queued.`', $commandTester->getDisplay());
    }

    public function testCommandSyncAllUsersWithQueueFull(): void
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $bus = $this->getMockBuilder('Symfony\Component\Messenger\MessageBusInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch');

        $connection = $this->getMockBuilder('Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('countMessagesInQueues')
            ->willReturn(10);

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get('app.repository.feed.test'),
            $container->get('app.repository.item.test'),
            null,
            $container->get('router.test'),
            'f43.me',
            new AmqpTransport($connection),
            $bus
        ));

        $command = $application->find('feed:fetch-items');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'age' => 'old',
            '--use_queue' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertStringContainsString('Current queue as too much messages (10), skipping.', $commandTester->getDisplay());
    }
}
