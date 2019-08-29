<?php

namespace AppBundle\Controller;

use AppBundle\Content\Extractor;
use AppBundle\Form\Type\ItemTestType;
use Graby\Monolog\Handler\GrabyHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends Controller
{
    /**
     * The only purpose is to be able to quickly see a convert article
     * - check bug
     * - improve parser
     * - chose the best parser
     * - test a site configuration.
     *
     * @Route("/feed/test", name="feed_test", methods={"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, Extractor $contentExtractor, GrabyHandler $grabyHandler)
    {
        $form = $this->createForm(ItemTestType::class);
        $form->handleRequest($request);

        $filePath = '';
        $content = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // load custom siteconfig from user
            // add ability to test a siteconfig before submitting it
            $siteConfig = $form->get('siteconfig')->getData();
            if (trim($siteConfig)) {
                $host = parse_url($form->get('link')->getData(), PHP_URL_HOST);

                if ($host) {
                    // remove www. from host because graby check for domain (without www.) first
                    $host = strtolower($host);
                    if (0 === stripos($host, 'www.')) {
                        $host = substr($host, 4);
                    }

                    $filePath = $this->getParameter('kernel.root_dir') . '/site_config/' . $host . '.txt';
                    file_put_contents($filePath, $siteConfig);
                }
            }

            $content = $contentExtractor
                ->enableReloadConfigFiles()
                ->init($form->get('parser')->getData())
                ->parseContent($form->get('link')->getData());

            if ($filePath) {
                unlink($filePath);
            }
        }

        return $this->render('AppBundle:Test:index.html.twig', [
            'menu' => 'test',
            'content' => $content,
            'form' => $form->createView(),
            'logs' => $grabyHandler->getRecords(),
            'logsHasWarning' => $grabyHandler->hasRecords(Logger::WARNING),
        ]);
    }
}
