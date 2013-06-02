<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use j0k3r\FeedBundle\Document\Feed;
// use j0k3r\FeedBundle\Document\FeedItem;

/**
 * FeedItem controller.
 */
class FeedItemController extends Controller
{
    /**
     * Lists all Feed documents.
     *
     * @Template()
     *
     * @return array
     */
    public function indexAction($slug)
    {
        $dm        = $this->getDocumentManager();
        $feed      = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $feeditems = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findByFeed($slug);

        return array(
            'feeditems' => $feeditems,
            'feed'      => $feed,
        );
    }

    /**
     * Deletes a Feed document.
     *
     * @param Request $request The request object
     * @param string $slug     The document Slug
     *
     * @return array
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $slug)
    {
        $form = $this->createDeleteForm($slug);
        $form->bind($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $document = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findOneBySlug($slug);

            if (!$document) {
                throw $this->createNotFoundException('Unable to find FeedItem document.');
            }

            $dm->remove($document);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document deleted!');
        }

        return $this->redirect($this->generateUrl('j0k3r_feed_homepage'));
    }

    private function createDeleteForm($slug)
    {
        return $this->createFormBuilder(array('slug' => $slug))
            ->add('slug', 'hidden')
            ->getForm()
        ;
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
