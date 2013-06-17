<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @Template()
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new ItemTestType());
        $content = null;

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $parser = $this
                    ->get('readability_proxy')
                    ->setChoosenParser($form->get('parser')->getData());

                $content = $parser->parseContent($form->get('link')->getData());
            }
        }

        return array(
            'menu'    => 'test',
            'content' => $content,
            'form'    => $form->createView(),
        );
    }
}
