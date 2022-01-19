<?php

namespace App\EventListener;

use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        if($user->getLastLoginAt())
        {
            $newsActivitiesSinceLastLogin = new ArrayCollection();
            $user->getAffectations()->filter(function (SignalementUserAffectation $affectation)use ($newsActivitiesSinceLastLogin,$user){
                $affectation->getSignalement()->getSuivis()->filter(function (Suivi $suivi)use ($newsActivitiesSinceLastLogin, $user){
                    if ($suivi->getCreatedAt() > $user->getLastLoginAt())
                        $newsActivitiesSinceLastLogin->add($suivi->getSignalement());
                });
            });
            $user->setNewsActivitiesSinceLastLogin($newsActivitiesSinceLastLogin);
           /* dd($user->getNewsActivitiesSinceLastLogin());*/
        }

        $user->setLastLoginAt(new \DateTimeImmutable());

        // Persist the data to database.
        $this->em->persist($user);
        $this->em->flush();
    }
}