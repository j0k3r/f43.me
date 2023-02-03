<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @Route("/login", name="login", methods={"GET"})
     *
     * @Template()
     *
     * @return Response|RedirectResponse
     */
    public function loginAction(Request $request, AuthorizationCheckerInterface $authorizationChecker, AuthenticationUtils $authenticationUtils)
    {
        if (true === $authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('feed_dashboard'));
        }

        return $this->render('default/Security/login.html.twig', [
            // last username entered by the user
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
