<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Service\NewsActivitiesSinceLastLoginService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackNewsActivitiesController extends AbstractController
{

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->affectations = new ArrayCollection();
    }

    #[Route('/news', name: 'back_news_activities')]
    public function newsActivitiesSinceLastLogin(Request $request): Response
    {
        $title = 'Administration - Nouveauté(s)';
//        dd($newsActivitiesSinceLastLoginService->getAll());
        $notifications = $this->getUser()->getNotifications();
        $notifications->filter(function (Notification $notification){
            if($notification->getType() === Notification::TYPE_AFFECTATION && $notification->getAffectation())
                $this->affectations->add($notification);
            elseif($notification->getType() === Notification::TYPE_SUIVI && $notification->getSuivi())
                $this->suivis->add($notification);
        });
        return $this->render('back/news_activities/index.html.twig', [
            'title' => $title,
            'suivis'=>$this->suivis,
            'affectations'=>$this->affectations
        ]);
    }
}