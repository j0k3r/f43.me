<?php

namespace Api43\FeedBundle\Controller;

use Api43\FeedBundle\Document\Feed;
use Api43\FeedBundle\Form\Type\FeedType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function dashboardAction()
    {
        $dm = $this->getDocumentManager();
        $feeds = $dm->getRepository('Api43FeedBundle:Feed')->findAllOrderedByDate(20);
        $feedlogs = $dm->getRepository('Api43FeedBundle:FeedLog')->findAllOrderedById(10);
        $historylogs = $dm->getRepository('Api43FeedBundle:FeedLog')->findStatsForLastDays();

        return $this->render('Api43FeedBundle:Feed:dashboard.html.twig', [
            'menu'        => 'dashboard',
            'feedlogs'    => $feedlogs,
            'feeds'       => $feeds,
            'historylogs' => $historylogs,
        ]);
    }

    /**
     * Display a public view.
     *
     * @return array
     */
    public function publicAction()
    {
        $feeds = $this->getDocumentManager()
            ->getRepository('Api43FeedBundle:Feed')
            ->findForPublic();

        return $this->render('Api43FeedBundle:Feed:public.html.twig', [
            'feeds' => $feeds,
        ]);
    }

    /**
     * Lists all Feed documents.
     *
     * @return array
     */
    public function indexAction()
    {
        $feeds = $this->getDocumentManager()
            ->getRepository('Api43FeedBundle:Feed')
            ->findAllOrderedByDate();

        return $this->render('Api43FeedBundle:Feed:index.html.twig', [
            'menu'  => 'feed',
            'feeds' => $feeds,
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

        return $this->render('Api43FeedBundle:Feed:new.html.twig', [
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
    public function createAction(Request $request)
    {
        $feed = new Feed();
        $form = $this->createForm(FeedType::class, $feed);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->persist($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document created!');

            return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Form is invalid.');
        }

        return $this->render('Api43FeedBundle:Feed:new.html.twig', [
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
    public function editAction(Request $request, Feed $feed)
    {
        $editForm = $this->createForm(FeedType::class, $feed);
        $editForm->handleRequest($request);

        $dm = $this->getDocumentManager();

        if ($editForm->isSubmitted()) {
            if ($editForm->isValid()) {
                $dm->persist($feed);
                $dm->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Document updated!');

                return $this->redirect($this->generateUrl('feed_edit', ['slug' => $feed->getSlug()]));
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Form is invalid.');
            }
        }

        $lastItem = $dm->getRepository('Api43FeedBundle:FeedItem')->findLastItemByFeedId($feed->getId());
        $lastLog = $dm->getRepository('Api43FeedBundle:FeedLog')->findLastItemByFeedId($feed->getId());
        $nbLogs = $dm->getRepository('Api43FeedBundle:FeedLog')->countByFeedId($feed->getId());

        $deleteForm = $this->createDeleteForm();

        return $this->render('Api43FeedBundle:Feed:edit.html.twig', [
            'menu'  => 'feed',
            'feed'  => $feed,
            'infos' => [
                'last_item' => $lastItem,
                'last_log'  => $lastLog,
                'nb_logs'   => $nbLogs,
            ],
            'edit_form'   => $editForm->createView(),
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
    public function deleteAction(Request $request, Feed $feed)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->remove($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document deleted!');
        }

        return $this->redirect($this->generateUrl('feed_homepage'));
    }

    private function createDeleteForm()
    {
        return $this->createFormBuilder()->getForm();
    }

    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return Response
     */
    public function xmlAction(Feed $feed)
    {
        return new Response(
            $this->get('xml_render')->doRender($feed),
            200,
            ['Content-Type' => 'text/xml']
        );
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
