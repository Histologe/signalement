<?php

namespace App\Controller;

use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{

    #[Route('/',name:'back_cartographie')]
    public function index(SignalementRepository $signalementRepository): Response
    {
        $title = 'Cartographie';
        return $this->render('back/cartographie/index.html.twig', [
            'title'=>$title,
            'signalements' => $signalementRepository->findAllWithGeoData(),
        ]);
    }
}