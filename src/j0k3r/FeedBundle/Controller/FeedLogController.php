<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
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
        $feedlogs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findAllOrderedById();

        return array(
            'feedlogs' => $feedlogs,
        );
    }

    /**
     * Lists all FeedLog documents related to a given feed
     *
     * @Template()
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

        return array(
            'feed'     => $feed,
            'feedlogs' => $feedlogs,
        );
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
