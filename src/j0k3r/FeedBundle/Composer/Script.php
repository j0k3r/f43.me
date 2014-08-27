<?php

namespace j0k3r\FeedBundle\Composer;

use Composer\Script\Event;

/**
 * @codeCoverageIgnore
 */
class Script
{
    private static $rootDir;
    private static $io;

    public static function postInstallFoundation(Event $event)
    {
        $config        = $event->getComposer()->getConfig();
        $vendorDir     = strtr(realpath($config->get('vendor-dir')), '\\', '/');
        self::$rootDir = getcwd();
        self::$io      = $event->getIO();
        $foundationDir = $vendorDir.'/../src/j0k3r/FeedBundle/Resources/foundation/';

        // move scss files
        $sourceDir = $vendorDir.'/zurb/foundation/scss';
        $targetDir = $foundationDir.'scss';

        self::createSymlink($targetDir, $sourceDir);

        // move js files
        $sourceDir = $vendorDir.'/zurb/foundation/js';
        $targetDir = $foundationDir.'js';

        self::createSymlink($targetDir, $sourceDir);
    }

    /**
     * Display full path
     *
     * @param string $directory
     *
     * @return string
     */
    private static function displayPath($directory)
    {
        return str_replace(self::$rootDir.'/', '', $directory);
    }

    /**
     * Call `system()` function to create symlink inside the project
     *
     * @param string  $target The target dir
     * @param string  $source The source dir
     * @param boolean $force
     */
    private static function createSymlink($target, $source, $force = false)
    {
        if (false === file_exists($target) || true === $force) {
            self::$io->write('Make symlink <info>'.self::displayPath($target).'</info> from <info>'.self::displayPath($source).'</info>');

            system(sprintf('ln -s %s %s', $source, $target));
        }
    }
}
