<?php

namespace App\EventListener;

use App\Entity\SignalementUserAffectation;
use App\Service\NotificationService;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PersistListener
{
    private NotificationService $notifier;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->notifier = $notificationService;
        $this->urlGenerator = $urlGenerator;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        if ($entity instanceof SignalementUserAffectation) {
            $this->notifier->send(NotificationService::TYPE_AFFECTATION, $entity->getUser()->getEmail(), [
                'link' => $this->urlGenerator->generate('back_signalement_view', [
                    'uuid' => $entity->getSignalement()->getUuid()
                ], $this->urlGenerator::ABSOLUTE_PATH)
            ]);
        }
    }
}

