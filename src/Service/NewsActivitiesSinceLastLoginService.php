<?php

namespace App\Service;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsActivitiesSinceLastLoginService
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function set($user)
    {
        $newsActivitiesSinceLastLogin = new ArrayCollection();
        $user->getAffectations()->filter(function (SignalementUserAffectation $affectation)use ($newsActivitiesSinceLastLogin,$user){
            $affectation->getSignalement()->getSuivis()->filter(function (Suivi $suivi)use ($newsActivitiesSinceLastLogin, $user){
                if ($suivi->getCreatedAt() > $user->getLastLoginAt())
                    $newsActivitiesSinceLastLogin->add($suivi);
            });
        });
        return $this->requestStack->getSession()->set('_newsActivitiesSinceLastLogin',$newsActivitiesSinceLastLogin);
    }

    public function getAll(): bool|ArrayCollection
    {
        return $this->requestStack->getSession()->get('_newsActivitiesSinceLastLogin');
    }

    public function count(): int
    {
        return count($this->getAll());
    }

    public function update(Signalement $signalement): ArrayCollection|bool
    {
        $news = $this->getAll();
        $news->filter(function (Suivi $new)use ($news,$signalement){
            if($signalement->getId() === $new->getSignalement()->getId())
                $news->removeElement($new);
        });
        return $news;
    }
}