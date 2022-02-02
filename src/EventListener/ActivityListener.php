<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivityListener implements EventSubscriberInterface
{
    private NotificationService $notifier;
    private UrlGeneratorInterface $urlGenerator;

    public function getSubscribedEvents(): array
    {
        return [
            Events::postFlush,
        ];
    }

    public function __construct(NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->notifier = $notificationService;
        $this->urlGenerator = $urlGenerator;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        foreach ($entityManager->getUnitOfWork()->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
                if ($entity instanceof Signalement) {
                    if (!$entityManager->contains($entity)) {
                        $emails = [$entity->getMailDeclarant() ?? null, $entity->getMailOccupant() ?? null];
                        array_map(function ($email) use ($entity) {
                            null !== $this->notifier->send(NotificationService::TYPE_ACCUSE_RECEPTION, $email, ['signalement' => $entity]);
                        }, $emails);
                    } elseif(!empty($changeSet['statut'])) {
                        if ($changeSet['statut'][1] === Signalement::STATUS_ACTIVE) {
                            $emails = [$entity->getMailDeclarant() ?? null, $entity->getMailOccupant() ?? null];
                            array_map(function ($email) use ($entity) {
                                null !== $email && $this->notifier->send(NotificationService::TYPE_SIGNALEMENT_VALIDE, $email, [
                                    'signalement' => $entity,
                                    'lien_suivi' => $this->urlGenerator->generate('front_suivi_signalement', ['code' => $entity->getCodeSuivi()], $this->urlGenerator::ABSOLUTE_URL)
                                ]);
                            }, $emails);
                        }
                    }
                }
                if ($entity instanceof SignalementUserAffectation) {
                    if (!$entityManager->contains($entity)) {
                        if ($entity->getUser()->getIsMailingActive() && $entity->getUser()->getStatut() === User::STATUS_ACTIVE) {
                            $this->notifier->send(NotificationService::TYPE_AFFECTATION, $entity->getUser()->getEmail(), [
                                'link' => $this->urlGenerator->generate('back_signalement_view', [
                                    'uuid' => $entity->getSignalement()->getUuid()
                                ], $this->urlGenerator::ABSOLUTE_PATH)
                            ]);
                        }
                    }
                }
                if ($entity instanceof User) {
                    if (!$entityManager->contains($entity)) {
                        if ($entity->getPartenaire()) {
                            $entity->getPartenaire()->getAffectations()->filter(function (SignalementUserAffectation $affectation) use ($entity, $entityManager) {
                                $aff = new SignalementUserAffectation();
                                $aff->setPartenaire($entity->getPartenaire());
                                $aff->setUser($entity);
                                $aff->setSignalement($affectation->getSignalement());
                                $entityManager->persist($aff);
                            });
                            $entityManager->flush();
                        }
                    }
                }
            }
        }
    }

}

