<?php

namespace App\Controller;

use App\Entity\Feed;
use App\Event\NewFeedEvent;
use App\Form\Type\FeedType;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use App\Repository\LogRepository;
use App\Xml\Render;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class FeedController extends AbstractController
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @Route("/dashboard", name="feed_dashboard", methods={"GET"})
     *
     * @return Response
     */
    public function dashboardAction(FeedRepository $feedRepository, LogRepository $logRepository)
    {
        $feeds = $feedRepository->findAllOrderedByDate(20);
        $feedlogs = $logRepository->findAllOrderedById(10);
        $historylogs = $logRepository->findStatsForLastDays();

        return $this->render('default/Feed/dashboard.html.twig', [
            'menu' => 'dashboard',
            'feedlogs' => $feedlogs,
            'feeds' => $feeds,
            'historylogs' => $historylogs,
        ]);
    }

    /**
     * Display a public view.
     *
     * @Route("/", name="feed_publicview", methods={"GET"})
     *
     * @return Response
     */
    public function publicAction(FeedRepository $feedRepository)
    {
        return $this->render('default/Feed/public.html.twig', [
            'feeds' => $feedRepository->findForPublic(),
        ]);
    }

    /**
     * Lists all Feed documents.
     *
     * @Route("/feeds", name="feed_homepage", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction(FeedRepository $feedRepository)
    {
        return $this->render('default/Feed/index.html.twig', [
            'menu' => 'feed',
            'feeds' => $feedRepository->findAllOrderedByDate(),
        ]);
    }

    /**
     * Displays a form to create a new Feed document.
     *
     * @Route("/feed/new", name="feed_new", methods={"GET"})
     *
     * @return Response
     */
    public function newAction()
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed, ['action' => $this->generateUrl('feed_create')]);

        return $this->render('default/Feed/new.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new Feed document.
     *
     * @Route("/feed/create", name="feed_create", methods={"POST"})
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, EntityManagerInterface $em, Session $session, EventDispatcherInterface $eventDispatcher)
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($feed);
            $em->flush();

            $eventDispatcher->dispatch(new NewFeedEvent($feed));

            $session->getFlashBag()->add('notice', 'Feed created!');

            return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
        }

        $session->getFlashBag()->add('error', 'Form is invalid.');

        return $this->render('default/Feed/new.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Feed document.
     *
     * @Route("/feed/{slug}/edit", name="feed_edit", methods={"GET", "POST"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     *
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, Feed $feed, EntityManagerInterface $em, LogRepository $logRepository, ItemRepository $itemRepository, Session $session)
    {
        $editForm = $this->createForm(FeedType::class, $feed);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            if ($editForm->isValid()) {
                $em->persist($feed);
                $em->flush();

                $session->getFlashBag()->add('notice', 'Feed updated!');

                return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
            }
            $session->getFlashBag()->add('error', 'Form is invalid.');
        }

        $lastItem = $itemRepository->findLastItemByFeedId($feed->getId());
        $lastLog = $logRepository->findLastItemByFeedId($feed->getId());
        $nbLogs = $logRepository->countByFeedId($feed->getId());

        return $this->render('default/Feed/edit.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'infos' => [
                'last_item' => $lastItem,
                'last_log' => $lastLog,
                'nb_logs' => $nbLogs,
            ],
            'edit_form' => $editForm->createView(),
            'delete_form' => $this->createFormBuilder()->getForm()->createView(),
        ]);
    }

    /**
     * Deletes a Feed document.
     *
     * @Route("/feed/{slug}/delete", name="feed_delete", methods={"POST"})
     *
     * @param Request $request The request object
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Feed $feed, EntityManagerInterface $em, Session $session)
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->remove($feed);
            $em->flush();

            $session->getFlashBag()->add('notice', 'Feed deleted!');
        }

        return $this->redirect($this->generateUrl('feed_homepage'));
    }

    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @Route("/{slug}.xml", name="feed_xml", methods={"GET"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function xmlAction(Feed $feed, Render $xmlRender)
    {
        return new Response(
            $xmlRender->doRender($feed),
            200,
            ['Content-Type' => 'text/xml']
        );
    }
}
