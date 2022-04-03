<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Cloture;
use App\Entity\Config;
use App\Entity\Signalement;
use App\Form\ConfigType;
use App\Repository\AffectationRepository;
use App\Repository\ConfigRepository;
use App\Repository\PartenaireRepository;
use App\Repository\SignalementRepository;
use App\Service\NewsActivitiesSinceLastLoginService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/bo')]
class BackController extends AbstractController
{
    private $req;
    private $iterator;


    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request, AffectationRepository $affectationRepository, PartenaireRepository $partenaireRepository): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $filter = [
            'search' => $request->get('search') ?? null,
            'status' => $request->get('bo-filter-statut') ?? 'all',
            'ville' => $request->get('bo-filter-ville') ?? 'all',
            'partenaire' => $request->get('bo-filter-partenaire') ?? 'all',
            'page' => $request->get('page') ?? 1,
        ];
        if ($user || $filter['partenaire'] !== 'all')
            $this->req = $affectationRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'], $filter['search'], $filter['partenaire'], $filter['page']);
        else
            $this->req = $signalementRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'], $filter['search'], $filter['page']);
        $this->iterator = $this->req->getIterator()->getArrayCopy();

        $signalements = [
            'list' => $this->iterator,
            'villes' => $signalementRepository->findCities($user),
            'total' => count($this->req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($this->req) / 50)
        ];
        $signalements['counts'] = $signalementRepository->countByStatus();
        if ($user) {
            $counts = $affectationRepository->countByStatusForUser($user);
            $signalements['counts'] = [
                Signalement::STATUS_NEED_VALIDATION => $counts[0] ?? ['count' => 0],
                Signalement::STATUS_ACTIVE => $counts[1] ?? ['count' => 0],
                Signalement::STATUS_CLOSED => ['count' => ($counts[3]['count'] ?? 0) + ($counts[2]['count'] ?? 0)],
            ];
            if ($user->getPartenaire()) {
                $status = [
                    Affectation::STATUS_WAIT => Signalement::STATUS_NEED_VALIDATION,
                    Affectation::STATUS_ACCEPTED => Signalement::STATUS_ACTIVE,
                    Affectation::STATUS_CLOSED => Signalement::STATUS_CLOSED,
                    Affectation::STATUS_REFUSED => Signalement::STATUS_CLOSED,
                ];
                foreach ($this->iterator as $item)
                    $item->getSignalement()->setStatut((int)$status[$item->getStatut()]);
            }
        }
//        dd($signalements['counts']);
        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->render('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'partenaires' => $partenaireRepository->findAllList(),
            'signalements' => $signalements,
        ]);
    }
}
