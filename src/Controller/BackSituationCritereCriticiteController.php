<?php

namespace App\Controller;

use App\Repository\SituationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/scc')]
class BackSituationCritereCriticiteController extends AbstractController
{
    #[Route('/')]
    public function index(SituationRepository $situationRepository)
    {
        $title = 'Cartographie';
        return $this->render('back/scc/index.html.twig', [
            'title'=>$title,
            'situations' => $situationRepository->findAllWithCritereAndCriticite(),
        ]);
    }
}