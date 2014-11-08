<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use j0k3r\FeedBundle\Document\Feed;

/**
 * FeedLog controller.
 */
class FeedLogController extends Controller
{
    /**
     * Lists all FeedLog documents.
     *
     * @return array
     */
    public function indexAction()
    {
        $dm       = $this->getDocumentManager();
        $feedlogs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findAllOrderedById(100);

        return $this->render('j0k3rFeedBundle:FeedLog:index.html.twig', array(
            'menu'     => 'log',
            'feedlogs' => $feedlogs,
        ));
    }

    /**
     * Lists all FeedLog documents related to a given feed
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return array
     */
    public function feedAction(Feed $feed)
    {
        $feedlogs = $this->getDocumentManager()
            ->getRepository('j0k3rFeedBundle:FeedLog')
            ->findByFeedId($feed->getId());

        $deleteAllForm = $this->createDeleteAllForm();

        return $this->render('j0k3rFeedBundle:FeedLog:feed.html.twig', array(
            'menu'            => 'log',
            'feed'            => $feed,
            'feedlogs'        => $feedlogs,
            'delete_all_form' => $deleteAllForm->createView(),
        ));
    }

    /**
     * Delete all logs for a given Feed
     *
     * @param Request $request
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return RedirectResponse
     */
    public function deleteAllAction(Request $request, Feed $feed)
    {
        $form = $this->createDeleteAllForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $res = $this->getDocumentManager()
                ->getRepository('j0k3rFeedBundle:FeedLog')
                ->deleteAllByFeedId($feed->getId());

            $this->get('session')->getFlashBag()->add('notice', $res['n'].' documents deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', array('slug' => $feed->getSlug())));
    }

    private function createDeleteAllForm()
    {
        return $this->createFormBuilder()->getForm();
    }

    /**
     * Returns the DocumentManager
     *
     * @return DocumentManager
     */
    private function getDocumentManager()
    {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }
}
