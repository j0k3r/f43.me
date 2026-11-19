<?php

namespace App\Content;

use Graby\Graby;
use Graby\GrabyConfig;
use Graby\SiteConfig\ConfigBuilder;
use Graby\SiteConfig\ConfigBuilderConfig;
use GrabySiteConfig\SiteConfig\Files;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SiteConfigTest
{
    public function __construct(private GrabyConfig $config, private Filesystem $filesystem, private LoggerInterface $logger, private string $temporaryDirectory)
    {
    }

    /**
     * @template T
     *
     * @param callable(Graby): T $test
     *
     * @return T
     */
    public function run(string $url, string $siteConfig, bool $replace, callable $test): mixed
    {
        $host = strtolower(parse_url($url, \PHP_URL_HOST) ?: '');
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $builderConfig = $this->config->extractor->configBuilder;
        if (!$host || \strlen($host) > 200 || !preg_match($builderConfig->hostnameRegex, $host)) {
            throw new \InvalidArgumentException('The URL must contain a valid siteconfig hostname.');
        }

        if (!$replace) {
            $files = Files::getFiles($builderConfig->siteConfig);
            $existingFile = $files[$host . '.txt'] ?? null;
            // Match Graby's fallback from a subdomain to .example.com.txt.
            $dot = strpos($host, '.');
            if (null === $existingFile && false !== $dot) {
                $existingFile = $files[substr($host, $dot) . '.txt'] ?? null;
            }
            if (null !== $existingFile) {
                $siteConfig = $this->filesystem->readFile($existingFile) . "\n" . $siteConfig;
            }
        }

        $directory = $this->temporaryDirectory . '/' . bin2hex(random_bytes(16));

        try {
            $this->filesystem->mkdir($directory, 0700);
            $this->filesystem->dumpFile($directory . '/' . $host . '.txt', $siteConfig);

            // A fresh builder also isolates parsed configs, which reloadConfigFiles() does not clear.
            $builder = new ConfigBuilder(new ConfigBuilderConfig(
                siteConfig: [...$builderConfig->siteConfig, $directory],
                hostnameRegex: $builderConfig->hostnameRegex,
            ), $this->logger);
            $graby = new Graby($this->config, configBuilder: $builder);
            $graby->setLogger($this->logger);

            $this->logger->info('Testing custom site config for {host} with the internal parser; specialized extractors are bypassed. A redirect to another host may select another config.', ['host' => $host]);

            return $test($graby);
        } finally {
            $this->filesystem->remove($directory);
        }
    }
}
