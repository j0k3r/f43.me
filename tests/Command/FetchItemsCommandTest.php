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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class FetchItemsCommandTest extends KernelTestCase
{
    /** @var TestHandler */
    private $handler;
    /** @var FetchItemsCommand */
    private $command;

    protected function setUp(): void
    {
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

        /** @var Container */
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

        $this->command = new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            $import,
            $container->get('router'),
            'f43.me',
            $container->get('messenger.transport.fetch_items'),
            $bus
        );
    }

    public function testWrongSlug(): void
    {
        $output = new BufferedOutput();

        $res = $this->command->__invoke($output, 'old', 'toto', false);

        $this->assertSame($res, 1);
        $this->assertMatchesRegularExpression('`Unable to find Feed document`', $output->fetch());
    }

    public function testHN(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $res = $this->command->__invoke($output, 'old', 'hackernews', false);

        $this->assertSame($res, 0);
        $this->assertMatchesRegularExpression('`items cached.`', $output->fetch());

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);
        $this->assertStringContainsString('HackerNews', $records[0]['message']);
    }

    public function testNew(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $res = $this->command->__invoke($output, 'new', false, false);

        $this->assertSame($res, 0);
        $this->assertMatchesRegularExpression('`items cached.`', $output->fetch());

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);
    }

    public function testOld(): void
    {
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $res = $this->command->__invoke($output, 'old', false, false);

        $this->assertSame($res, 0);
        $this->assertMatchesRegularExpression('`items cached.`', $output->fetch());

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);
    }

    public function testUsingQueue(): void
    {
        /** @var Container */
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

        $command = new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            null,
            $container->get('router'),
            'f43.me',
            new AmqpTransport($connection),
            $bus
        );

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $res = $command->__invoke($output, 'old', false, true);

        $this->assertSame($res, 0);
        $this->assertMatchesRegularExpression('`feeds queued.`', $output->fetch());
    }

    public function testCommandSyncAllUsersWithQueueFull(): void
    {
        /** @var Container */
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

        $command = new FetchItemsCommand(
            $container->get(FeedRepository::class),
            $container->get(ItemRepository::class),
            null,
            $container->get('router'),
            'f43.me',
            new AmqpTransport($connection),
            $bus
        );

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);

        $res = $command->__invoke($output, 'old', false, true);

        $this->assertSame($res, 1);
        $this->assertStringContainsString('Current queue as too much messages (10), skipping.', $output->fetch());
    }
}
