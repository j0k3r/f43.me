<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Display some information about feeds, items, logs, etc ...
     *
     * @return Response|RedirectResponse
     */
    #[Route(path: '/login', name: 'login', methods: ['GET'])]
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
