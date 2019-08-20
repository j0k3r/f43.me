<?php

namespace AppBundle\Controller;

use AppBundle\Content\Extractor;
use AppBundle\Entity\Feed;
use AppBundle\Entity\Item;
use AppBundle\Repository\ItemRepository;
use AppBundle\Xml\SimplePieProxy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ItemController extends Controller
{
    /**
     * Lists all Items documents related to a Feed.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function indexAction(Feed $feed, ItemRepository $itemRepository)
    {
        $items = $itemRepository->findByFeed(
            $feed->getId(),
            $feed->getSortBy()
        );

        return $this->render('AppBundle:Item:index.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'items' => $items,
            'delete_all_form' => $this->createFormBuilder()->getForm()->createView(),
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
    public function deleteAllAction(Request $request, Feed $feed, ItemRepository $itemRepository, EntityManagerInterface $em, Session $session)
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $res = $itemRepository->deleteAllByFeedId($feed->getId());

            $feed->setNbItems(0);
            $em->persist($feed);
            $em->flush();

            $session->getFlashBag()->add('notice', $res . ' items deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
    }

    /**
     * Preview an item that is already cached.
     *
     * @param Item $feedItem The document Item (retrieving for a ParamConverter with the id)
     *
     * @return Response
     */
    public function previewCachedAction(Item $feedItem)
    {
        return $this->render('AppBundle:Item:content.html.twig', [
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
     * @return Response
     */
    public function testItemAction(Feed $feed)
    {
        return $this->render('AppBundle:Item:preview.html.twig', [
            'feed' => $feed,
        ]);
    }

    /**
     * Following the previous action, this one will actually parse the content (for both parser).
     *
     * @param Request $request
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function previewNewAction(Request $request, Feed $feed, SimplePieProxy $simplePieProxy, Extractor $contentExtractor)
    {
        $rssFeed = $simplePieProxy
            ->setUrl($feed->getLink())
            ->init();

        try {
            $parser = $contentExtractor->init($request->get('parser'), $feed);
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

        return $this->render('AppBundle:Item:content.html.twig', [
            'title' => html_entity_decode($firstItem->get_title(), ENT_COMPAT, 'UTF-8'),
            'content' => $content->content,
            'modal' => false,
            'url' => $content->url,
            'defaultContent' => $content->useDefault,
        ]);
    }
}
