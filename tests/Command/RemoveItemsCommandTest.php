<?php

namespace App\Tests\Command;

use App\Command\RemoveItemsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveItemsCommandTest extends WebTestCase
{
    /** @var \Symfony\Component\Console\Command\Command */
    private $command;
    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        static::createClient();

        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = self::getContainer();

        $application = new Application(static::$kernel);
        $application->add(new RemoveItemsCommand(
            $container->get(\App\Repository\FeedRepository::class),
            $container->get(\App\Repository\ItemRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class)
        ));

        $this->command = $application->find('feed:remove-items');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testRemoveAllAvailable(): void
    {
        $this->commandTester->execute(['command' => $this->command->getName()]);

        $this->assertRegExp('`0 items removed.`', $this->commandTester->getDisplay());
    }

    public function testRemoveForOneFeed(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'hackernews',
        ]);

        $this->assertRegExp('`0 items removed.`', $this->commandTester->getDisplay());
    }

    public function testRemoveBadSlug(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
        ]);

        $this->assertRegExp('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testRemoveAllMaxYes(): void
    {
        $this->commandTester->setInputs(['yes\\n']);
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
            '--max' => 0,
        ]);

        $this->assertRegExp('`You will remove ALL items, are your sure?`', $this->commandTester->getDisplay());
    }

    public function testRemoveAllMaxNo(): void
    {
        $this->commandTester->setInputs(['no\\n']);
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
            '--max' => 0,
        ]);

        $this->assertRegExp('`remove everything from your database, pfiou`', $this->commandTester->getDisplay());
    }

    public function testRemove(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'hackernews',
            '--max' => 2,
        ]);

        $this->assertRegExp('`items removed.`', $this->commandTester->getDisplay());
    }
}
