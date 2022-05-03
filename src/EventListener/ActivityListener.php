<?php

namespace App\EventListener;

use App\Entity\Affectation;
use App\Entity\Notification;
use App\Entity\Partenaire;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActivityListener implements EventSubscriberInterface
{
    private NotificationService $notifier;
    private UrlGeneratorInterface $urlGenerator;

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::preRemove,
        ];
    }

    public function __construct(NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->notifier = $notificationService;
        $this->urlGenerator = $urlGenerator;
        $this->tos = new ArrayCollection();
        $this->uow = null;
        $this->em = null;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Affectation) {
                $partenaire = $entity->getPartenaire();
                $this->notifyPartner($partenaire,$entity,Notification::TYPE_AFFECTATION,NotificationService::TYPE_AFFECTATION);
            }
            if ($entity instanceof Suivi) {
                $entity->getSignalement()->getAffectations()->filter(function (Affectation $affectation) use ($entity) {
                    $partenaire = $affectation->getPartenaire();
                    $this->notifyPartner($partenaire,$entity,Notification::TYPE_SUIVI,NotificationService::TYPE_NOUVEAU_SUIVI_BACK);
                });
                if($entity->getIsPublic())
                {
                    $this->notifier->send(NotificationService::TYPE_NOUVEAU_SUIVI, [$entity->getSignalement()->getMailDeclarant(), $entity->getSignalement()->getMailOccupant()], [
                        'signalement' => $entity->getSignalement(),
                        'lien_suivi' => $this->urlGenerator->generate('front_suivi_signalement', ['code' => $entity->getSignalement()->getCodeSuivi()], 0)
                    ]);
                }
            }
        }
    }

    public function preRemove(LifecycleEventArgs $args){
        $entity = $args->getObject();
        if($entity instanceof Affectation){
            $entity->getNotifications()->filter(function (Notification $notification) use ($args) {
                $args->getObjectManager()->remove($notification);
            });
            $args->getObjectManager()->flush();
        }
    }

    private function notifyPartner($partner,$entity,$inAppType,$mailType){
        if ($partner->getEmail()) {
            $this->tos->add($partner->getEmail());
        }
        $partner->getUsers()->filter(function (User $user) use ($inAppType, $entity) {
            if ($user->getStatut() !== User::STATUS_ARCHIVE) {
                $this->createInAppNotification($user, $entity, $inAppType);
                if ($user->getIsMailingActive())
                    $this->tos->add($user->getEmail());
            }
        });
        if(!$this->tos->isEmpty())
        {
            $this->notifier->send($mailType, $this->tos->toArray(), [
                'link' => $this->urlGenerator->generate('back_signalement_view', [
                    'uuid' => $entity->getSignalement()->getUuid()
                ], 0)
            ]);
        }
    }

    private function createInAppNotification($user, $entity, $type)
    {
        $notification = new Notification();
        $notification->setUser($user);
        switch ($type) {
            case Notification::TYPE_SUIVI:
                $notification->setSuivi($entity);
                break;
            default:
                $notification->setAffectation($entity);
                break;
        }
        $notification->setSignalement($entity->getSignalement());
        $notification->setType($type);
        $this->em->persist($notification);
        $this->uow->computeChangeSet(
            $this->em->getClassMetadata(Notification::class),
            $notification
        );
    }

}

