<?php

namespace Api43\FeedBundle\Controller;

use Api43\FeedBundle\Document\Feed;
use Api43\FeedBundle\Document\FeedItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * FeedItem controller.
 */
class FeedItemController extends Controller
{
    /**
     * Lists all Items documents related to a Feed.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return array
     */
    public function indexAction(Feed $feed)
    {
        $feeditems = $this->getDocumentManager()->getRepository('Api43FeedBundle:FeedItem')->findByFeed(
            $feed->getId(),
            $feed->getSortBy()
        );

        $deleteAllForm = $this->createDeleteAllForm();

        return $this->render('Api43FeedBundle:FeedItem:index.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'feeditems' => $feeditems,
            'delete_all_form' => $deleteAllForm->createView(),
        ]);
    }

    /**
     * Delete all items for a given Feed.
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

        if ($form->isSubmitted() && $form->isValid()) {
            $dm = $this->getDocumentManager();
            $res = $dm->getRepository('Api43FeedBundle:FeedItem')->deleteAllByFeedId($feed->getId());

            $feed->setNbItems(0);
            $dm->persist($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', $res['n'] . ' documents deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
    }

    /**
     * Preview an item that is already cached.
     *
     * @param FeedItem $feedItem The document FeedItem (retrieving for a ParamConverter with the id)
     *
     * @return string
     */
    public function previewCachedAction(FeedItem $feedItem)
    {
        return $this->render('Api43FeedBundle:FeedItem:content.html.twig', [
            'title' => $feedItem->getTitle(),
            'content' => $feedItem->getContent(),
            'url' => $feedItem->getLink(),
            'modal' => true,
        ]);
    }

    /**
     * Display a modal to preview the first item from a Feed.
     * It will allow to preview the parsed item (which isn't cached) using the internal or the external parser.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return string
     */
    public function testItemAction(Feed $feed)
    {
        return $this->render('Api43FeedBundle:FeedItem:preview.html.twig', [
            'feed' => $feed,
        ]);
    }

    /**
     * Following the previous action, this one will actually parse the content (for both parser).
     *
     * @param Request $request
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return string
     */
    public function previewNewAction(Request $request, Feed $feed)
    {
        $rssFeed = $this
            ->get('simple_pie_proxy')
            ->setUrl($feed->getLink())
            ->init();

        try {
            $parser = $this
                ->get('content_extractor')
                ->init($request->get('parser'), $feed);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $firstItem = $rssFeed->get_item(0);
        if (!$firstItem) {
            throw $this->createNotFoundException('No item found in this feed.');
        }

        $content = $parser->parseContent(
            $firstItem->get_permalink(),
            $firstItem->get_description()
        );

        return $this->render('Api43FeedBundle:FeedItem:content.html.twig', [
            'title' => html_entity_decode($firstItem->get_title(), ENT_COMPAT, 'UTF-8'),
            'content' => $content->content,
            'modal' => false,
            'url' => $content->url,
            'defaultContent' => $content->useDefault,
        ]);
    }

    private function createDeleteAllForm()
    {
        return $this->createFormBuilder()->getForm();
    }

    /**
     * Returns the DocumentManager.
     *
     * @return DocumentManager
     */
    private function getDocumentManager()
    {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }
}
