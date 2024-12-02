<?php

namespace App\Controller;

use App\Content\Extractor;
use App\Form\Type\ItemTestType;
use Graby\Monolog\Handler\GrabyHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    /**
     * The only purpose is to be able to quickly see a convert article
     * - check bug
     * - improve parser
     * - chose the best parser
     * - test a site configuration.
     */
    #[Route(path: '/feed/test', name: 'feed_test', methods: ['GET', 'POST'])]
    public function indexAction(Request $request, Extractor $contentExtractor, GrabyHandler $grabyHandler): Response
    {
        $form = $this->createForm(ItemTestType::class);
        $form->handleRequest($request);

        $filePath = '';
        $content = null;
        $previousVersion = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // load custom siteconfig from user
            // add ability to test a siteconfig before submitting it
            $siteConfig = $form->get('siteconfig')->getData();
            if (trim($siteConfig)) {
                $host = parse_url($form->get('link')->getData(), \PHP_URL_HOST);

                if ($host) {
                    // remove www. from host because graby check for domain (without www.) first
                    $host = strtolower($host);
                    if (0 === stripos($host, 'www.')) {
                        $host = substr($host, 4);
                    }

                    $filePath = $this->getParameter('kernel.project_dir') . '/vendor/j0k3r/graby-site-config/' . $host . '.txt';

                    if (file_exists($filePath)) {
                        $previousVersion = file_get_contents($filePath);
                        $siteConfig = $previousVersion . "\n" . $siteConfig;
                    }

                    file_put_contents($filePath, $siteConfig);
                }
            }

            $content = $contentExtractor
                ->enableReloadConfigFiles()
                ->init($form->get('parser')->getData())
                ->parseContent($form->get('link')->getData());

            if ($filePath) {
                if ($previousVersion) {
                    file_put_contents($filePath, $previousVersion);
                } else {
                    unlink($filePath);
                }
            }
        }

        return $this->render('default/Test/index.html.twig', [
            'menu' => 'test',
            'content' => $content,
            'form' => $form->createView(),
            'logs' => $grabyHandler->getRecords(),
            'logsHasWarning' => $grabyHandler->hasRecords(Logger::WARNING),
        ]);
    }
}
