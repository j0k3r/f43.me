<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Form\FeedType;

/**
 * Feed controller.
 */
class FeedController extends Controller
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @Template()
     *
     * @return array
     */
    public function dashboardAction()
    {
        $dm          = $this->getDocumentManager();
        $feeds       = $dm->getRepository('j0k3rFeedBundle:Feed')->findAllOrderedByDate(20);
        $feedlogs    = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findAllOrderedById(10);
        $historylogs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findStatsForLastDays();

        return array(
            'menu'        => 'dashboard',
            'feedlogs'    => $feedlogs,
            'feeds'       => $feeds,
            'historylogs' => $historylogs,
        );
    }

    /**
     * Display a public view
     *
     * @Template()
     *
     * @return array
     */
    public function publicAction()
    {
        $feeds = $this->getDocumentManager()
            ->getRepository('j0k3rFeedBundle:Feed')
            ->findForPublic();

        return array(
            'feeds' => $feeds,
        );
    }

    /**
     * Lists all Feed documents.
     *
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $feeds = $this->getDocumentManager()
            ->getRepository('j0k3rFeedBundle:Feed')
            ->findAllOrderedByDate();

        return array(
            'menu'  => 'feed',
            'feeds' => $feeds
        );
    }

    /**
     * Displays a form to create a new Feed document.
     *
     * @Template()
     *
     * @return array
     */
    public function newAction()
    {
        $feed = new Feed();
        $form = $this->createForm(new FeedType(), $feed);

        return array(
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView()
        );
    }

    /**
     * Creates a new Feed document.
     *
     * @Template("j0k3rFeedBundle:Feed:new.html.twig")
     *
     * @param Request $request
     *
     * @return RedirectResponse|array
     */
    public function createAction(Request $request)
    {
        $feed = new Feed();
        $form = $this->createForm(new FeedType(), $feed);
        $form->submit($request);

        if ($form->isValid()) {
            $dm = $this->getDocumentManager();
            $dm->persist($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document created!');

            return $this->redirect($this->generateUrl('feed_edit', array('slug' => $feed->getSlug())));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Form is invalid.');
        }

        return array(
            'menu' => 'feed',
            'feed' => $feed,
            'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing Feed document.
     *
     * @Template()
     *
     * @param Feed $feed The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return RedirectResponse|array
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction(Request $request, Feed $feed)
    {
        $dm   = $this->getDocumentManager();

        $editForm   = $this->createForm(new FeedType(), $feed);
        $deleteForm = $this->createDeleteForm();

        $lastItem   = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findLastItemByFeedId($feed->getId());
        $lastLog    = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findLastItemByFeedId($feed->getId());
        $nbLogs     = $dm->getRepository('j0k3rFeedBundle:FeedLog')->countByFeedId($feed->getId());

        if ($request->isMethod('POST')) {
            $editForm->submit($request);

            if ($editForm->isValid()) {
                $dm->persist($feed);
                $dm->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Document updated!');

                return $this->redirect($this->generateUrl('feed_edit', array('slug' => $feed->getSlug())));
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Form is invalid.');
            }
        }

        return array(
            'menu'        => 'feed',
            'feed'        => $feed,
            'infos'       => array(
                'last_item' => $lastItem,
                'last_log'  => $lastLog,
                'nb_logs'   => $nbLogs,
            ),
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Feed document.
     *
     * @param Request $request The request object
     * @param Feed    $feed    The document Feed (retrieving for a ParamConverter with the slug)
     *
     * @return RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, Feed $feed)
    {
        $form = $this->createDeleteForm();
        $form->submit($request);

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
            $this->get('rss_render')->render($feed),
            200,
            array('Content-Type' => 'text/xml')
        );
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
