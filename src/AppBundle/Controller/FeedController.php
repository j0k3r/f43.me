<?php

namespace AppBundle\Controller;

use AppBundle\Document\Feed;
use AppBundle\Form\Type\FeedType;
use AppBundle\Repository\FeedItemRepository;
use AppBundle\Repository\FeedLogRepository;
use AppBundle\Repository\FeedRepository;
use AppBundle\Xml\Render;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Feed controller.
 */
class FeedController extends Controller
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @return array
     */
    public function dashboardAction(FeedRepository $feedRepository, FeedLogRepository $feedLogRepository, FeedItemRepository $feedItemRepository)
    {
        $feeds = $feedRepository->findAllOrderedByDate(20);
        $feedlogs = $feedItemRepository->findAllOrderedById(10);
        $historylogs = $feedLogRepository->findStatsForLastDays();

        return $this->render('AppBundle:Feed:dashboard.html.twig', [
            'menu' => 'dashboard',
            'feedlogs' => $feedlogs,
            'feeds' => $feeds,
            'historylogs' => $historylogs,
        ]);
    }

    /**
     * Display a public view.
     *
     * @return array
     */
    public function publicAction(FeedRepository $feedRepository)
    {
        return $this->render('AppBundle:Feed:public.html.twig', [
            'feeds' => $feedRepository->findForPublic(),
        ]);
    }

    /**
     * Lists all Feed documents.
     *
     * @return array
     */
    public function indexAction(FeedRepository $feedRepository)
    {
        return $this->render('AppBundle:Feed:index.html.twig', [
            'menu' => 'feed',
            'feeds' => $feedRepository->findAllOrderedByDate(),
        ]);
    }

    /**
     * Displays a form to create a new Feed document.
     *
     * @return array
     */
    public function newAction()
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed, ['action' => $this->generateUrl('feed_create')]);

        return $this->render('AppBundle:Feed:new.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new Feed document.
     *
     * @param Request $request
     *
     * @return RedirectResponse|array
     */
    public function createAction(Request $request, DocumentManager $dm, Session $session)
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $dm->persist($feed);
            $dm->flush();

            $session->getFlashBag()->add('notice', 'Document created!');

            return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
        }

        $session->getFlashBag()->add('error', 'Form is invalid.');

        return $this->render('AppBundle:Feed:new.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Feed document.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     *
     * @return RedirectResponse|array
     */
    public function editAction(Request $request, Feed $feed, DocumentManager $dm, FeedLogRepository $feedLogRepository, FeedItemRepository $feedItemRepository, Session $session)
    {
        $editForm = $this->createForm(FeedType::class, $feed);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted()) {
            if ($editForm->isValid()) {
                $dm->persist($feed);
                $dm->flush();

                $sesion->getFlashBag()->add('notice', 'Document updated!');

                return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
            }
            $sesion->getFlashBag()->add('error', 'Form is invalid.');
        }

        $lastItem = $feedItemRepository->findLastItemByFeedId($feed->getId());
        $lastLog = $feedLogRepository->findLastItemByFeedId($feed->getId());
        $nbLogs = $feedLogRepository->countByFeedId($feed->getId());

        $deleteForm = $this->createDeleteForm();

        return $this->render('AppBundle:Feed:edit.html.twig', [
            'menu' => 'feed',
            'feed' => $feed,
            'infos' => [
                'last_item' => $lastItem,
                'last_log' => $lastLog,
                'nb_logs' => $nbLogs,
            ],
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a Feed document.
     *
     * @param Request $request The request object
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Feed $feed, DocumentManager $dm, Session $session)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $dm->remove($feed);
            $dm->flush();

            $session->getFlashBag()->add('notice', 'Document deleted!');
        }

        return $this->redirect($this->generateUrl('feed_homepage'));
    }

    /**
     * Display some information about feeds, items, logs, etc ...
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

    private function createDeleteForm()
    {
        return $this->createFormBuilder()->getForm();
    }
}
