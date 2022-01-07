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
        $title = 'Un service public pour les locataires et propriétaires';
        return $this->render('front/index.html.twig', [
            'title'=> $title
            //TODO: Includes stats
        ]);
    }
    #[Route('/qui-sommes-nous', name: 'about')]
    public function about(): Response
    {
        $title = 'Qui sommes-nous ?';
        return $this->render('front/about.html.twig',[
            'title'=>$title
        ]);
    }
    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        $title = "Conditions Générales d'Utilisation";
        return $this->render('front/cgu.html.twig',[
            'title'=>$title
        ]);
    }
    #[Route('/faq', name: 'faq')]
    public function faq(): Response
    {
        $title = "Conditions Générales d'Utilisation";
        return $this->render('front/cgu.html.twig',[
            'title'=>$title
        ]);
    }
    #[Route('/cgu', name: 'cgu')]
    public function cgu(): Response
    {
        $title = "Conditions Générales d'Utilisation";
        return $this->render('front/cgu.html.twig',[
            'title'=>$title
        ]);
    }
}
