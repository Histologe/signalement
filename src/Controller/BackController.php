<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackController extends AbstractController
{
    #[Route('/bo', name: 'back_index')]
    public function index(): Response
    {
        $title = 'Administration';
        return $this->render('back/index.html.twig', [
            'title' => $title,
        ]);
    }
}
