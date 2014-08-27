<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * FeedLog controller.
 */
class FeedLogController extends Controller
{
    /**
     * Lists all FeedLog documents.
     *
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $dm       = $this->getDocumentManager();
        $feedlogs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findAllOrderedById(100);

        return array(
            'menu'     => 'log',
            'feedlogs' => $feedlogs,
        );
    }

    /**
     * Lists all FeedLog documents related to a given feed
     *
     * @Template()
     * @param string $slug The Feed slug
     *
     * @return array
     */
    public function feedAction($slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $feedlogs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findByFeedId($feed->getId());

        $deleteAllForm = $this->createDeleteAllForm();

        return array(
            'menu'            => 'log',
            'feed'            => $feed,
            'feedlogs'        => $feedlogs,
            'delete_all_form' => $deleteAllForm->createView(),
        );
    }

    /**
     * Delete all logs for a given Feed
     *
     * @param Request $request
     * @param string  $slug    The Feed slug
     *
     * @return RedirectResponse
     */
    public function deleteAllAction(Request $request, $slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $form = $this->createDeleteAllForm();
        $form->submit($request);

        if ($form->isValid()) {
            $res = $dm->getRepository('j0k3rFeedBundle:FeedLog')->deleteAllByFeedId($feed->getId());

            $this->get('session')->getFlashBag()->add('notice', $res['n'].' documents deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', array('slug' => $slug)));
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
