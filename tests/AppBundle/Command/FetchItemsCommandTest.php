<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\FetchItemsCommand;
use AppBundle\Content\Import;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group legacy
 *
 * Because there is no right way to override existing service in the upcoming Symfony 4.0
 * without modifiy them globally (using config_test.yml)
 */
class FetchItemsCommandTest extends WebTestCase
{
    private $handler;
    private $command;
    private $commandTester;

    public function setUp(): void
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

        $simplePieProxy = $this->getMockBuilder('AppBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->willReturnSelf();

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->willReturn($simplePie);

        $publisher = $this->getMockBuilder('Swarrot\SwarrotBundle\Broker\Publisher')
            ->disableOriginalConstructor()
            ->getMock();

        $publisher->expects($this->any())
            ->method('publish');

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
            $publisher,
            'f43.me',
            $this->getAmqpMessage(0)
        ));

        $this->command = $application->find('feed:fetch-items');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testWrongSlug()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'toto',
        ]);

        $this->assertRegExp('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testHN()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'hackernews',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);
        $this->assertStringContainsString('HackerNews', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testNew()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'new',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testOld()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'old',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertStringContainsString('Working on', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testUsingQueue()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'age' => 'old',
            '--use_queue' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertRegExp('`feeds queued.`', $this->commandTester->getDisplay());
    }

    public function testCommandSyncAllUsersWithQueueFull()
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::$kernel->getContainer();

        $publisher = $this->getMockBuilder('Swarrot\SwarrotBundle\Broker\Publisher')
            ->disableOriginalConstructor()
            ->getMock();

        $publisher->expects($this->any())
            ->method('publish');

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            $container->get('app.repository.feed.test'),
            $container->get('app.repository.item.test'),
            null,
            $container->get('router.test'),
            $publisher,
            'f43.me',
            $this->getAmqpMessage(10)
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

    /**
     * @see http://symfony.com/doc/current/components/console/helpers/dialoghelper.html#testing-a-command-which-expects-input
     *
     * @param string $input
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);

        if (false === $stream) {
            throw new \Exception('Cannot create stream ...');
        }

        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }

    private function getAmqpMessage($totalMessage = 0)
    {
        $message = new AMQPMessage();
        $message->delivery_info = [
            'message_count' => $totalMessage,
        ];

        $amqpChannel = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel->expects($this->any())
            ->method('basic_get')
            ->with('f43.fetch_items')
            ->willReturn($message);

        $amqpLibFactory = $this->getMockBuilder('Swarrot\SwarrotBundle\Broker\AmqpLibFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpLibFactory->expects($this->any())
            ->method('getChannel')
            ->with('rabbitmq')
            ->willReturn($amqpChannel);

        return $amqpLibFactory;
    }
}
