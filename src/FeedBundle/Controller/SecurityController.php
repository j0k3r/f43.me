<?php

namespace Api43\FeedBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Security controller.
 */
class SecurityController extends Controller
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @Template()
     *
     * @return array|RedirectResponse
     */
    public function loginAction(Request $request)
    {
        if (true === $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('feed_dashboard'));
        }

        $helper = $this->get('security.authentication_utils');

        return array(
            // last username entered by the user
            'last_username' => $helper->getLastUsername(),
            'error' => $helper->getLastAuthenticationError(),
        );
    }
}
