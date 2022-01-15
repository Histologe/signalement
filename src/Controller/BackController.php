<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use App\Repository\SignalementUserAffectationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackController extends AbstractController
{

    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, SignalementUserAffectationRepository $affectationRepository, Request $request): Response
    {
        //TODO: Formulaire de recherche
        $title = 'Administration';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $signalements = [
            'list' => $signalementRepository->findByStatusAndOrCityForUser($user, $request->get('bo-filter-statut') ?? 'all', $request->get('bo-filter-ville') ?? 'all'),
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
            'signalements' => $signalements,
        ]);
    }

}
