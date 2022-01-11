<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackController extends AbstractController
{
    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request): Response
    {
        $title = 'Administration';
        $signalements = [
            'list' => $signalementRepository->findByStatusAndOrCity($request->get('bo-filter-statut') ?? Signalement::STATUS_NEW, $request->get('bo-filter-ville') ?? 'all'),
            'counts' => [
                Signalement::STATUS_NEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEW]),
                Signalement::STATUS_AWAIT => $signalementRepository->count(['statut' => Signalement::STATUS_AWAIT]),
                Signalement::STATUS_NEED_REVIEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEED_REVIEW]),
                Signalement::STATUS_CLOSED => $signalementRepository->count(['statut' => Signalement::STATUS_CLOSED]),
            ],
            'villes' => $signalementRepository->findCities()
        ];
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'signalements' => $signalements,
        ]);
    }
    #[Route('/s/{id}', name: 'back_signalement_view')]
    public function viewSignalement(Signalement $signalement,Request $request): Response
    {
        $title = 'Administration - Signalement #'.$signalement->getReference();
        return $this->render('back/signalement/view.html.twig',[
            'title'=>$title,
            'signalement'=>$signalement
        ]);
    }
    #[Route('/s/{id}/delete', name: 'back_signalement_delete',methods: "DELETE")]
    public function deleteSignalement(Signalement $signalement,Request $request,ManagerRegistry $doctrine): Response
    {
        if($this->isCsrfTokenValid('signalement_delete',$request->get('_token')))
        {
            $doctrine->getManager()->remove($signalement);
            $doctrine->getManager()->flush();
            $this->addFlash('success','Signalement supprimé ave succès !');
        }
        return $this->redirectToRoute('back_index');
    }
}
