<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Situation;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/stats')]
class BackStatsController extends AbstractController
{
    #[Route('/', name: 'back_statistique')]
    public function index(SignalementRepository $signalementRepository,SituationRepository $situationRepository): Response
    {
        $title = 'Statistiques';
        $dates = [];
        $totaux = ['open' => 0, 'closed' => 0];
        $situations = [];
        $criteres = [];
        $signalements = $signalementRepository->findAll();
        foreach ($signalements as $signalement) {
            if ($signalement->getStatut() === Signalement::STATUS_CLOSED) {
                $dates[$signalement->getCreatedAt()->format('M Y')]['close'][] = $signalement;
                $totaux['closed']++;
            } else {
                $dates[$signalement->getCreatedAt()->format('M Y')]['open'][] = $signalement;
                $totaux['open']++;
            }
        }
        foreach ($situationRepository->findAllWithCritereAndCriticite() as $situation){
            $situations[$situation->getLabel()] = $situation->getSignalements()->count();
            foreach ($situation->getCriteres() as $critere)
            {
                $criteres[$critere->getLabel()] = $critere->getSignalements()->count();
            }
        }
        return $this->render('back/statistique/index.html.twig', [
            'title' => $title,
            'dates' => $dates,
            'totaux'=>$totaux,
            'situations' => $situations,
            'criteres' => $criteres,
        ]);
    }
}