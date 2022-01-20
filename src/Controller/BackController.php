<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\SignalementUserAffectationRepository;
use App\Service\NewsActivitiesSinceLastLoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[Route('/bo')]
class BackController extends AbstractController
{
    #[Route('/', name: 'back_index')]
    public function index(RequestStack$requestStack,SignalementRepository $signalementRepository, SignalementUserAffectationRepository $affectationRepository, Request $request): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $filter = [
            'status' => $request->get('bo-filter-statut') ?? 'all',
            'ville' => $request->get('bo-filter-ville') ?? 'all'
        ];
        $signalements = [
            'list' => $signalementRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'],$request->get('search')),
            'villes' => $signalementRepository->findCities($user)
        ];
        if (!$user) {
            $signalements['counts'] = [
                Signalement::STATUS_NEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEW]),
                Signalement::STATUS_AWAIT => $signalementRepository->count(['statut' => Signalement::STATUS_AWAIT]),
                Signalement::STATUS_NEED_REVIEW => $signalementRepository->count(['statut' => Signalement::STATUS_NEED_REVIEW]),
                Signalement::STATUS_CLOSED => $signalementRepository->count(['statut' => Signalement::STATUS_CLOSED]),
            ];
        } else {
            $signalements['counts'] = [
                Signalement::STATUS_NEW => $affectationRepository->countForUser(Signalement::STATUS_NEW, $user),
                Signalement::STATUS_AWAIT => $affectationRepository->countForUser(Signalement::STATUS_AWAIT, $user),
                Signalement::STATUS_NEED_REVIEW => $affectationRepository->countForUser(Signalement::STATUS_NEED_REVIEW, $user),
                Signalement::STATUS_CLOSED => $affectationRepository->countForUser(Signalement::STATUS_CLOSED, $user),
            ];
        }
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'signalements' => $signalements,
        ]);
    }

    #[Route('/news',name:'back_news_activities')]
    public function newsActivitiesSinceLastLogin(NewsActivitiesSinceLastLoginService $newsActivitiesSinceLastLoginService): Response
    {
        $title = 'Administration - Nouveaux suivis';
        $suivis = $newsActivitiesSinceLastLoginService->getAll();
        if($suivis->isEmpty())
            return $this->redirectToRoute('back_index');
        return $this->render('back/news_activities/index.html.twig',[
            'title'=> $title,
            'suivis' => $suivis
        ]);
    }
}
