<?php

namespace App\Controller;

use App\Entity\Feed;
use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    /**
     * Lists all Log documents.
     *
     * @Route("/logs", name="log_homepage", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction(LogRepository $logRepository)
    {
        return $this->render('default/Log/index.html.twig', [
            'menu' => 'log',
            'feedlogs' => $logRepository->findAllOrderedById(100),
        ]);
    }

    /**
     * Lists all Log documents related to a given feed.
     *
     * @Route("/feed/{slug}/logs", name="log_feed", methods={"GET"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function feedAction(Feed $feed, LogRepository $logRepository)
    {
        return $this->render('default/Log/feed.html.twig', [
            'menu' => 'log',
            'feed' => $feed,
            'feedlogs' => $logRepository->findByFeedId($feed->getId()),
            'delete_all_form' => $this->createFormBuilder()->getForm()->createView(),
        ]);
    }

    /**
     * Delete all logs for a given Feed.
     *
     * @Route("/feed/{slug}/logs/deleteAll", name="log_delete_all", methods={"POST"})
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return RedirectResponse
     */
    public function deleteAllAction(Request $request, Feed $feed, LogRepository $logRepository, Session $session)
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $res = $logRepository->deleteAllByFeedId($feed->getId());

            $session->getFlashBag()->add('notice', $res . ' logs deleted!');
        }

        return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
    }
}
