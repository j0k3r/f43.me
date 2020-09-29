<?php

namespace App\Controller;

use App\Content\Extractor;
use App\Entity\Feed;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Xml\SimplePieProxy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{
    /**
     * Lists all Items documents related to a Feed.
     *
     * @Route("/feed/{slug}/items", name="item_homepage", methods={"GET"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    public function indexAction(Feed $feed, ItemRepository $itemRepository): Response
    {
        $items = $itemRepository->findByFeed(
            $feed->getId(),
            $feed->getSortBy()
        );

        return $this->render('default/Item/index.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'items' => $items,
            'delete_all_form' => $this->createFormBuilder()->getForm()->createView(),
        ]);
    }

    /**
     * Delete all items for a given Feed.
     *
     * @Route("/feed/{slug}/items/deleteAll", name="item_delete_all", methods={"POST"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    public function deleteAllAction(Request $request, Feed $feed, ItemRepository $itemRepository, EntityManagerInterface $em, Session $session): RedirectResponse
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
     * @Route("/item/{id}/preview", name="item_preview_cached", methods={"GET"})
     *
     * @param Item $feedItem The document Item (retrieving for a ParamConverter with the id)
     */
    public function previewCachedAction(Item $feedItem): Response
    {
        return $this->render('default/Item/content.html.twig', [
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
     * @Route("/feed/{slug}/testItem", name="item_test", methods={"GET"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    public function testItemAction(Feed $feed): Response
    {
        return $this->render('default/Item/preview.html.twig', [
            'feed' => $feed,
        ]);
    }

    /**
     * Following the previous action, this one will actually parse the content (for both parser).
     *
     * @Route("/feed/{slug}/previewItem", name="item_preview_new", methods={"GET"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    public function previewNewAction(Request $request, Feed $feed, SimplePieProxy $simplePieProxy, Extractor $contentExtractor): Response
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
            (string) $firstItem->get_permalink(),
            $firstItem->get_description()
        );

        return $this->render('default/Item/content.html.twig', [
            'title' => html_entity_decode((string) $firstItem->get_title(), ENT_COMPAT, 'UTF-8'),
            'content' => $content->content,
            'modal' => false,
            'url' => $content->url,
            'defaultContent' => $content->useDefault,
        ]);
    }
}
