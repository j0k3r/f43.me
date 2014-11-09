<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use j0k3r\FeedBundle\Form\ItemTestType;

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
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new ItemTestType());
        $form->handleRequest($request);

        $content = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $parser = $this
                ->get('readability_proxy')
                ->init($form->get('parser')->getData());

            $content = $parser->parseContent($form->get('link')->getData());
        }

        return $this->render('j0k3rFeedBundle:FeedTest:index.html.twig', array(
            'menu'    => 'test',
            'content' => $content,
            'form'    => $form->createView(),
        ));
    }
}
