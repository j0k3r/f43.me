<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\FetchItemsCommand;
use AppBundle\Content\Import;
use Faker\Factory;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
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

    public function setUp()
    {
        static::createClient();

        $faker = Factory::create();

        $simplePieItem = $this->getMockBuilder('SimplePie_Item')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieItem->expects($this->any())
            ->method('get_description')
            ->willReturn($faker->text);

        $simplePieItem->expects($this->any())
            ->method('get_permalink')
            ->will($this->onConsecutiveCalls(
                // ensure a new url is generated each time the method is called (to avoid duplicate in db)
                $faker->url,
                $faker->url,
                $faker->url,
                $faker->url
            ));

        $simplePie = $this->getMockBuilder('SimplePie')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->any())
            ->method('get_items')
            ->willReturn([$simplePieItem]);

        $simplePie->expects($this->any())
            ->method('get_description')
            ->willReturn($faker->text);

        $simplePieProxy = $this->getMockBuilder('AppBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->willReturnSelf();

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->willReturn($simplePie);

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
            // $container->get('app.content.import.test'),
            $import,
            $container->get('router.test'),
            'f43.me'
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
        $this->assertContains('Working on', $records[0]['message']);
        $this->assertContains('HackerNews', $records[0]['message']);

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
        $this->assertContains('Working on', $records[0]['message']);

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
        $this->assertContains('Working on', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
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
}
