<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * FeedItem controller.
 */
class FeedItemController extends Controller
{
    /**
     * Lists all Items documents related to a Feed
     *
     * @Template()
     * @param string $slug Feed slug
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

        $feeditems = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findByFeed(
            $feed->getId(),
            $feed->getSortBy()
        );

        $deleteAllForm = $this->createDeleteAllForm();

        return array(
            'menu'            => 'feed',
            'feed'            => $feed,
            'feeditems'       => $feeditems,
            'delete_all_form' => $deleteAllForm->createView(),
        );
    }

    /**
     * Delete all items for a given Feed
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
            $res = $dm->getRepository('j0k3rFeedBundle:FeedItem')->deleteAllByFeedId($feed->getId());

            $feed->setNbItems(0);
            $dm->persist($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', $res['n'].' documents deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', array('slug' => $slug)));
    }

    /**
     * Preview an item that is already cached
     *
     * @param string $id Item id
     *
     * @return string
     */
    public function previewCachedAction($id)
    {
        $dm       = $this->getDocumentManager();
        $feeditem = $dm->getRepository('j0k3rFeedBundle:FeedItem')->find($id);

        if (!$feeditem) {
            throw $this->createNotFoundException('Unable to find FeedItem document.');
        }

        return $this->container->get('templating')->renderResponse('j0k3rFeedBundle:FeedItem:content.html.twig', array(
            'title'   => $feeditem->getTitle(),
            'content' => $feeditem->getContent(),
            'url'     => $feeditem->getLink(),
            'modal'   => true,
        ));
    }

    /**
     * Display a modal to preview the first item from a Feed.
     * It will allow to preview the parsed item (which isn't cached) using the internal or the external parser
     *
     * @param string $slug The Feed slug
     *
     * @return string
     */
    public function testItemAction($slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        return $this->container->get('templating')->renderResponse('j0k3rFeedBundle:FeedItem:preview.html.twig', array(
            'feed' => $feed
        ));
    }

    /**
     * Following the previous action, this one will actually parse the content (for both parser)
     *
     * @param Request $request
     * @param string  $slug    The Feed slug
     *
     * @return string
     */
    public function previewNewAction(Request $request, $slug)
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
            ->init($request->get('parser'), $feed);

        $firstItem = $rssFeed->get_item(0);
        if (!$firstItem) {
            throw $this->createNotFoundException('No item found in this feed.');
        }

        $content = $parser->parseContent(
            $firstItem->get_permalink(),
            $firstItem->get_description()
        );

        return $this->container->get('templating')->renderResponse('j0k3rFeedBundle:FeedItem:content.html.twig', array(
            'title'   => html_entity_decode($firstItem->get_title(), ENT_COMPAT, 'UTF-8'),
            'content' => $content->content,
            'modal'   => false,
            'url'     => $content->url,
            'defaultContent' => $content->useDefault,
        ));
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
