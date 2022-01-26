<?php

namespace App\EventListener;

use App\Entity\Partenaire;
use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChangeListener implements EventSubscriberInterface
{
    private NotificationService $notifier;
    private UrlGeneratorInterface $urlGenerator;

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function __construct(NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->notifier = $notificationService;
        $this->urlGenerator = $urlGenerator;
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        if ($entity instanceof Signalement) {
            //SIGNALEMENT VALIDE
            $emails = [$entity->getMailDeclarant() ?? null, $entity->getMailOccupant() ?? null];
            if ($entity->getStatut() === Signalement::STATUS_NEW) {
                foreach ($emails as $email)
                    if ($email)
                        $this->notifier->send(NotificationService::TYPE_SIGNALEMENT_VALIDE, $email, [
                            'signalement' => $entity,
                            'lien_suivi' => $this->urlGenerator->generate('front_suivi_signalement', ['code' => $entity->getCodeSuivi()], $this->urlGenerator::ABSOLUTE_URL)
                        ]);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        //NOUVEAU SIGNALEMENT
        if ($entity instanceof Signalement) {
            $emails = [$entity->getMailDeclarant() ?? null, $entity->getMailOccupant() ?? null];
            foreach ($emails as $email)
                if ($email)
                    $this->notifier->send(NotificationService::TYPE_ACCUSE_RECEPTION, $email, ['signalement' => $entity]);
                /** @var Partenaire $partenaire */
            foreach ($entityManager->getRepository(Partenaire::class)->findAllOrByInseeIfCommune($entity->getInseeOccupant()) as $partenaire){
                $partenaire->getUsersAffectable($entity)->filter(function (User $user)use ($entity){
                    if($user->getIsMailingActive())
                        $this->notifier->send(NotificationService::TYPE_NEW_SIGNALEMENT, $user->getEmail(), ['signalement' => $entity]);
                });
            }
        }
        //NOUVELLE AFFECTATION
        if ($entity instanceof SignalementUserAffectation) {
            if($entity->getUser()->getIsMailingActive() && $entity->getUser()->getStatut() === User::STATUS_ACTIVE)
            {
                $this->notifier->send(NotificationService::TYPE_AFFECTATION, $entity->getUser()->getEmail(), [
                    'link' => $this->urlGenerator->generate('back_signalement_view', [
                        'uuid' => $entity->getSignalement()->getUuid()
                    ], $this->urlGenerator::ABSOLUTE_PATH)
                ]);
            }
        }
        //NOUVEL UTILISATEUR
        if ($entity instanceof User) {
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

