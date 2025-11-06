<?php

namespace App\Controller;

use App\Entity\Feed;
use App\Repository\LogRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends AbstractController
{
    /**
     * Lists all Log documents.
     */
    #[Route(path: '/logs', name: 'log_homepage', methods: ['GET'])]
    public function indexAction(LogRepository $logRepository): Response
    {
        return $this->render('default/Log/index.html.twig', [
            'menu' => 'log',
            'feedlogs' => $logRepository->findAllOrderedById(100),
        ]);
    }

    /**
     * Lists all Log documents related to a given feed.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    #[Route(path: '/feed/{slug}/logs', name: 'log_feed', methods: ['GET'])]
    public function feedAction(#[MapEntity(mapping: ['slug' => 'slug'])] Feed $feed, LogRepository $logRepository): Response
    {
        return $this->render('default/Log/feed.html.twig', [
            'menu' => 'log',
            'feed' => $feed,
            'feedlogs' => $logRepository->findByFeedId($feed->getId()),
            'delete_all_form' => $this->createFormBuilder()->getForm(),
        ]);
    }

    /**
     * Delete all logs for a given Feed.
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     */
    #[Route(path: '/feed/{slug}/logs/deleteAll', name: 'log_delete_all', methods: ['POST'])]
    public function deleteAllAction(Request $request, #[MapEntity(mapping: ['slug' => 'slug'])] Feed $feed, LogRepository $logRepository, Session $session): RedirectResponse
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
