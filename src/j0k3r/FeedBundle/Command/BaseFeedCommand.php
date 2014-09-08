<?php

namespace j0k3r\FeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseFeedCommand extends ContainerAwareCommand
{
    protected $lockResource = null;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lockCommand($input->getOptions())) {
            return $output->writeLn("<error>Command locked !</error>");
        }
    }

    /**
     * Lock command using given option as "lock file".
     * We also write the date inside the lock file
     *
     * @param array $options
     *
     * @return true/false
     */
    protected function lockCommand($options)
    {
        $keySuffix = md5(serialize($options));

        $fileName = 'task.'.$this->getName().'-'.$keySuffix.'.pid';
        $pidFile  = $this->getContainer()->get('kernel')->getLogDir().'/task/'.$fileName;

        if (!is_readable($pidFile)) {
            if (!is_dir(dirname($pidFile)) && false === @mkdir(dirname($pidFile), 0777)) {
                throw new RuntimeException();
            }

            file_put_contents($pidFile, date('Y-m-d H:i:s'));
        }

        $this->lockResource = fopen($pidFile, 'r+');

        if (false === @flock($this->lockResource, LOCK_EX | LOCK_NB)) {
            return false;
        }

        fwrite($this->lockResource, date('Y-m-d H:i:s')."\n");

        return true;
    }

    /**
     * Unlock command
     *
     */
    protected function unlockCommand()
    {
        if (false === @flock($this->lockResource, LOCK_UN) || false === @fclose($this->lockResource)) {
            throw new RuntimeException(sprintf('Unable to unlock file "%s"', $this->lockResource));
        }
    }
}
