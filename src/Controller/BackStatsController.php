<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Situation;
use App\Repository\SignalementRepository;
use App\Repository\SituationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/stats')]
class BackStatsController extends AbstractController
{
    #[Route('/', name: 'back_statistique')]
    public function index(SignalementRepository $signalementRepository, SituationRepository $situationRepository, EntityManagerInterface $entityManager): Response
    {
        $title = 'Statistiques';
        $dates = [];
        $totaux = ['open' => 0, 'closed' => 0, 'all' => 0];
        $villes = [];
        $signalements = $entityManager->createQuery("SELECT s.id,s.statut,s.createdAt,s.villeOccupant FROM App\Entity\Signalement AS s")->getResult();
        foreach ($signalements as $signalement) {
            $dates[$signalement['createdAt']->format('M Y')]['close'] ?? $dates[$signalement['createdAt']->format('M Y')]['close'] = 0;
            $dates[$signalement['createdAt']->format('M Y')]['open'] ?? $dates[$signalement['createdAt']->format('M Y')]['open'] = 0;
            if ($signalement['statut'] === Signalement::STATUS_CLOSED) {
                $dates[$signalement['createdAt']->format('M Y')]['close']++;
                $totaux['closed']++;
            } else {
                $dates[$signalement['createdAt']->format('M Y')]['open']++;
                $totaux['open']++;
            }
            true === !isset($villes[mb_strtoupper($signalement['villeOccupant'])]) ?
                $villes[mb_strtoupper($signalement['villeOccupant'])] = 1 : $villes[mb_strtoupper($signalement['villeOccupant'])]++;
            $totaux['all']++;
        }
        $criteres = $entityManager->getConnection()
            ->createQueryBuilder()
            ->select("c.label,COUNT(critere_id) as count")
            ->from("signalement_critere")
            ->leftJoin('signalement_critere', 'critere', 'c', 'signalement_critere.critere_id = c.id')
            ->groupBy('c.id')->fetchAllAssociativeIndexed();
        $situations= $entityManager->getConnection()
            ->createQueryBuilder()
            ->select("s.label,COUNT(situation_id) as count")
            ->from("signalement_situation")
            ->leftJoin('signalement_situation', 'situation', 's', 'signalement_situation.situation_id = s.id')
            ->groupBy('s.id')->fetchAllAssociativeIndexed();


        arsort($criteres);
        arsort($villes);
        return $this->render('back/statistique/index.html.twig', [
            'title' => $title,
            'dates' => $dates,
            'totaux' => $totaux,
            'situations' => $situations,
            'criteres' => $criteres,
            'villes' => $villes
        ]);
    }
}