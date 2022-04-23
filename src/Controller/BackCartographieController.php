<?php

namespace App\Controller;

use App\Repository\CritereRepository;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{

    #[Route('/', name: 'back_cartographie')]
    public function index(SignalementRepository $signalementRepository,Request $request, CritereRepository $critereRepository, PartenaireRepository $partenaireRepository): Response
    {
        $title = 'Cartographie';
        $filter = [
            'search' => $request->get('search') ?? null,
            'statuses' => $request->get('bo-filter-statut') ?? null,
            'cities' => $request->get('bo-filter-ville') ?? null,
            'partners' => $request->get('bo-filter-partenaires') ?? null,
            'criteres' => $request->get('bo-filter-criteres') ?? null,
            'allocs' => $request->get('bo-filter-allocs') ?? null,
            'housetypes' => $request->get('bo-filter-housetypes') ?? null,
            'declarants' => $request->get('bo-filter-declarants') ?? null,
            'proprios' => $request->get('bo-filter-proprios') ?? null,
            'interventions' => $request->get('bo-filter-interventions') ?? null,
            'visites' => $request->get('bo-filter-visites') ?? null,
            'dates' => $request->get('bo-filter-dates') ?? null,
        ];
        $signalements = $signalementRepository->findAllWithGeoData($filter);
//        dd($signalements->getQuery()->getResult());
//        $signalements['cities'] = $signalementRepository->findCities($user ?? null);
        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'cities' => $signalementRepository->findCities($user ?? null),
            'partenaires' => $partenaireRepository->findAllList(),
            'signalements' => $signalements,
            'criteres' => $critereRepository->findAllList()
        ]);
    }
}