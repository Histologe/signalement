<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Cloture;
use App\Entity\Config;
use App\Entity\Signalement;
use App\Form\ConfigType;
use App\Repository\ConfigRepository;
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
    #[Route('/', name: 'back_index')]
    public function index(SignalementRepository $signalementRepository, Request $request): Response
    {
        $title = 'Administration - Tableau de bord';
        $user = null;
        if (!$this->isGranted('ROLE_ADMIN_PARTENAIRE'))
            $user = $this->getUser();
        $filter = [
            'search' => $request->get('search') ?? null,
            'status' => $request->get('bo-filter-statut') ?? 'all',
            'ville' => $request->get('bo-filter-ville') ?? 'all',
            'page' => $request->get('page') ?? 1,
        ];
        $req = $signalementRepository->findByStatusAndOrCityForUser($user, $filter['status'], $filter['ville'], $filter['search'], $filter['page']);

        if ($this->getUser()->getPartenaire()) {
            foreach ($req as $k => $signalement) {
                $signalement->getAffectations()->filter(function (Affectation $affectation) use ($signalement,$filter) {
                    if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $affectation->getStatut() === Affectation::STATUS_WAIT)
                        $signalement->setStatut(Signalement::STATUS_NEED_PARTNER_RESPONSE);
                    if ($affectation->getPartenaire()->getId() === $this->getUser()->getPartenaire()->getId() && $affectation->getStatut() === Affectation::STATUS_CLOSED)
                        $signalement->setStatut(Signalement::STATUS_CLOSED);
                });
               if($filter['status'] === "3" && $signalement->getStatut() !== 3)
                   unset($req[$k]);
            }
        }
        $signalements = [
            'list' => $req,
            'villes' => $signalementRepository->findCities($user),
            'total' => count($req),
            'page' => (int)$filter['page'],
            'pages' => (int)ceil(count($req) / 50)
        ];
        $signalements['counts'] = $signalementRepository->countByStatus($user);
        if (/*$request->isXmlHttpRequest() && */ $request->get('pagination'))
            return $this->render('back/table_result.html.twig', ['filter' => $filter, 'signalements' => $signalements]);
        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filter' => $filter,
            'signalements' => $signalements,
        ]);
    }
}
