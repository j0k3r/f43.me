<?php

namespace AppBundle\Controller;

use AppBundle\Document\Feed;
use AppBundle\Repository\FeedLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * FeedLog controller.
 */
class FeedLogController extends Controller
{
    /**
     * Lists all FeedLog documents.
     *
     * @return Response
     */
    public function indexAction(FeedLogRepository $feedLogRepository)
    {
        return $this->render('AppBundle:FeedLog:index.html.twig', [
            'menu' => 'log',
            'feedlogs' => $feedLogRepository->findAllOrderedById(100),
        ]);
    }

    /**
     * Lists all FeedLog documents related to a given feed.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function feedAction(Feed $feed, FeedLogRepository $feedLogRepository)
    {
        $feedlogs = $feedLogRepository->findByFeedId($feed->getId());

        $deleteAllForm = $this->createDeleteAllForm();

        return $this->render('AppBundle:FeedLog:feed.html.twig', [
            'menu' => 'log',
            'feed' => $feed,
            'feedlogs' => $feedlogs,
            'delete_all_form' => $deleteAllForm->createView(),
        ]);
    }

    /**
     * Delete all logs for a given Feed.
     *
     * @param Request $request
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return RedirectResponse
     */
    public function deleteAllAction(Request $request, Feed $feed, FeedLogRepository $feedLogRepository, Session $session)
    {
        $form = $this->createDeleteAllForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $res = $feedLogRepository->deleteAllByFeedId($feed->getId());

            $session->getFlashBag()->add('notice', $res['n'] . ' documents deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
    }

    private function createDeleteAllForm()
    {
        return $this->createFormBuilder()->getForm();
    }
}
