<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Service\NewsActivitiesSinceLastLoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackNewsActivitiesController extends AbstractController
{
    private $suivis;
    private $affectations;

    public function __construct()
    {
        $this->affectations = [];
        $this->suivis = [];
    }

    #[Route('/news', name: 'back_news_activities')]
    public function newsActivitiesSinceLastLogin(NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService, Request $request): Response
    {
        $title = 'Administration - Nouveaux suivis';
        $newsActivitiesSinceLastLoginService->getAll()->filter(function ($activity){
            $class =  substr(get_class($activity), strrpos(get_class($activity), '\\') + 1);
            if($class === "Suivi")
                $this->suivis[] = $activity;
            elseif($class === "Affectation")
                $this->affectations[] = $activity;
        });

        if (empty($this->suivis) && empty($this->affectations))
            return $this->redirectToRoute('back_index');
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('clear_news_' . $this->getUser()->getId(), $request->get('_token'))) {
            $newsActivitiesSinceLastLoginService->clear();
            $this->addFlash('success','Les nouveaux suivis ont été marqués comme lus.');
            return $this->redirectToRoute('back_index');
        }
        return $this->render('back/news_activities/index.html.twig', [
            'title' => $title,
            'suivis' => $this->suivis,
            'affectations' => $this->affectations,
        ]);
    }
}