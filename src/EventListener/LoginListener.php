<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\NewsActivitiesSinceLastLoginService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private EntityManagerInterface $em;
    private NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService;

    public function __construct(EntityManagerInterface $em,NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService)
    {
        $this->em = $em;
        $this->newsActivitiesSinceLastLoginService = $newsActivitiesSinceLastLoginService;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $this->newsActivitiesSinceLastLoginService->clear();
        if($user->getLastLoginAt())
        {
            $this->newsActivitiesSinceLastLoginService->set($user);
        }
        $user->setLastLoginAt(new \DateTimeImmutable());

        // Persist the data to database.
        $this->em->persist($user);
        $this->em->flush();
    }

}