<?php

namespace Api43\FeedBundle\Controller;

use Api43\FeedBundle\Form\Type\ItemTestType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * FeedTest controller.
 */
class FeedTestController extends Controller
{
    /**
     * The only purpose is to be able to quickly see a convert article
     * - check bug
     * - improve parser
     * - chose the best parser
     * - test a site configuration.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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
                    if (stripos($host, 'www.') === 0) {
                        $host = substr($host, 4);
                    }

                    $filePath = $this->getParameter('kernel.root_dir') . '/site_config/' . $host . '.txt';
                    file_put_contents($filePath, $siteConfig);
                }
            }

            $content = $this
                ->get('content_extractor')
                ->init($form->get('parser')->getData())
                ->parseContent($form->get('link')->getData());

            if ($filePath) {
                unlink($filePath);
            }
        }

        return $this->render('Api43FeedBundle:FeedTest:index.html.twig', [
            'menu' => 'test',
            'content' => $content,
            'form' => $form->createView(),
        ]);
    }
}
