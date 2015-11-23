<?php

namespace Api43\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Api43\FeedBundle\Form\Type\ItemTestType;

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
     * @return array
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new ItemTestType());
        $form->handleRequest($request);

        $filePath = null;
        $content = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // load custom siteconfig from user
            // add ability to test a siteconfig before submitting it
            $siteConfig = $form->get('siteconfig')->getData();
            if (trim($siteConfig)) {
                $url = parse_url($form->get('link')->getData(), PHP_URL_HOST);

                if ($url) {
                    $filePath = $this->getParameter('kernel.root_dir').'/site_config/'.$url.'.txt';
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

        return $this->render('Api43FeedBundle:FeedTest:index.html.twig', array(
            'menu' => 'test',
            'content' => $content,
            'form' => $form->createView(),
        ));
    }
}
