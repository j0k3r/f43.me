<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use j0k3r\FeedBundle\Document\Feed;

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
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $feeditems = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findByFeedId($feed->getId());

        return array(
            'feed'      => $feed,
            'feeditems' => $feeditems,
        );
    }

    public function previewAction($id)
    {
        $dm       = $this->getDocumentManager();
        $feeditem = $dm->getRepository('j0k3rFeedBundle:FeedItem')->find($id);

        if (!$feeditem) {
            throw $this->createNotFoundException('Unable to find FeedItem document.');
        }

        return $this->container->get('templating')->renderResponse('j0k3rFeedBundle:FeedItem:content.html.twig', array(
            'content' => $feeditem->getContent(),
            'url'     => $feeditem->getLink(),
        ));
    }

    public function testItemAction($slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $rssFeed = $this
            ->get('simple_pie_proxy')
            ->setUrl($feed->getLink())
            ->init();

        $parser = $this
            ->get('readability_proxy')
            ->setChoosenParser($feed->getParser());

        $content = $parser->parseContent($rssFeed->get_item(0)->get_link());

        return $this->container->get('templating')->renderResponse('j0k3rFeedBundle:FeedItem:content.html.twig', array(
            'content' => $content->content,
            'url'     => $content->url,
        ));
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
