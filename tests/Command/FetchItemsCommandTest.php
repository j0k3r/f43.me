<?php

namespace App\Tests\Command;

use App\Command\FetchItemsCommand;
use App\Content\Extractor;
use App\Content\Import;
use App\Improver\ImproverChain;
use App\Message\FeedSync;
use App\Parser\ParserChain;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use App\Xml\SimplePieProxy;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class FetchItemsCommandTest extends WebTestCase
{
    /** @var TestHandler */
    private $handler;
    /** @var Command */
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

        $simplePieProxy = $this->getMockBuilder(SimplePieProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->willReturnSelf();

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->willReturn($simplePie);

        $bus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch');

        $logger = new Logger('import');
        $this->handler = new TestHandler();
        $logger->pushHandler($this->handler);

        /** @var ContainerInterface */
        $container = self::getContainer();

        $container->get(ParserChain::class)->addParser(
            $container->get('feed.parser.internal'),
            'internal'
        );

        $container->get(ParserChain::class)->addParser(
            $container->get('feed.parser.external'),
            'external'
        );

        $container->get(ImproverChain::class)->addImprover(
            $container->get('feed.improver.default_improver'),
            'default_improver'
        );

        $container->get(ImproverChain::class)->addImprover(
            $container->get('feed.improver.hackernews'),
            'hackernews'
        );

        $import = new Import(
            $simplePieProxy,
            $container->get(Extractor::class),
            $container->get('event_dispatcher'),
            $container->get(EntityManagerInterface::class),
            $logger,
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class)
        );

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            $import,
            $container->get('router'),
            'f43.me',
            $container->get('messenger.transport.fetch_items'),
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

        $this->assertGreaterThan(0, $records);
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
        /** @var ContainerInterface */
        $container = self::getContainer();

        $bus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch')
            ->willReturn(new Envelope(new FeedSync(555)));

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('countMessagesInQueues')
            ->willReturn(0);

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            null,
            $container->get('router'),
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
        /** @var ContainerInterface */
        $container = self::getContainer();

        $bus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch');

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('countMessagesInQueues')
            ->willReturn(10);

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            null,
            $container->get('router'),
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
