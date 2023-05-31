<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private BillingClient $billingClient;
    private Security $security;

    public function __construct(Security $security, BillingClient $billingClient)
    {
        $this->security = $security;
        $this->billingClient = $billingClient;
    }

    /**
     * @Route("/profile", name="app_profile")
     */
    public function profile(): Response
    {
        $user = $this->billingClient->getCurrentUser($this->getUser()->getApiToken());

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}
