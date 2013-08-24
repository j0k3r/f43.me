<?php

namespace j0k3r\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
        $dm   = $this->getDocumentManager();
        $logs = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findLastUpdated();

        // loop through all logs and retrieve the feed document
        // since we use the php function to query, object aren't hydrated
        foreach ($logs as $key => &$log) {
            $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->find($log['feed_id']);

            // do not include private feed in public view
            if ($feed->getIsPrivate()) {
                unset($logs[$key]);
            }

            $log['feed'] = $feed;
        }

        return array(
            'logs' => $logs,
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
        $dm    = $this->getDocumentManager();
        $feeds = $dm->getRepository('j0k3rFeedBundle:Feed')->findAllOrderedByDate();

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
     * @return array
     */
    public function createAction(Request $request)
    {
        $feed = new Feed();
        $form = $this->createForm(new FeedType(), $feed);
        $form->bind($request);

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
     * @param string $slug The document Slug
     *
     * @return array
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function editAction($slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $editForm   = $this->createForm(new FeedType(), $feed);
        $deleteForm = $this->createDeleteForm($feed->getId());

        $lastItem   = $dm->getRepository('j0k3rFeedBundle:FeedItem')->findLastItemByFeedId($feed->getId());
        $lastLog    = $dm->getRepository('j0k3rFeedBundle:FeedLog')->findLastItemByFeedId($feed->getId());

        return array(
            'menu'        => 'feed',
            'feed'        => $feed,
            'lastItem'    => $lastItem,
            'lastLog'     => $lastLog,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Feed document.
     *
     * @Template("j0k3rFeedBundle:Feed:edit.html.twig")
     *
     * @param Request $request The request object
     * @param string  $slug    The document Slug
     *
     * @return array
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function updateAction(Request $request, $slug)
    {
        $dm   = $this->getDocumentManager();
        $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

        if (!$feed) {
            throw $this->createNotFoundException('Unable to find Feed document.');
        }

        $editForm   = $this->createForm(new FeedType(), $feed);
        $deleteForm = $this->createDeleteForm($feed->getId());

        $editForm->bind($request);

        if ($editForm->isValid()) {
            $dm->persist($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document updated!');

            return $this->redirect($this->generateUrl('feed_edit', array('slug' => $slug)));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Form is invalid.');
        }

        return array(
            'menu'        => 'feed',
            'feed'        => $feed,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Feed document.
     *
     * @param Request $request The request object
     * @param string  $slug    The document Slug
     *
     * @return array
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If document doesn't exists
     */
    public function deleteAction(Request $request, $slug)
    {
        $form = $this->createDeleteForm($slug);
        $form->bind($request);

        if ($form->isValid()) {
            $dm   = $this->getDocumentManager();
            $feed = $dm->getRepository('j0k3rFeedBundle:Feed')->findOneBySlug($slug);

            if (!$feed) {
                throw $this->createNotFoundException('Unable to find Feed.');
            }

            $dm->remove($feed);
            $dm->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Document deleted!');
        }

        return $this->redirect($this->generateUrl('feed_homepage'));
    }

    private function createDeleteForm($slug)
    {
        return $this->createFormBuilder(array('slug' => $slug))
            ->add('slug', 'hidden')
            ->getForm()
        ;
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
