<?php

namespace App\Controller;

use App\Repository\SituationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SignalementController extends AbstractController
{
    #[Route('/signalement', name: 'signalement')]
    public function index(SituationRepository $situationRepository): Response
    {
        $etats = ["Etat moyen","Mauvais état","Très mauvais état"];
        $etats_classes = ["moyen","grave","tres-grave"];
        return $this->render('front/signalement.html.twig', [
            'situations' => $situationRepository->findAllActive(['isActive'=>true]),
            'etats' => $etats,
            'etats_classes' => $etats_classes
        ]);
    }
}
