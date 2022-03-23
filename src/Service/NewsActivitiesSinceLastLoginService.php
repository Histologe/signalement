<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsActivitiesSinceLastLoginService
{
    private RequestStack $requestStack;
    private array $activities;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function set($user)
    {
        $activities = $this->requestStack->getSession()->get('lastActionTime');
        $newsActivitiesSinceLastLogin = new ArrayCollection();
        $user->getPartenaire()?->getAffectations()->filter(function (Affectation $affectation) use ($newsActivitiesSinceLastLogin, $user,$activities) {
            $affectation->getSignalement()->getSuivis()->filter(function (Suivi $suivi) use ($newsActivitiesSinceLastLogin, $user,$activities) {
                if (!isset($activities[$suivi->getSignalement()->getId()]) && $suivi->getCreatedAt() > $user->getLastLoginAt()
                    || isset($activities[$suivi->getSignalement()->getId()]) && $activities[$suivi->getSignalement()->getId()] < $suivi->getCreatedAt())
                    $newsActivitiesSinceLastLogin->add($suivi);
            });
            if ($affectation->getStatut() === Affectation::STATUS_WAIT && $affectation->getPartenaire() && $affectation->getCreatedAt()->diff(new \DateTimeImmutable())->days < 31)
                $newsActivitiesSinceLastLogin->add($affectation);
        });
        return $this->requestStack->getSession()->set('_newsActivitiesSinceLastLogin', $newsActivitiesSinceLastLogin);
    }

    public function getAll(): bool|ArrayCollection|null
    {
        return $this->requestStack->getSession()->get('_newsActivitiesSinceLastLogin');
    }

    public function count(): int
    {
        if ($this->getAll())
            return count($this->getAll());
        return 0;
    }

    public function update(Signalement $signalement): ArrayCollection|bool|null
    {
        $activities = $this->requestStack->getSession()->get('lastActionTime');
        $activities[$signalement->getId()] = new \DateTimeImmutable();
        $this->requestStack->getSession()->set('lastActionTime', $activities);
        $news = $this->getAll();
        $news?->filter(function (Suivi|Affectation $new) use ($news, $signalement) {
            if ($signalement->getId() === $new->getSignalement()->getId() && $new instanceof Suivi)
                $news->removeElement($new);
        });
        return $news;
    }

    public function clear(): ArrayCollection|bool|null
    {
        $this->requestStack->getSession()->remove('lastActionTime');
        $news = $this->getAll();
        $news?->filter(function (Suivi|Affectation $new) use ($news) {
            $news->removeElement($new);
        });
        return $news;
    }
}