<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig', [
            //TODO: Includes stats
        ]);
    }
    #[Route('/qui-sommes-nous', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
